@extends('layouts.vertical', ['title' => 'Check-in Settings', 'subTitle' => 'Settings'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Check-in Availability</h4>
    </div>
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('settings.checkins.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <label class="form-label">Availability Hours</label>
                    <input type="number" name="hours" min="1" max="168" class="form-control @error('hours') is-invalid @enderror" value="{{ old('hours', $hours) }}">
                    @error('hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Controls how long check-ins are considered available.</div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
