<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Schedule;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'featured', 'custom_timegrid', 'timegrid_start_hour', 'timegrid_end_hour'];

    protected $casts =
    [
        'custom_timegrid' => 'boolean',
    ];

    // Cached results of the time grid calculations, so they only run once per request
    private $detectedTimegridCache = false;
    private $timegridCache = null;

    public static function boot() {
        parent::boot();

        static::deleting(function($model) {
            $model->departments()->delete();
            $model->shifts()->delete();
        });
    }

    // Helper functions to select events by date
    public static function future($preferFeatured = false)
    {
        if($preferFeatured)
        {
            return Event::where('start_date', '>', Carbon::now())
                            ->where('featured', true)
                            ->orderBy('start_date', 'asc')->get();
        }

        return Event::where('start_date', '>', Carbon::now())
                        ->orderBy('start_date', 'asc')->get();
    }

    public static function present($preferFeatured = false)
    {
        if($preferFeatured)
        {
            return Event::where('start_date', '<', Carbon::now())
                            ->where('end_date', '>', Carbon::now())
                            ->where('featured', true)
                            ->orderBy('start_date', 'desc')->get();
        }

        return Event::where('start_date', '<', Carbon::now())
                        ->where('end_date', '>', Carbon::now())
                        ->orderBy('start_date', 'desc')->get();
    }

    public static function past($preferFeatured = false)
    {
        if($preferFeatured)
        {
            return Event::where('end_date', '<', Carbon::now())
                            ->where('featured', true)
                            ->orderBy('start_date', 'desc')->get();
        }

        return Event::where('end_date', '<', Carbon::now())
                        ->orderBy('start_date', 'desc')->get();
    }

    // Helper function to return the most recent ongoing or upcoming event
    public static function ongoingOrUpcoming($preferFeatured = true)
    {
        $ongoing = Event::present($preferFeatured)->first();

        if(!empty($ongoing))
        {
            return $ongoing;
        }

        $upcoming = Event::future($preferFeatured)->first();

        if(!empty($upcoming))
        {
            return $upcoming;
        }

        // Are there no featured events? Fall back to regular events
        if($preferFeatured)
        {
            return self::ongoingOrUpcoming(false);
        }

        return false;
    }

    // Events have departments
    public function departments()
    {
        return $this->hasMany('App\Models\Department');
    }

    // Events have shifts
    public function shifts()
    {
        return $this->hasMany('App\Models\Shift');
    }

    // Format an hour of the day (0-24) the same way the time grid labels it, e.g. "12 am", "6 pm"
    public static function hourLabel($hour)
    {
        $hour = (int)$hour % 24;
        $suffix = ($hour < 12) ? 'am' : 'pm';
        $display = $hour % 12;

        return ($display === 0 ? 12 : $display) . ' ' . $suffix;
    }

    // Convert a "HH:MM:SS" database time into seconds since midnight
    private static function timeToSeconds($time)
    {
        $parts = array_map('intval', explode(':', (string)$time));

        return ($parts[0] ?? 0) * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0);
    }

    // Find the earliest start and latest end of all dated shifts in this event, rounded out to whole hours.
    // Every day is checked, and the result covers all of them so that every day can share one grid.
    // Returns an object with start and end hours, or null when there are no dated shifts.
    public function detectedTimegridRange()
    {
        if($this->detectedTimegridCache !== false)
        {
            return $this->detectedTimegridCache;
        }

        $earliest = null;
        $latest = null;
        $firstDay = (new Carbon($this->start_date))->format('Y-m-d');
        $lastDay = (new Carbon($this->end_date))->format('Y-m-d');

        foreach($this->departments as $department)
        {
            foreach($department->slots as $slot)
            {
                // Skip shifts without a date, and anything outside the event dates (those aren't displayed)
                if(is_null($slot->start_date) || $slot->start_date < $firstDay || $slot->start_date > $lastDay)
                {
                    continue;
                }

                $start = self::timeToSeconds($slot->start_time);
                $end = self::timeToSeconds($slot->end_time);

                // A slot that ends at or before it starts wraps past midnight (for example 10 pm - 12 am
                // is stored with an end time of 00:00). Treat it as running to the end of the day.
                if($end <= $start)
                {
                    $end = 24 * 3600;
                }

                $earliest = is_null($earliest) ? $start : min($earliest, $start);
                $latest = is_null($latest) ? $end : max($latest, $end);
            }
        }

        if(is_null($earliest))
        {
            return $this->detectedTimegridCache = null;
        }

        return $this->detectedTimegridCache = (object)
        [
            'start' => (int)floor($earliest / 3600),
            'end' => (int)min(24, ceil($latest / 3600)),
        ];
    }

    // The hours shown on the schedule time grid, as an object with start (0-23) and end (1-24) hours.
    public function timegridRange()
    {
        if(!is_null($this->timegridCache))
        {
            return $this->timegridCache;
        }

        // Default: the whole day
        $start = 0;
        $end = 24;

        if($this->custom_timegrid)
        {
            $detected = $this->detectedTimegridRange();
            $manualStart = $this->timegrid_start_hour;
            $manualEnd = $this->timegrid_end_hour;

            // Manual values win over the detected ones
            $start = !is_null($manualStart) ? (int)$manualStart : ($detected ? $detected->start : 0);
            $end = !is_null($manualEnd) ? (int)$manualEnd : ($detected ? $detected->end : 24);

            // Never hide a shift: stretch the range if any shift falls outside it
            if($detected)
            {
                $start = min($start, $detected->start);
                $end = max($end, $detected->end);
            }

            // Guard against bad data so the grid always has at least one hour
            $start = max(0, min(23, $start));
            $end = max($start + 1, min(24, $end));
        }

        return $this->timegridCache = (object)
        [
            'start' => $start,
            'end' => $end,
        ];
    }

    // Helper function to generate a list of days the event will take place
    public function days($hideEmpty = false)
    {
        // Use carbon!
        $start_date = new Carbon($this->start_date);
        $end_date = new Carbon($this->end_date);

        // Array for output
        $days = [];

        // This only works when the start date is before the end date
        if($start_date->lte($end_date))
        {
            // do this once
            if($hideEmpty)
            {
                // get an array of all the dates fields for this event
                $shift_dates = Schedule::whereIn('shift_id', $this->shifts->pluck('id'))->whereNotNull('start_date')->pluck('dates');
                $merged_dates = [];
                //iterate through scheduled dates and merge them into one list
                foreach ($shift_dates as $i) {
                    $merged_dates = array_merge($merged_dates, json_decode($i));
                }
                // remove duplicate dates
                $shift_dates = array_unique( $merged_dates );
            }

            // $date keeps track of the current date as we loop towards the end
            $date = $start_date;

            while($date->lte($end_date))
            {
                if($hideEmpty)
                {
                    // check to see if the current day is in the list of shift_dates we prepared earlier
                    if(!in_array( $date->format('Y-m-d'), $shift_dates ))
                    {
                        // Continue onto the next day
                        $date->addDay();
                        continue;
                    }
                }

                $days[] = (object)
                [
                    'name' => $date->formatLocalized('%A'),
                    'date' => clone $date
                ];

                $date->addDay();
            }
        }

        return $days;
    }
}
