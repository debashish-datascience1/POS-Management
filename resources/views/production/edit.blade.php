@extends('layouts.app')

@section('title', __('production.edit_production_unit'))

@section('content')

<section class="content-header">
    <h1>@lang('production.edit_production_unit')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action([\App\Http\Controllers\ProductionController::class, 'update'], [$production_unit->id]), 'method' => 'PUT', 'id' => 'production_edit_form' ]) !!}
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('date', __('messages.date') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('date', @format_date($production_unit->date), ['class' => 'form-control', 'required', 'readonly', 'id' => 'date']); !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('raw_material', __('production.raw_material') . ':*') !!}
                    {!! Form::number('raw_material', $production_unit->raw_material, ['class' => 'form-control', 'required', 'placeholder' => __('production.raw_material')]); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('product_id', __('production.product') . ':*') !!}
                    {!! Form::select('product_id', $products, $production_unit->product_id, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
            </div>
        </div>
        {!! Form::close() !!}
    @endcomponent
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('#date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
        });
        
        $('.select2').select2();
    });
</script>
@endsection