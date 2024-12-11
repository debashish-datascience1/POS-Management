@extends('layouts.app')
@section('title', 'Edit Employee Advance')
@section('content')

<section class="content-header">
    <h1>Edit Employee Advance</h1>
</section>

<section class="content">
    <form action="{{ route('employee_advance.update', $employeeAdvance->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="empid">Employee</label>
            <select name="empid" id="empid" class="form-control" required>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" 
                            {{ $employee->id == $employeeAdvance->empid ? 'selected' : '' }}>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="date">Advance Date</label>
            <input type="date" name="date" id="date" class="form-control" 
                   value="{{ old('date', $employeeAdvance->date) }}" required>
        </div>

        <div class="form-group">
            <label for="refund">Refund Status</label>
            <input type="text" name="refund" id="refund" class="form-control" 
                   value="{{ old('refund', $employeeAdvance->refund) }}">
        </div>

        <div class="form-group">
            <label for="refund_date">Refund Date</label>
            <input type="date" name="refund_date" id="refund_date" class="form-control" 
                   value="{{ old('refund_date', $employeeAdvance->refund_date) }}">
        </div>

        <div class="form-group">
            <label for="refund_amount">Refund Amount</label>
            <input type="number" name="refund_amount" id="refund_amount" class="form-control" 
                   value="{{ old('refund_amount', $employeeAdvance->refund_amount) }}">
        </div>

        <div class="form-group">
            <label for="balance">Balance</label>
            <input type="number" name="balance" id="balance" class="form-control" 
                   value="{{ old('balance', $employeeAdvance->balance) }}" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</section>

@endsection
