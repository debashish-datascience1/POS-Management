<!-- resources/views/attendance/index.blade.php -->

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Employee Attendance</h1>

    <form action="{{ route('attendance.fetch') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="employee_id">Select Employee</label>
            <select name="employee_id" id="employee_id" class="form-control" required>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="year">Select Year</label>
            <input type="number" name="year" id="year" class="form-control" value="{{ old('year', date('Y')) }}" required>
        </div>

        <div class="form-group">
            <label for="month">Select Month</label>
            <input type="number" name="month" id="month" class="form-control" value="{{ old('month', date('m')) }}" required min="1" max="12">
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    @if(isset($attendances))
        <h3>Attendance for {{ $employee->name }} in {{ $startDate->format('F Y') }}</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d M, Y') }}</td>
                        <td>{{ $attendance->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
