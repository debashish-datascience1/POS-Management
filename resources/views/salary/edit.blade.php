@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Salary</h2>

    <form action="{{ route('salaries.update', $salary->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="employee_id">Employee</label>
            <select name="employee_id" id="employee_id" class="form-control" required>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ $salary->employee_id == $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="salary_date">Salary Date</label>
            <input type="date" name="salary_date" id="salary_date" class="form-control" required value="{{ $salary->salary_date }}">
        </div>

        <div class="form-group">
            <label for="basic_salary">Basic Salary</label>
            <input type="number" name="basic_salary" id="basic_salary" class="form-control" required value="{{ $salary->basic_salary }}" oninput="calculateNetSalary()">
        </div>

        <div class="form-group">
            <label for="deduction">Deduction (%)</label>
            <input type="number" name="deduction" id="deduction" class="form-control" value="{{ $salary->deduction }}" oninput="calculateNetSalary()">
        </div>

        <div class="form-group">
            <label for="tax_deduction">Tax Deduction (%)</label>
            <input type="number" name="tax_deduction" id="tax_deduction" class="form-control" value="{{ $salary->tax_deduction }}" oninput="calculateNetSalary()">
        </div>

        <div class="form-group">
            <label for="net_salary">Net Salary</label>
            <input type="text" name="net_salary" id="net_salary" class="form-control" readonly value="{{ $salary->net_salary }}">
        </div>

        <div class="form-group">
            <label for="bank_account_number">Bank Account Number</label>
            <input type="text" name="bank_account_number" id="bank_account_number" class="form-control" required value="{{ $salary->bank_account_number }}">
        </div>

        <div class="form-group">
            <label for="payment_mode">Payment Mode</label>
            <select name="payment_mode" id="payment_mode" class="form-control" required>
                <option value="Cash" {{ $salary->payment_mode == 'Cash' ? 'selected' : '' }}>Cash</option>
                <option value="Bank Transfer" {{ $salary->payment_mode == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="Cheque" {{ $salary->payment_mode == 'Cheque' ? 'selected' : '' }}>Cheque</option>
            </select>
        </div>

        <div class="form-group">
            <label for="salary_payment_mode">Salary Payment Mode</label>
            <select name="salary_payment_mode" id="salary_payment_mode" class="form-control" required>
                <option value="Monthly" {{ $salary->salary_payment_mode == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="Weekly" {{ $salary->salary_payment_mode == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="Daily" {{ $salary->salary_payment_mode == 'Daily' ? 'selected' : '' }}>Daily</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Salary</button>
    </form>
</div>

<script>
    function calculateNetSalary() {
        var basicSalary = parseFloat(document.getElementById('basic_salary').value) || 0;
        var deduction = parseFloat(document.getElementById('deduction').value) || 0;
        var taxDeduction = parseFloat(document.getElementById('tax_deduction').value) || 0;

        var deductionAmount = (basicSalary * deduction) / 100;
        var taxDeductionAmount = (basicSalary * taxDeduction) / 100;

        var netSalary = basicSalary - deductionAmount - taxDeductionAmount;

        document.getElementById('net_salary').value = Math.floor(netSalary); // Math.floor to remove decimals
    }
</script>
@endsection
