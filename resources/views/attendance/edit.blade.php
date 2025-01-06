@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Edit Attendance</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('attendance.index') }}"> Back</a>
            </div>
        </div>
    </div>
   
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
   
    <form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Employee Name:</strong>
                    <select name="user_id" id="user_id" class="form-control" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" 
                                {{ $attendance->user_id == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Select Year:</strong>
                    <select name="select_year" id="select_year" class="form-control" required>
                        @foreach(range(date('Y'), 2000) as $year)
                            <option value="{{ $year }}" 
                                {{ $year == $attendance->select_year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Select Month:</strong>
                    <select name="select_month" id="select_month" class="form-control" required>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}" 
                                {{ $month == $attendance->select_month ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="datesContainer" class="mt-4">
            @php
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $attendance->select_month, $attendance->select_year);
                $monthName = DateTime::createFromFormat('!m', $attendance->select_month)->format('F');
            @endphp
            
            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $currentDate = DateTime::createFromFormat('!Y-m-d', "{$attendance->select_year}-{$attendance->select_month}-{$day}");
                    $isWeekend = in_array($currentDate->format('w'), ['0', '6']);
                    
                    $dayRecord = $attendanceData->first(function($record) use ($day) {
                        return $record->day == $day;
                    });
                    
                    $dayStatus = $dayRecord ? $dayRecord->status : ($isWeekend ? 'weekend' : 'unknown');
                @endphp
                
                <div class="row mb-2">
                    <label class="col-md-2 col-form-label">
                        Date: {{ $day }}/{{ $monthName }}/{{ $attendance->select_year }}
                    </label>
                    
                    <select name="status[{{ $day }}]" class="form-select col-md-4 ms-2">
                        @php
                            $statusOptions = $isWeekend 
                                ? ['Weekend'] 
                                : ['Present', 'Absent', 'Half Day', 'Leave'];
                        @endphp
                        
                        @foreach($statusOptions as $status)
                            <option value="{{ strtolower($status) }}" 
                                {{ strtolower($dayStatus) == strtolower($status) ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endfor
        </div>

        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-success mt-3">Update Attendance</button>
        </div>
    </form>
</div>
@endsection

@section('css')
<style>
    .form-select {
        width: auto;
        display: inline-block;
    }
</style>
@endsection