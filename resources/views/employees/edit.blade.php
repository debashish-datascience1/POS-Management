@extends('layouts.app')

@section('title', __('lang_v1.edit_employee'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.edit_employee')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\EmployeeController::class, 'update'], [$employee->id]), 'method' => 'PUT', 'id' => 'employee_edit_form', 'files' => true]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <!-- Name Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('name', __('messages.name') . ':*') !!}
                            {!! Form::text('name', $employee->name, ['class' => 'form-control', 'required', 'placeholder' => __('messages.enter_name')]) !!}
                        </div>
                    </div>

                    <!-- Age Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('age', __('messages.age') . ':*') !!}
                            {!! Form::number('age', $employee->age, ['class' => 'form-control', 'required', 'placeholder' => __('messages.enter_age')]) !!}
                        </div>
                    </div>

                    <!-- ID Proof Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('idprove', __('messages.idprove') . ':*') !!}
                            {!! Form::text('idprove', $employee->idprove, ['class' => 'form-control', 'required', 'placeholder' => __('messages.enter_idprove')]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Phone Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('phone', __('messages.phone') . ':*') !!}
                            {!! Form::text('phone', $employee->phone, ['class' => 'form-control', 'required', 'placeholder' => __('messages.enter_phone')]) !!}
                        </div>
                    </div>

                    <!-- Gender Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('gender', __('messages.gender') . ':*') !!}
                            {!! Form::select('gender', ['' => __('messages.select_gender'), 'Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'], $employee->gender, ['class' => 'form-control', 'required']) !!}
                        </div>
                    </div>

                    <!-- Address Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('address', __('messages.address') . ':*') !!}
                            {!! Form::textarea('address', $employee->address, ['class' => 'form-control', 'required', 'placeholder' => __('messages.enter_address')]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- File Upload Field -->
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('file_url', __('messages.attach_file') . ':') !!}
                            {!! Form::file('file_url', ['class' => 'form-control']) !!}
                            @if($employee->file_url)
                                <p>Current file: <a href="{{ Storage::url($employee->file_url) }}" target="_blank">{{ __('messages.view_file') }}</a></p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
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
        // Initialize form components, select2, etc.
        initializeSelect2();
        
        // Initialize select2 for any select fields
        function initializeSelect2() {
            $('.select2').select2({
                width: '100%'
            });
        }
    });
</script>
@endsection
