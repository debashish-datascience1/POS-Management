@extends('layouts.app')

@section('title', __('lang_v1.create_salary'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.create_salary')
        <small>@lang('lang_v1.add_new_salary')</small>
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => route('salaries.store'), 'method' => 'POST', 'id' => 'salary_form']) !!}

    <div class="box box-solid">
        <div class="box-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <!-- User Selection -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('user_id', __('lang_v1.employee') . ':*') !!}
                        {!! Form::select('user_id', 
                            $users->pluck('first_name', 'id')->mapWithKeys(function ($first_name, $id) use ($users) {
                                // Concatenate first name and last name
                                $last_name = $users->find($id)->last_name;
                                return [$id => $first_name . ' ' . $last_name];
                            }),
                            null, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>

                <!-- Salary Date -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('salary_date', __('lang_v1.salary_date') . ':*') !!}
                        {!! Form::text('salary_date', null, ['class' => 'form-control datepicker', 'required', 'readonly']) !!}
                    </div>
                </div>

                <!-- Basic Salary -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('basic_salary', __('lang_v1.basic_salary') . ':*') !!}
                        {!! Form::number('basic_salary', null, ['class' => 'form-control', 'required', 'min' => 0, 'step' => '0.01']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Deduction -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('deduction', __('lang_v1.deduction') . ' (%)' . ':') !!}
                        {!! Form::number('deduction', 0, ['class' => 'form-control', 'min' => 0, 'max' => 100, 'step' => '0.01']) !!}
                    </div>
                </div>

                <!-- Tax Deduction -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('tax_deduction', __('lang_v1.tax_deduction') . ' (%)' . ':') !!}
                        {!! Form::number('tax_deduction', 0, ['class' => 'form-control', 'min' => 0, 'max' => 100, 'step' => '0.01']) !!}
                    </div>
                </div>

                <!-- Net Salary -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('net_salary', __('lang_v1.net_salary') . ':') !!}
                        {!! Form::number('net_salary', null, ['class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Bank Account Number -->
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('bank_account_number', __('lang_v1.bank_account_number') . ':') !!}
                        {!! Form::text('bank_account_number', null, ['class' => 'form-control']) !!}
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
                            null, 
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
                            null, 
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select'), 'required']
                        ) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
            <a href="{{ route('salaries.index') }}" class="btn btn-default">@lang('messages.cancel')</a>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2();

        // Initialize date picker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            endDate: '0d'
        });

        // Calculate Net Salary
        function calculateNetSalary() {
            var basicSalary = parseFloat($('#basic_salary').val()) || 0;
            var deduction = parseFloat($('#deduction').val()) || 0;
            var taxDeduction = parseFloat($('#tax_deduction').val()) || 0;

            var deductionAmount = (basicSalary * deduction) / 100;
            var taxDeductionAmount = (basicSalary * taxDeduction) / 100;
            var netSalary = basicSalary - deductionAmount - taxDeductionAmount;

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
                },
                payment_mode: 'required',
                salary_payment_mode: 'required'
            },
            messages: {
                user_id: LANG.required,
                salary_date: LANG.required,
                basic_salary: {
                    required: LANG.required,
                    number: LANG.number,
                    min: LANG.min_value
                }
            },
            submitHandler: function(form) {
                $(form).find('button[type="submit"]').prop('disabled', true);
                form.submit();
            }
        });
    });
</script>
@endsection
