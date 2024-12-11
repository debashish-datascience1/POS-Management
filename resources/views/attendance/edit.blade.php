@extends('layouts.app')
@section('title', __('Edit Attendance'))
@section('content')
<section class="content-header">
    <h1>@lang('Edit Attendance')</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="form-group">
                    <label for="employee_id">@lang('Employee')</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $attendance->employee_id == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">@lang('Date')</label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ $attendance->date }}" required>
                </div>
                <div class="form-group">
                    <label for="check_in_time">@lang('Check-in Time')</label>
                    <input type="time" name="check_in_time" id="check_in_time" class="form-control" value="{{ $attendance->check_in_time }}" required>
                </div>
                <div class="form-group">
                    <label for="check_out_time">@lang('Check-out Time')</label>
                    <input type="time" name="check_out_time" id="check_out_time" class="form-control" value="{{ $attendance->check_out_time }}" required>
                </div>
                <div class="form-group">
                    <label for="leave_type">@lang('Leave Type')</label>
                    <input type="text" name="leave_type" id="leave_type" class="form-control" value="{{ $attendance->leave_type }}">
                </div>
                <div class="form-group">
                    <label for="total_hours_worked">@lang('Total Hours Worked')</label>
                    <input type="number" name="total_hours_worked" id="total_hours_worked" class="form-control" value="{{ $attendance->total_hours_worked }}" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="overtime_hours">@lang('Overtime Hours')</label>
                    <input type="number" name="overtime_hours" id="overtime_hours" class="form-control" value="{{ $attendance->overtime_hours }}" step="0.01">
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary">@lang('Save')</button>
                <a href="{{ route('attendance.index') }}" class="btn btn-default">@lang('Cancel')</a>
            </div>
        </form>
    </div>
</section>
@endsection
