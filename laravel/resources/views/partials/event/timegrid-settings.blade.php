<?php

use App\Models\Event;

// Settings for which hours the schedule's time grid shows
$detected = $event->detectedTimegridRange();

// End times can be 24, which reads better as "midnight"
$endLabel = function($hour)
{
    return $hour == 24 ? 'midnight' : Event::hourLabel($hour);
};

if($detected)
{
    $detectedText = "Your shifts run from " . Event::hourLabel($detected->start) . " to " . $endLabel($detected->end) . ".";
    $autoStart = "Automatic (" . Event::hourLabel($detected->start) . ")";
    $autoEnd = "Automatic (" . $endLabel($detected->end) . ")";
}
else
{
    $detectedText = "This event doesn't have any scheduled shifts yet, so the full day will be shown until you add some.";
    $autoStart = "Automatic";
    $autoEnd = "Automatic";
}

// Keep the user's input if the form failed validation
$hasOldInput = !is_null(old('_token'));
$customEnabled = $hasOldInput ? in_array('yes', (array)old('custom_timegrid', [])) : $event->custom_timegrid;
$startValue = $hasOldInput ? old('timegrid_start_hour') : $event->timegrid_start_hour;
$endValue = $hasOldInput ? old('timegrid_end_hour') : $event->timegrid_end_hour;

// Compare as strings so "12 am" (0) and "Automatic" (empty) aren't confused with each other
$startValue = is_null($startValue) ? '' : (string)$startValue;
$endValue = is_null($endValue) ? '' : (string)$endValue;

?>

<div class="timegrid-settings">
    @include('partials/form/checkbox',
    [
        'name' => 'custom_timegrid',
        'label' => 'Schedule hours',
        'options' => ['yes' => 'Only show the hours when shifts happen'],
        'selected' => $customEnabled ? ['yes'] : [],
        'help' => 'When this is off, the schedule shows the full day from 12 am to midnight.'
    ])

    <div class="timegrid-hours {{ $customEnabled ? '' : 'hidden' }}">
        <p class="help-block">
            {{ $detectedText }}
            Leave both times on automatic to fit the schedule to your shifts, or pick times to show more of the day.
            The schedule always stretches to include every shift, and every day uses the same hours.
        </p>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group {{ $errors->has('timegrid_start_hour') ? 'has-error' : '' }}">
                    <label class="control-label" for="timegrid_start_hour-field">Schedule starts at</label>
                    <select class="form-control" name="timegrid_start_hour" id="timegrid_start_hour-field">
                        <option value="" {{ $startValue === '' ? 'selected' : '' }}>{{ $autoStart }}</option>
                        @for($hour = 0; $hour <= 23; $hour++)
                            <option value="{{ $hour }}" {{ $startValue === (string)$hour ? 'selected' : '' }}>{{ Event::hourLabel($hour) }}</option>
                        @endfor
                    </select>

                    @if($errors->has('timegrid_start_hour'))
                        <span class="help-block">{{ $errors->first('timegrid_start_hour') }}</span>
                    @endif
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group {{ $errors->has('timegrid_end_hour') ? 'has-error' : '' }}">
                    <label class="control-label" for="timegrid_end_hour-field">Schedule ends at</label>
                    <select class="form-control" name="timegrid_end_hour" id="timegrid_end_hour-field">
                        <option value="" {{ $endValue === '' ? 'selected' : '' }}>{{ $autoEnd }}</option>
                        @for($hour = 1; $hour <= 24; $hour++)
                            <option value="{{ $hour }}" {{ $endValue === (string)$hour ? 'selected' : '' }}>{{ $endLabel($hour) }}</option>
                        @endfor
                    </select>

                    @if($errors->has('timegrid_end_hour'))
                        <span class="help-block">{{ $errors->first('timegrid_end_hour') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>