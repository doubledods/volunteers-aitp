<h3>Volunteer</h3>
<ul>
    <li>Email: <b>{{ $volunteer_email }}</b></li>
    <li>Username: <b>{{ $volunteer_username }}</b></li>
    <li>Burner name: <b>{{ $volunteer_burner_name }}</b></li>
    <li>User ID: <b>{{ $volunteer_id }}</b></li>
    @if ($volunteer_profile_url)
        <li>Profile: <a href="{{ $volunteer_profile_url }}">{{ $volunteer_profile_url }}</a></li>
    @endif
</ul>