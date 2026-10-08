@extends('layouts.main')

@section('contents')
    <div class="container">
        <h2>Selamat Datang AL</h2>
        <form action="{{ url('/logout') }}" method="post">
            @csrf
            <button type="submit" class="btn btn-primary">Logout</button>
        </form>
    </div>
@endsection
