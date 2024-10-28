@extends('layouts.app')
@section('title', __('lang_v1.edit_product'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.edit_product')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductionController::class, 'update'], [$production_unit->id]), 'method' => 'PUT', 'id' => 'production_edit_form' ]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date', __('messages.date') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('date', @format_date($production_unit->date), ['class' => 'form-control', 'required', 'placeholder' => __('messages.date'), 'id' => 'date']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                            @show_tooltip(__('tooltip.purchase_location'))
                            {!! Form::select('location_id', $business_locations, $production_unit->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('liquor', __('lang_v1.liquor') . ':*') !!}
                            {!! Form::text('liquor', $production_unit->name, ['class' => 'form-control', 'required', 'readonly' => 'readonly', 'placeholder' => __('lang_v1.liquor')]); !!}
                        </div>
                    </div>
                </div>

                <div id="raw_materials">
                    @foreach($production_unit->product_id as $key => $product_id)
                        <div class="raw-material-entry">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('product_id[]', __('lang_v1.raw_material') . ':*') !!}
                                        {!! Form::select('product_id[]', $products, $product_id, ['class' => 'form-control select2 product_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
                                        <span class="current_stock text-muted" data-original-stock="{{ $product_stocks[$product_id] ?? 0 }}">
                                            Current Stock: {{ $product_stocks[$product_id] ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('raw_material[]', __('lang_v1.quantity') . ':*') !!}
                                        {!! Form::number('raw_material[]', $production_unit->raw_material[$key], ['class' => 'form-control raw_material', 'required', 'placeholder' => __('lang_v1.raw_material')]); !!}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    @if($key > 0)
                                        <button type="button" class="btn btn-danger remove-material" style="margin-top: 25px;">Remove</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" id="add_more" class="btn btn-primary">Add More</button>
                    </div>
                </div>

                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('total_quantity', __('lang_v1.total_quantity') . ':') !!}
                            {!! Form::text('total_quantity', $production_unit->total_quantity, ['class' => 'form-control', 'readonly', 'id' => 'total_quantity']); !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
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

    initializeSelect2();

    function initializeSelect2(context = $('body')) {
        context.find('.select2').select2({
            width: '100%'
        });
    }

    function updateStockDisplay(element) {
        let productId = $(element).val();
        let stockSpan = $(element).closest('.form-group').find('.current_stock');
        let quantityInput = $(element).closest('.raw-material-entry').find('.raw_material');
        
        if (productId) {
            $.ajax({
                url: '/get-product-stock/' + productId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    let currentStock = parseFloat(data.current_stock);
                    stockSpan.data('original-stock', currentStock);
                    updateRemainingStock(stockSpan, quantityInput, currentStock, data.unit);
                },
                error: function() {
                    stockSpan.html('Unable to fetch stock information');
                }
            });
        } else {
            stockSpan.html('');
        }
    }

    function updateRemainingStock(stockSpan, quantityInput, currentStock, unit) {
        let quantity = parseFloat(quantityInput.val()) || 0;
        let originalStock = parseFloat(stockSpan.data('original-stock')) || currentStock;
        let remainingStock = originalStock - quantity;
        stockSpan.html('Current Stock: ' + remainingStock.toFixed(2) + ' ' + (unit || ''));

        if (remainingStock < 0) {
            stockSpan.addClass('text-danger');
        } else {
            stockSpan.removeClass('text-danger');
        }
    }

    function calculateTotal() {
        let total = 0;
        $('.raw_material').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_quantity').val(total.toFixed(2));
    }

    $(document).on('change', '.product_id', function() {
        updateStockDisplay(this);
    });

    $(document).on('input', '.raw_material', function() {
        let stockSpan = $(this).closest('.raw-material-entry').find('.current_stock');
        let originalStock = parseFloat(stockSpan.data('original-stock')) || 0;
        updateRemainingStock(stockSpan, $(this), originalStock, '');
        calculateTotal();
    });

    $('#add_more').click(function() {
        let newEntry = $('.raw-material-entry:first').clone();
        newEntry.find('input').val('');
        newEntry.find('select').val('').removeClass('select2-hidden-accessible').next('.select2-container').remove();
        newEntry.find('.current_stock').html('').removeData('original-stock');
        newEntry.find('.col-md-4:last').html('<button type="button" class="btn btn-danger remove-material" style="margin-top: 25px;">Remove</button>');
        $('#raw_materials').append(newEntry);
        initializeSelect2(newEntry);
    });

    $(document).on('click', '.remove-material', function() {
        $(this).closest('.raw-material-entry').remove();
        calculateTotal();
    });

    $('#production_edit_form').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    alert('Production unit updated successfully and stock updated.');
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

    // Initialize stock display for existing entries
    $('.raw_material').each(function() {
        let stockSpan = $(this).closest('.raw-material-entry').find('.current_stock');
        let originalStock = parseFloat(stockSpan.data('original-stock')) || 0;
        updateRemainingStock(stockSpan, $(this), originalStock, '');
    });

    // Calculate initial total
    calculateTotal();
});
</script>
@endsection