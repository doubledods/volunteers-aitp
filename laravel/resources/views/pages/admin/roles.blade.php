@extends('app')

@section('content')
    <h1>Roles</h1>
    <p>
        Roles are tags on user accounts. Use them to mark who you've approved for an event
        (e.g. <code>aitp-2026</code>), then check who has picked shifts on the
        <a href="/approvals">Approvals</a> page.
    </p>
    <p>
        A role only stops someone signing up if a shift is set to require it (in the shift's
        role settings). Shifts set to "None" are open to everyone.
    </p>
    <hr>

    <h3>Add a role</h3>
    <form method="POST" action="/roles" class="form-inline">
        {{ csrf_field() }}
        <div class="form-group">
            <input type="text" name="name" class="form-control" placeholder="e.g. aitp-2026" maxlength="50" required pattern="[A-Za-z0-9-]+">
        </div>
        <button type="submit" class="btn btn-primary">Add role</button>
        <p class="help-block">Lowercase letters, numbers and dashes only.</p>
    </form>
    <hr>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Role</th>
                <th>Users with this role</th>
                <th>Shift settings using it</th>
                <th>&nbsp;</th>
            </tr>
        </thead>

        <tbody>
            @foreach($roles as $role)
                <?php
                    $users = isset($userCounts[$role->id]) ? $userCounts[$role->id] : 0;
                    $shifts = isset($shiftCounts[$role->id]) ? $shiftCounts[$role->id] : 0;
                ?>
                <tr>
                    <td><b>{{ $role->name }}</b></td>
                    <td><a href="/users?role={{ $role->id }}">{{ $users }}</a></td>
                    <td>{{ $shifts }}</td>
                    <td>
                        @if(in_array($role->name, $builtIn))
                            <span class="text-muted">Built-in</span>
                        @else
                            <form method="POST" action="/role/{{ $role->id }}/delete" style="display:inline"
                                  onsubmit="return confirm('Delete the {{ $role->name }} role? It will be removed from {{ $users }} user(s) and {{ $shifts }} shift setting(s). This can\'t be undone.');">
                                {{ csrf_field() }}
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
