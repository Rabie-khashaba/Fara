@extends('layouts.vertical', ['title' => 'Create Place', 'subTitle' => 'Places Management'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Create Place</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('places.store') }}">
            @csrf
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Place Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude') }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude') }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Country Code</label>
                    <input type="text" name="country_code" maxlength="2" class="form-control @error('country_code') is-invalid @enderror" value="{{ old('country_code', 'SA') }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Radius (KM)</label>
                    <input type="number" step="0.1" name="radius_km" class="form-control @error('radius_km') is-invalid @enderror" value="{{ old('radius_km', 30) }}" required>
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_predefined" value="1" id="placePredefined" @checked(old('is_predefined', true))>
                        <label class="form-check-label" for="placePredefined">Predefined Place</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('places.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Place</button>
            </div>
        </form>
    </div>
</div>
@endsection
