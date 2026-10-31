<h1>New shift signup</h1>

<p>
    <b>{{ $volunteer_display_name }}</b> {{ $admin_assigned ? 'was assigned to' : 'signed up for' }} a shift.
</p>

@include('emails.partials.volunteer-details')

<h3>Shift</h3>
<ul>
    <li>Event: <b>{{ $event_name }}</b></li>
    <li>Department: <b>{{ $department_name }}</b></li>
    <li>Shift: <b>{{ $shift_name }}</b></li>
    <li>Date: <b>{{ $start_date }}</b></li>
    <li>Time: <b>{{ $start_time }} - {{ $end_time }}</b></li>
</ul>