@extends('layouts.app')
@section('title', 'Fee Settings')
@section('nav', 'admin.fees')

@section('content')
    <h1 class="page-title">Fee Settings</h1>


    <form class="ajax-form card-box" action="{{ route('admin.fees.update') }}">
        @foreach ($settings as $key => [$label])
            <div class="row mb-3 align-items-center">
                <label class="col-md-5 fw-semibold">{{ $label }}</label>
                <div class="col-md-4"><input name="settings[{{ $key }}]" class="form-control" value="{{ $values[$key]->setting_value ?? '' }}" required></div>
                <div class="col-md-3 text-muted small">
                    @if ($values[$key]->updated_at ?? null) Updated {{ $values[$key]->updated_at->format('M j, Y g:i A') }} @endif
                </div>
            </div>
        @endforeach
        <button class="btn btn-primary">Save settings</button>
    </form>
@endsection