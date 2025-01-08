@extends('layouts.app')
@section('title', __('lang_v1.add_user'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_user')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['route' => 'users.store', 'method' => 'POST', 'id' => 'user_add_form', 'enctype' => 'multipart/form-data']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('surname', __('lang_v1.messages.surname') . ':*') !!}
                            {!! Form::text('surname', old('surname'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_surname')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('first_name', __('lang_v1.messages.first_name') . ':*') !!}
                            {!! Form::text('first_name', old('first_name'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_first_name')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('last_name', __('lang_v1.messages.last_name') . ':*') !!}
                            {!! Form::text('last_name', old('last_name'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_last_name')]) !!}
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('email', __('lang_v1.messages.email') . ':*') !!}
                            {!! Form::email('email', old('email'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_email')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('phone', __('lang_v1.messages.phone') . ':*') !!}
                            {!! Form::text('phone', old('phone'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_phone')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('gender', __('lang_v1.messages.gender') . ':*') !!}
                            {!! Form::select('gender', ['' => __('messages.select_gender'), 'Male' => __('messages.male'), 'Female' => __('messages.female'), 'Other' => __('messages.other')], old('gender'), ['class' => 'form-control select2', 'required']) !!}
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('address', __('lang_v1.messages.address') . ':*') !!}
                            {!! Form::textarea('address', old('address'), ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.messages.enter_address')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('file_url', __('lang_v1.messages.attach_file') . ':') !!}
                            {!! Form::file('file_url', ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('lang_v1.messages.save')</button>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize Select2 for gender select
    $('.select2').select2({
        width: '100%'
    });

    // Optional: You can add more JS functionalities here
});
</script>
@endsection
