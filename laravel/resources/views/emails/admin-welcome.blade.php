<h1>New user registered!</h1>

<p>
    A new user has registered on the Volunteer Database.
</p>

@include('emails.partials.volunteer-details', \App\Helpers::volunteerDetails($user))