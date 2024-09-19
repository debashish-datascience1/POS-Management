@extends('layouts.app')
@section('title', __('lang_v1.add_production_unit'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_production_unit')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductionController::class, 'store']), 'method' => 'post', 'id' => 'production_add_form' ]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('date', @format_date('now'), ['class' => 'form-control', 'required', 'placeholder' => __('messages.date'), 'id' => 'date']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('raw_material', __('lang_v1.raw_material') . ':*') !!}
                        {!! Form::number('raw_material', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.raw_material')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('product_id', __('lang_v1.product') . ':*') !!}
                        {!! Form::select('product_id', $products->pluck('name', 'id'), null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        $('#date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        $('.select2').select2();
    });
</script>
@endsection