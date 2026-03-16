@extends('layouts.vertical', ['title' => 'Edit Place', 'subTitle' => 'Places Management'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Edit Place</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('places.update', $place) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Place Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $place->name) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $place->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude', $place->latitude) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude', $place->longitude) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Country Code</label>
                    <input type="text" name="country_code" maxlength="2" class="form-control @error('country_code') is-invalid @enderror" value="{{ old('country_code', $place->country_code) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Radius (KM)</label>
                    <input type="number" step="0.1" name="radius_km" class="form-control @error('radius_km') is-invalid @enderror" value="{{ old('radius_km', $place->radius_km) }}" required>
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_predefined" value="1" id="placePredefined" @checked(old('is_predefined', $place->is_predefined))>
                        <label class="form-check-label" for="placePredefined">Predefined Place</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('places.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Place</button>
            </div>
        </form>
    </div>
</div>
@endsection
