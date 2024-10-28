@extends('layouts.app')
@section('title', __('lang_v1.add_product'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_product')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductionController::class, 'store']), 'method' => 'post', 'id' => 'production_add_form' ]) !!}
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
                                {!! Form::text('date', @format_date('now'), ['class' => 'form-control', 'required', 'placeholder' => __('messages.date'), 'id' => 'date']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                            @show_tooltip(__('tooltip.purchase_location'))
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('liquor', __('lang_v1.liquor') . ':*') !!}
                            {!! Form::text('liquor', 'liquor', ['class' => 'form-control', 'required', 'readonly' => 'readonly', 'style' => 'background-color: #eee;']); !!}
                        </div>
                    </div>
                </div>

                <div id="raw_materials">
                    <div class="raw-material-entry">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('product_id[]', __('lang_v1.raw_materials') . ':*') !!}
                                    {!! Form::select('product_id[]', $products->pluck('name', 'id'), null, ['class' => 'form-control select2 product_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
                                    <span class="current_stock text-muted"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('raw_material[]', __('lang_v1.quantity') . ':*') !!}
                                    {!! Form::number('raw_material[]', null, ['class' => 'form-control raw_material', 'required', 'placeholder' => __('lang_v1.raw_materials')]); !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-danger remove-material" style="margin-top: 25px;">Remove</button>
                            </div>
                        </div>
                    </div>
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
                            {!! Form::text('total_quantity', '0', ['class' => 'form-control', 'readonly', 'id' => 'total_quantity']); !!}
                        </div>
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
    // Initialize datepicker
    $('#date').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
    });

    // Initialize Select2 for existing elements
    initializeSelect2();

    // Function to initialize Select2
    function initializeSelect2(context = $('body')) {
        context.find('.select2').select2({
            width: '100%'
        });
    }

    // Function to update stock display
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

    // Function to update remaining stock
    function updateRemainingStock(stockSpan, quantityInput, currentStock, unit) {
        let quantity = parseFloat(quantityInput.val()) || 0;
        let remainingStock = currentStock - quantity;
        stockSpan.html('Current Stock: ' + remainingStock.toFixed(2) + ' ' + unit);

        // Optionally, add visual feedback
        if (remainingStock < 0) {
            stockSpan.addClass('text-danger');
        } else {
            stockSpan.removeClass('text-danger');
        }
    }

    // Function to calculate total quantity
    function calculateTotal() {
        let total = 0;
        $('.raw_material').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_quantity').val(total.toFixed(2));
    }

    // Event handler for product selection
    $(document).on('change', '.product_id', function() {
        updateStockDisplay(this);
    });

    // Event handler for quantity input
    $(document).on('input', '.raw_material', function() {
        let stockSpan = $(this).closest('.raw-material-entry').find('.current_stock');
        let originalStock = stockSpan.data('original-stock');
        if (originalStock !== undefined) {
            updateRemainingStock(stockSpan, $(this), originalStock, stockSpan.text().split(' ').pop());
        }
        calculateTotal();
    });

    // Event handler to add more raw materials
    $('#add_more').click(function() {
        let newEntry = $('.raw-material-entry:first').clone();
        newEntry.find('input').val('');
        newEntry.find('select').val('').removeClass('select2-hidden-accessible').next('.select2-container').remove();
        newEntry.find('.current_stock').html('').removeData('original-stock');
        $('#raw_materials').append(newEntry);
        initializeSelect2(newEntry);
    });

    // Event handler to remove raw materials
    $(document).on('click', '.remove-material', function() {
        if ($('.raw-material-entry').length > 1) {
            $(this).closest('.raw-material-entry').remove();
            calculateTotal();
        }
    });

    // Form submission handler
    $('#production_add_form').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    alert('Production added successfully and stock updated.');
                    window.location.href = '{{ action([\App\Http\Controllers\ProductionController::class, 'index']) }}'; // Update this URL if needed
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