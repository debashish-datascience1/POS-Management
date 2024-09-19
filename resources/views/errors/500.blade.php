@extends('layouts.app')
@section('title', __('Server Error'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 font-weight-bold">500</h1>
            <h2 class="mb-4">@lang('Server Error')</h2>
            <p class="lead">@lang('Oops! Something went wrong on our end. We\'re working to fix it.')</p>
            <a href="{{ url('/home') }}" class="btn btn-primary">@lang('Go Home')</a>
        </div>
    </div>
</div>
@endsection