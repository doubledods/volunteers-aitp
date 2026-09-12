@extends('app')

@section('content')
    <h1>Delete User: {{ $user->name }}</h1>
    <hr>

    {!! Form::open() !!}
        <p>
            Are you sure you want to delete <b>{{ $user->name }}</b> ({{ $user->email }})?
        </p>

        <p>
            This will permanently remove their profile data, uploaded files, and role
            assignments, and free up any shifts they were signed up for. This cannot be undone.
        </p>

        <button type="submit" class="btn btn-danger">Delete User</button>
        <a href="/user/{{ $user->id }}" class="btn btn-primary">Cancel</a>
    {!! Form::close() !!}
@endsection