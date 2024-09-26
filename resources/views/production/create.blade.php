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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_id', __('lang_v1.raw_material') . ':*') !!}
                        {!! Form::select('product_id', $products->pluck('name', 'id'), null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select'), 'id' => 'product_id']); !!}
                        <span id="current_stock" class="text-muted"></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('raw_material', __('lang_v1.quantity') . ':*') !!}
                        {!! Form::number('raw_material', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.raw_material'), 'id' => 'raw_material']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
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

        let currentStock = 0;
        let productUnit = '';
        let productId = null;

        $('#product_id').on('change', function() {
            productId = $(this).val();
            if (productId) {
                $.ajax({
                    url: '/get-product-stock/' + productId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        currentStock = parseFloat(data.current_stock);
                        productUnit = data.unit;
                        updateStockDisplay();
                    },
                    error: function() {
                        $('#current_stock').html('Unable to fetch stock information');
                    }
                });
            } else {
                $('#current_stock').html('');
            }
        });

        $('#raw_material').on('input', function() {
            updateStockDisplay();
        });

        function updateStockDisplay() {
            let quantity = parseFloat($('#raw_material').val()) || 0;
            let remainingStock = Math.max(currentStock - quantity, 0);
            $('#current_stock').html('Current Stock: ' + remainingStock.toFixed(2) + ' ' + productUnit);
        }

        $('#production_add_form').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let quantity = parseFloat($('#raw_material').val()) || 0;
            let remainingStock = Math.max(currentStock - quantity, 0);
            
            formData += '&updated_stock=' + remainingStock + '&product_id=' + productId;

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                success: function(response) {
                    if(response.success) {
                        alert('Production added successfully and stock updated.');
                        window.location.href = '{{ action([\App\Http\Controllers\ProductionController::class, 'index']) }}';
                    } else {
                        alert('Error: ' + response.msg);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        });
    });
</script>
@endsection