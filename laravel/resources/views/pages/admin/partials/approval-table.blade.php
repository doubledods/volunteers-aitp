{{-- Expects: $users, $event, $role, $shiftCounts, $action ('tag', 'untag' or null) --}}
<table class="table table-hover table-condensed">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Burner name</th>
            <th>Email</th>
            <th>Shifts in event</th>
            <th>&nbsp;</th>
        </tr>
    </thead>

    <tbody>
        @foreach($users as $user)
            <?php $burner = optional($user->data)->burner_name; ?>
            <tr>
                <td>{{ $user->id }}</td>
                <td><a href="/user/{{ $user->id }}">{{ $user->name }}</a></td>
                <td>{{ trim((string) $burner) !== '' ? $burner : '' }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ isset($shiftCounts[$user->id]) ? $shiftCounts[$user->id] : 0 }}</td>
                <td>
                    @if($action)
                        <form method="POST" action="/approvals/{{ $action }}" style="display:inline">
                            {{ csrf_field() }}
                            <input type="hidden" name="event" value="{{ $event->id }}">
                            <input type="hidden" name="role" value="{{ $role->id }}">
                            <input type="hidden" name="user" value="{{ $user->id }}">
                            @if($action == 'tag')
                                <button type="submit" class="btn btn-xs btn-success">Approve</button>
                            @else
                                <button type="submit" class="btn btn-xs btn-default"
                                        onclick="return confirm('Remove {{ $role->name }} from {{ $user->name }}?');">Remove tag</button>
                            @endif
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
