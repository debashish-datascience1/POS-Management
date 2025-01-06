@extends('layouts.app')

@section('title', __('employee_advance.edit_employee_advance'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('employee_advance.edit_employee_advance')</h1>
</section>

<!-- Main content -->
<section class="content">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::model($employeeAdvance, [
        'url' => action([\App\Http\Controllers\EmployeeAdvanceController::class, 'update'], [$employeeAdvance->id]),
        'method' => 'PUT',
        'id' => 'employee_advance_form',
        'class' => 'employee_advance_form edit'
    ]) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('user_id', __('employee_advance.employee') . ':*') !!}
                    <div class="input-group">
                        {!! Form::select('user_id', 
                            $employees->pluck('full_name', 'id'), 
                            $employeeAdvance->user_id, 
                            ['class' => 'form-control select2', 
                             'required',
                             'placeholder' => __('messages.please_select'),
                             'data-allow-clear' => 'true'
                            ]
                        ) !!}
                        <span class="input-group-btn">
                            <button type="button" 
                                @if(!auth()->user()->can('employee.create')) disabled @endif 
                                class="btn btn-default bg-white btn-flat btn-modal" 
                                data-href="{{ action([\App\Http\Controllers\EmployeeController::class, 'create'], ['quick_add' => true]) }}" 
                                title="@lang('employee.add_employee')" 
                                data-container=".view_modal">
                                <i class="fa fa-plus-circle text-primary fa-lg"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('date', __('employee_advance.advance_date') . ':*') !!}
                    {!! Form::date('date', 
                        optional($employeeAdvance->date)->format('Y-m-d'), 
                        ['class' => 'form-control', 
                         'required', 
                         'placeholder' => __('employee_advance.advance_date')
                        ]
                    ) !!}
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('refund', __('employee_advance.refund') . ':') !!}
                    {!! Form::text('refund', 
                        $employeeAdvance->refund, 
                        ['class' => 'form-control', 
                         'placeholder' => __('employee_advance.refund')
                        ]
                    ) !!}
                </div>
            </div>

            <div class="clearfix"></div>

            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('refund_date', __('employee_advance.refund_date') . ':') !!}
                    {!! Form::date('refund_date', 
                        optional($employeeAdvance->refund_date)->format('Y-m-d'), 
                        ['class' => 'form-control', 
                         'placeholder' => __('employee_advance.refund_date')
                        ]
                    ) !!}
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('refund_amount', __('employee_advance.refund_amount') . ':') !!}
                    {!! Form::number('refund_amount', 
                        $employeeAdvance->refund_amount, 
                        ['class' => 'form-control input_number', 
                         'placeholder' => __('employee_advance.refund_amount'),
                         'step' => '0.01'
                        ]
                    ) !!}
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('balance', __('employee_advance.balance') . ':*') !!}
                    {!! Form::number('balance', 
                        $employeeAdvance->balance, 
                        ['class' => 'form-control input_number', 
                         'required',
                         'placeholder' => __('employee_advance.balance'),
                         'step' => '0.01'
                        ]
                    ) !!}
                </div>
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-sm-12">
            <div class="text-center">
                <button type="submit" class="btn btn-primary submit_employee_advance_form">
                    @lang('messages.update')
                </button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@push('javascripts')
<script type="text/javascript">
$(document).ready(function() {
    // Initialize select2
    $('.select2').select2();

    // Form validation
    $('#employee_advance_form').validate({
        rules: {
            user_id: {
                required: true
            },
            date: {
                required: true
            },
            balance: {
                required: true,
                number: true,
                min: 0
            },
            refund_amount: {
                number: true,
                min: 0
            }
        },
        messages: {
            user_id: {
                required: '@lang("validation.required", ["attribute" => __("employee_advance.employee")])'
            },
            date: {
                required: '@lang("validation.required", ["attribute" => __("employee_advance.advance_date")])'
            },
            balance: {
                required: '@lang("validation.required", ["attribute" => __("employee_advance.balance")])',
                number: '@lang("validation.numeric", ["attribute" => __("employee_advance.balance")])',
                min: '@lang("validation.min.numeric", ["attribute" => __("employee_advance.balance"), "min" => 0])'
            }
        },
        submitHandler: function(form) {
            $(form).find('button[type="submit"]').attr('disabled', true);
            form.submit();
        }
    });

    // Page leave confirmation
    __page_leave_confirmation('#employee_advance_form');

    // Format number inputs
    $(document).on('change', '.input_number', function() {
        var input = $(this);
        input.val(parseFloat(input.val()).toFixed(2));
    });
});
</script>
@endpush