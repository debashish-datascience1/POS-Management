@extends('layouts.app')
@section('title', __('lang_v1.employee_advance.create_employee_advance'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('employee_advance.create_employee_advance')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\EmployeeAdvanceController::class, 'store']), 'method' => 'post', 'id' => 'employee_advance_form', 'class' => 'employee_advance_form create']) !!}

    @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('user_id', __('lang_v1.employee_advance.employee') . ':*') !!}
                <div class="input-group">
                    {!! Form::select('user_id', $employees->pluck('full_name', 'id'), null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
                    {{-- @dd($employees); --}}
                    <span class="input-group-btn">
                        <button type="button" @if(!auth()->user()->can('employee.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\EmployeeController::class, 'create'], ['quick_add' => true])}}" title="@lang('employee.add_employee')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('date', __('lang_v1.employee_advance.advance_date') . ':*') !!}
                {!! Form::date('date', null, ['class' => 'form-control', 'required', 'placeholder' => __('employee_advance.advance_date')]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('refund', __('lang_v1.employee_advance.refund') . ':') !!}
                {!! Form::text('refund', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.employee_advance.refund')]); !!}
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('refund_date', __('lang_v1.employee_advance.refund_date') . ':') !!}
                {!! Form::date('refund_date', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.employee_advance.refund_date')]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('refund_amount', __('lang_v1.employee_advance.refund_amount') . ':') !!}
                {!! Form::number('refund_amount', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.employee_advance.refund_amount'), 'step' => '0.01']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('balance', __('lang_v1.employee_advance.balance') . ':*') !!}
                {!! Form::number('balance', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.employee_advance.balance'), 'step' => '0.01']); !!}
            </div>
        </div>
    </div>
    @endcomponent

    <div class="row">
        <div class="col-sm-12">
            <div class="text-center">
                <div class="btn-group">
                    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white submit_employee_advance_form">@lang('lang_v1.messages.save')</button>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script src="{{ asset('js/employee_advance.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        __page_leave_confirmation('#employee_advance_form');
    });
</script>
@endsection
