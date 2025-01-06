@extends('layouts.app')

@section('title', __('lang_v1.edit_salary'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.edit_salary')
        <small>@lang('lang_v1.edit_existing_salary')</small>
    </h1>
</section>

<section class="content">
    {!! Form::model($salary, ['url' => route('salaries.update', $salary->id), 'method' => 'PUT', 'id' => 'salary_form']) !!}

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <!-- User Selection -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('user_id', __('lang_v1.employee') . ':*') !!}
                        {!! Form::select('user_id', 
                            $users->mapWithKeys(function ($user) {
                                return [$user->id => $user->first_name . ' ' . $user->last_name];
                            }), 
                            $salary->employee_id, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>

                <!-- Salary Date -->    
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('salary_date', __('lang_v1.salary_date') . ':*') !!}
                        {!! Form::text('salary_date', \Carbon\Carbon::parse($salary->salary_date)->format('Y-m-d'), ['class' => 'form-control datepicker', 'required']) !!}
                    </div>
                </div>

                <!-- Basic Salary -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('basic_salary', __('lang_v1.basic_salary') . ':*') !!}
                        {!! Form::number('basic_salary', $salary->basic_salary, ['class' => 'form-control', 'required', 'min' => 0, 'step' => '0.01']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Deduction -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('deduction', __('lang_v1.deduction') . ':') !!}
                        {!! Form::number('deduction', $salary->deduction, ['class' => 'form-control', 'min' => 0, 'step' => '0.01']) !!}
                    </div>
                </div>

                <!-- Tax Deduction -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('tax_deduction', __('lang_v1.tax_deduction') . ':') !!}
                        {!! Form::number('tax_deduction', $salary->tax_deduction, ['class' => 'form-control', 'min' => 0, 'step' => '0.01']) !!}
                    </div>
                </div>

                <!-- Net Salary (readonly) -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('net_salary', __('lang_v1.net_salary') . ':*') !!}
                        {!! Form::number('net_salary', $salary->net_salary, ['class' => 'form-control', 'readonly', 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Bank Account Number -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('bank_account_number', __('lang_v1.bank_account_number') . ':') !!}
                        {!! Form::text('bank_account_number', $salary->bank_account_number, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <!-- Payment Mode -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('payment_mode', __('lang_v1.payment_mode') . ':*') !!}
                        {!! Form::select('payment_mode', 
                            [
                                'cash' => __('lang_v1.cash'),
                                'bank_transfer' => __('lang_v1.bank_transfer'),
                                'cheque' => __('lang_v1.cheque')
                            ], 
                            $salary->payment_mode, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>

                <!-- Salary Payment Mode -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('salary_payment_mode', __('lang_v1.salary_payment_mode') . ':*') !!}
                        {!! Form::select('salary_payment_mode', 
                            [
                                'monthly' => __('lang_v1.monthly'),
                                'weekly' => __('lang_v1.weekly'),
                                'daily' => __('lang_v1.daily')
                            ], 
                            $salary->salary_payment_mode, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">
                @lang('messages.update')
            </button>
            <a href="{{ route('salaries.index') }}" class="btn btn-danger">
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
        // Initialize date picker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });

        // Calculate Net Salary
        function calculateNetSalary() {
            var basicSalary = parseFloat($('#basic_salary').val()) || 0;
            var deduction = parseFloat($('#deduction').val()) || 0;
            var taxDeduction = parseFloat($('#tax_deduction').val()) || 0;

            if (deduction > 0 && deduction <= 100) {
                deduction = (deduction / 100) * basicSalary;  // Convert percentage to amount
            }

            if (taxDeduction > 0 && taxDeduction <= 100) {
                taxDeduction = (taxDeduction / 100) * basicSalary;  // Convert percentage to amount
            }

            var netSalary = basicSalary - deduction - taxDeduction;
            $('#net_salary').val(netSalary.toFixed(2));
        }

        // Bind calculation to input changes
        $('#basic_salary, #deduction, #tax_deduction').on('input', calculateNetSalary);

        // Form validation
        $('#salary_form').validate({
            rules: {
                user_id: 'required',
                salary_date: 'required',
                basic_salary: {
                    required: true,
                    number: true,
                    min: 0
                }
            }
        });
    });
</script>
@endsection
