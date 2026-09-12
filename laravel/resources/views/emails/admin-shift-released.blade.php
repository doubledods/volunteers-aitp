<h1>Shift released</h1>

<p>
    <b>{{ $volunteer_name }}</b> is no longer scheduled for this shift
    ({{ $removed_by_admin ? 'removed by an admin' : 'released by the volunteer' }}).
</p>

<ul>
    <li>Event: <b>{{ $event_name }}</b></li>
    <li>Department: <b>{{ $department_name }}</b></li>
    <li>Shift: <b>{{ $shift_name }}</b></li>
    <li>Date: <b>{{ $start_date }}</b></li>
    <li>Time: <b>{{ $start_time }} - {{ $end_time }}</b></li>
</ul>