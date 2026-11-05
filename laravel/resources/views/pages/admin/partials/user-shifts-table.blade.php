<table class="table table-hover">
    <thead>
        <tr>
            <th>Event</th>
            <th>Department</th>
            <th>Shift</th>
            <th>Date</th>
            <th>Time</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>
        @foreach($slots as $slot)
            @if($slot->schedule && $slot->event)
                <tr>
                    <td><a href="/event/{{ $slot->event->id }}">{{ $slot->event->name }}</a></td>
                    <td>{{ $slot->department->name }}</td>
                    <td>{{ $slot->schedule->shift->name }}</td>
                    <td>{{ date('D M j, Y', strtotime($slot->start_date)) }}</td>
                    <td>{{ date('g:i a', strtotime($slot->start_time)) }} - {{ date('g:i a', strtotime($slot->end_time)) }}</td>
                    <td><a href="/slot/{{ $slot->id }}/view" class="btn btn-sm btn-default">View</a></td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
