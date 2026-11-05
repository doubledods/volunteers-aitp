@extends('app')

@section('content')
    <h1>Approvals</h1>
    <p>
        Pick an event and the role you use to mark people approved for it. Tagging someone here
        doesn't stop or allow any signups; it just records that you've approved them.
        Manage roles on the <a href="/roles">Roles</a> page.
    </p>

    <form method="GET" action="/approvals" class="form-inline">
        <div class="form-group">
            <label for="event">Event</label>
            <select name="event" id="event" class="form-control">
                <option value="">----</option>
                @foreach($events as $e)
                    <option value="{{ $e->id }}" {{ $event && $event->id == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="role">Approval role</label>
            <select name="role" id="role" class="form-control">
                <option value="">----</option>
                @foreach($roles as $r)
                    @if(!in_array($r->name, $builtIn))
                        <option value="{{ $r->id }}" {{ $role && $role->id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Show</button>
    </form>
    <hr>

    @if(Session::has('tag_result'))
        <?php $result = Session::get('tag_result'); ?>
        <div class="alert alert-info">
            <b>Tagged {{ count($result['added']) }}</b>{{ count($result['added']) ? ' (' . implode(', ', $result['added']) . ')' : '' }}.
            Already tagged: {{ count($result['already']) }}.
            @if(count($result['notFound']))
                <br><b>No account found for {{ count($result['notFound']) }}:</b> {{ implode(', ', $result['notFound']) }}
                <br><small>They may not have registered yet, or registered with a different email.</small>
            @endif
        </div>
    @endif

    @if($event && $role && !in_array($role->name, $builtIn))
        <h2>{{ $event->name }} &middot; {{ $role->name }}</h2>
        <p>
            <b>{{ $approvedScheduled->count() }}</b> approved with shifts &middot;
            <b>{{ $approvedNoShifts->count() }}</b> approved, no shifts yet &middot;
            <b>{{ $notApprovedScheduled->count() }}</b> signed up without approval
        </p>

        <h3>Approved, no shifts yet ({{ $approvedNoShifts->count() }})</h3>
        @if($approvedNoShifts->count())
            @include('pages.admin.partials.approval-table', ['users' => $approvedNoShifts, 'action' => 'untag'])
            <label>Their emails (to copy into a reminder):</label>
            <textarea class="form-control" rows="2" readonly onclick="this.select()">{{ $approvedNoShifts->pluck('email')->implode(', ') }}</textarea>
        @else
            <p class="text-muted">Nobody. Everyone approved has picked a shift.</p>
        @endif

        <h3>Signed up without approval ({{ $notApprovedScheduled->count() }})</h3>
        @if($notApprovedScheduled->count())
            <p class="help-block">These people have shifts in this event but don't have the {{ $role->name }} role. Check their shifts on their profile, then approve them or remove them from shifts.</p>
            @include('pages.admin.partials.approval-table', ['users' => $notApprovedScheduled, 'action' => 'tag'])
        @else
            <p class="text-muted">Nobody.</p>
        @endif

        <h3>Approved with shifts ({{ $approvedScheduled->count() }})</h3>
        @if($approvedScheduled->count())
            @include('pages.admin.partials.approval-table', ['users' => $approvedScheduled, 'action' => 'untag'])
        @else
            <p class="text-muted">Nobody yet.</p>
        @endif

        <hr>
        <h3>Approve a list of people</h3>
        <form method="POST" action="/approvals/tag-list">
            {{ csrf_field() }}
            <input type="hidden" name="event" value="{{ $event->id }}">
            <input type="hidden" name="role" value="{{ $role->id }}">
            <div class="form-group">
                <textarea name="list" class="form-control" rows="6" placeholder="One email or username per line (e.g. pasted from NocoDB)"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Tag with {{ $role->name }}</button>
        </form>
    @elseif(!$roles->reject(function($r) use ($builtIn) { return in_array($r->name, $builtIn); })->count())
        <p>You don't have any custom roles yet. <a href="/roles">Create one on the Roles page</a> first (e.g. <code>aitp-2026</code>).</p>
    @endif
@endsection
