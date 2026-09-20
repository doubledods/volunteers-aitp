<?php

// Work out which hours to show. Pages can pass $timegrid in directly; otherwise use the event's settings.
if(!isset($timegrid))
{
    $timegrid = $event->timegridRange();
}

$hours = range($timegrid->start, $timegrid->end - 1);

?>

<div class="timegrid" data-start-hour="{{ $timegrid->start }}" data-end-hour="{{ $timegrid->end }}">
    <div class="row hidden-xs hidden-sm">
        <div class="col-sm-2"></div>
        <div class="times col-sm-10">
            <div class="hours">
                @foreach($hours as $hour)
                    <div class="time">{{ \App\Models\Event::hourLabel($hour) }}</div>
                @endforeach
            </div>
        </div> <!-- / .times -->
    </div> <!-- / .row -->

    <div class="row hidden-xs hidden-sm">
        <div class="col-sm-2"></div>
        <div class="background col-sm-10">
            <div class="hours">
                @foreach($hours as $hour)
                    <div class="time"></div>
                @endforeach
            </div>
        </div> <!-- / .background -->
    </div> <!-- / .row -->
</div> <!-- / .timegrid -->