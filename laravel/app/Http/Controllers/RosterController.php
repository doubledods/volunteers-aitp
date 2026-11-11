<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\Helpers;
use App\Models\Event;

/**
 * Public, read-only roster pages. No login required.
 * Shows only display names, never emails or real names.
 */
class RosterController extends Controller
{
    // /roster: all events, split into upcoming and past
    public function index()
    {
        $today = Carbon::today()->format('Y-m-d');

        $upcoming = Event::where('end_date', '>=', $today)->orderBy('start_date', 'asc')->get();
        $past = Event::where('end_date', '<', $today)->orderBy('start_date', 'desc')->get();

        return $this->noindex(response()->view('pages.roster.index', compact('upcoming', 'past')));
    }

    // /roster/{slug}: one event's roster (falls back to the event id)
    public function show($slug)
    {
        $event = Event::where('slug', $slug)->first();

        if(!$event && ctype_digit((string)$slug))
        {
            $event = Event::find($slug);
        }

        if(!$event)
        {
            abort(404);
        }

        $departments = $event->departments()
            ->with(['schedule.shift', 'schedule.slots.user.data'])
            ->orderBy('name')
            ->get();

        $roster = [];

        foreach($departments as $department)
        {
            $shifts = [];

            foreach($department->schedule as $schedule)
            {
                $people = [];

                foreach($schedule->slots as $slot)
                {
                    // Only slots that someone is assigned to
                    if(!$slot->user)
                    {
                        continue;
                    }

                    $start = $slot->start_time ?: $schedule->start_time;
                    $end = $slot->end_time ?: $schedule->end_time;

                    $people[] =
                    [
                        'sort' => ($slot->start_date ?: '9999-99-99') . ' ' . ($start ?: '99:99'),
                        'when' => $this->when($slot->start_date, $start, $end),
                        'name' => Helpers::displayName($slot->user),
                    ];
                }

                if(!$people)
                {
                    continue;
                }

                usort($people, function($a, $b)
                {
                    return strcmp($a['sort'] . ' ' . $a['name'], $b['sort'] . ' ' . $b['name']);
                });

                $shifts[] =
                [
                    'name' => $schedule->shift ? $schedule->shift->name : 'Shift',
                    'people' => $people,
                ];
            }

            if(!$shifts)
            {
                continue;
            }

            usort($shifts, function($a, $b)
            {
                return strcasecmp($a['name'], $b['name']);
            });

            $roster[] = ['name' => $department->name, 'shifts' => $shifts];
        }

        return $this->noindex(response()->view('pages.roster.show', compact('event', 'roster')));
    }

    // Tell search engines not to index these pages (works together with the meta tag in the views)
    private function noindex($response)
    {
        return $response->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // "Sat Nov 21 · 2:00 PM–4:00 PM", leaving out whatever isn't set
    private function when($date, $start, $end)
    {
        $parts = [];

        if($date)
        {
            $parts[] = $this->format($date, 'D M j');
        }

        if($start && $end)
        {
            $parts[] = $this->format($start, 'g:i A') . '–' . $this->format($end, 'g:i A');
        }

        return implode(' · ', $parts);
    }

    private function format($value, $format)
    {
        try
        {
            return Carbon::parse($value)->format($format);
        }
        catch(\Exception $e)
        {
            return $value;
        }
    }
}
