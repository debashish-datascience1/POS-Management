@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Create Employee Advance</h1>
        <form action="{{ route('employee_advance.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="empid">Employee</label>
                <select name="empid" id="empid" class="form-control" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="date">Advance Date</label>
                <input type="date" name="date" id="date" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="refund">Refund</label>
                <input type="text" name="refund" id="refund" class="form-control">
            </div>
            <div class="form-group">
                <label for="refund_date">Refund Date</label>
                <input type="date" name="refund_date" id="refund_date" class="form-control">
            </div>
            <div class="form-group">
                <label for="refund_amount">Refund Amount</label>
                <input type="number" name="refund_amount" id="refund_amount" class="form-control" step="0.01">
            </div>
            <div class="form-group">
                <label for="balance">Balance</label>
                <input type="number" name="balance" id="balance" class="form-control" required step="0.01">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
@endsection
