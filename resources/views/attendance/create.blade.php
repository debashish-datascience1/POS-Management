@extends('layouts.app')
@section('title', __('Add Attendance'))

@section('content')
<section class="content-header">
    <h1>@lang('Add Attendance')
        <small>@lang('lang_v1.add_new_attendance')</small>
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => route('attendance.store'), 'method' => 'POST', 'id' => 'attendance_form']) !!}

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <!-- Employee Selection -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('employee_id', __('lang_v1.employee')) !!}
                        {!! Form::select('employee_id', 
                            $employees->pluck('name', 'id'), // Only showing employee names, using employee ID as value
                            null, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>

                <!-- Date -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('date', __('lang_v1.date')) !!}
                        {!! Form::date('date', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <!-- Check-in Time -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('check_in_time', __('lang_v1.check_in_time')) !!}
                        {!! Form::time('check_in_time', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Check-out Time -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('check_out_time', __('lang_v1.check_out_time')) !!}
                        {!! Form::time('check_out_time', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <!-- Leave Type -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('leave_type', __('lang_v1.leave_type')) !!}
                        {!! Form::text('leave_type', null, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <!-- Total Hours Worked -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('total_hours_worked', __('lang_v1.total_hours_worked')) !!}
                        {!! Form::number('total_hours_worked', null, ['class' => 'form-control', 'step' => '0.01', 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Overtime Hours -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('overtime_hours', __('lang_v1.overtime_hours')) !!}
                        {!! Form::number('overtime_hours', null, ['class' => 'form-control', 'step' => '0.01']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">
                @lang('messages.save')
            </button>
            <a href="{{ route('attendance.index') }}" class="btn btn-danger">
                @lang('messages.cancel')
            </a>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Form validation
        $('#attendance_form').validate({
            rules: {
                employee_id: 'required',
                date: 'required',
                check_in_time: 'required',
                check_out_time: 'required',
                total_hours_worked: {
                    required: true,
                    number: true,
                    min: 0
                }
            }
        });
    });
</script>
@endsection
