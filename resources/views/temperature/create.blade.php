@extends('layouts.app')
@section('title', __('temperature.add_temperature'))

@section('content')
<section class="content-header">
    <h1>@lang('temperature.add_temperature')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\TemperatureController::class, 'store']), 'method' => 'post', 'id' => 'temperature_add_form' ]) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('date', __('messages.date') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('date', @format_date('now'), ['class' => 'form-control', 'required', 'placeholder' => __('messages.date'), 'id' => 'date', 'readonly']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                            @show_tooltip(__('tooltip.purchase_location'))
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'location_id'], $bl_attributes); !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('product_output', __('Product Output') . ' (' . $sevenDaysAgo . '):*') !!}
                            {!! Form::text('product_output', '', ['class' => 'form-control', 'readonly', 'id' => 'product_output']); !!}
                            {!! Form::hidden('packing_stock_id', '', ['id' => 'packing_stock_id']); !!}
                        </div>
                    </div>
                </div>

                <div id="temperature-entries">
                    <div class="temperature-entry row">
                        <div class="col-md-5">
                            <div class="form-group">
                                {!! Form::label('temperatures[]', __('lang_v1.temperature') . ':*') !!}
                                {!! Form::select('temperatures[]', $temperatures, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                {!! Form::label('quantities[]', __('lang_v1.quantity') . ':*') !!}
                                {!! Form::number('quantities[]', null, ['class' => 'form-control quantity-input', 'required', 'placeholder' => __('lang_v1.quantity'), 'step' => 'any']); !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-entry" style="margin-top: 25px;">
                                @lang('messages.remove')
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" id="add-more-entry">
                            <i class="fa fa-plus"></i> @lang('messages.add_more')
                        </button>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>@lang('messages.total_quantity'): </strong>
                            <span id="total-quantity">0</span>
                            <br>
                            <strong>@lang('messages.remaining_quantity'): </strong>
                            <span id="remaining-quantity">0</span>
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
    // Initialize datepicker with proper configuration
    $('#date').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd',
        startDate: new Date(2024, 0, 1),
        endDate: new Date()
    });

    // Initialize Select2
    initializeSelect2();

    // Store product outputs data
    let productOutputs = @json($productOutputs);

    // Function to initialize Select2 on new elements
    function initializeSelect2() {
        $('.select2').select2();
    }

    // Function to format date as YYYY-MM-DD
    function formatDate(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Clone template for new entries
    function getEntryTemplate() {
        return `
            <div class="temperature-entry row">
                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('temperatures[]', __('temperature.temperature') . ':*') !!}
                        {!! Form::select('temperatures[]', $temperatures, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        {!! Form::label('quantities[]', __('lang_v1.quantity') . ':*') !!}
                        {!! Form::number('quantities[]', null, ['class' => 'form-control quantity-input', 'required', 'placeholder' => __('lang_v1.quantity'), 'step' => 'any']); !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-entry" style="margin-top: 25px;">
                        @lang('messages.remove')
                    </button>
                </div>
            </div>
        `;
    }

    // Function to calculate total quantity
    function calculateTotalQuantity() {
        let total = 0;
        $('.quantity-input').each(function() {
            const value = parseFloat($(this).val()) || 0;
            total += value;
        });
        return total;
    }

    // Function to update quantity displays
    function updateQuantityDisplays() {
        const totalQuantity = calculateTotalQuantity();
        const productOutput = parseFloat($('#product_output').val()) || 0;
        const remainingQuantity = productOutput - totalQuantity;

        $('#total-quantity').text(totalQuantity.toFixed(2));
        $('#remaining-quantity').text(remainingQuantity.toFixed(2));

        // Highlight remaining quantity in red if negative
        if (remainingQuantity < 0) {
            $('#remaining-quantity').css('color', 'red');
        } else {
            $('#remaining-quantity').css('color', 'inherit');
        }
    }

    // Function to update product output value based on location
    function updateProductOutput(locationId) {
        const $productOutput = $('#product_output');
        const $packingStockId = $('#packing_stock_id');
        
        if (locationId && productOutputs[locationId]) {
            const output = productOutputs[locationId];
            $productOutput.val(output.stock);
            $packingStockId.val(output.id);
            updateQuantityDisplays();
        } else {
            $productOutput.val('');
            $packingStockId.val('');
            updateQuantityDisplays();
        }
    }

    // Add more entries
    $('#add-more-entry').on('click', function() {
        const $newEntry = $(getEntryTemplate());
        $('#temperature-entries').append($newEntry);
        initializeSelect2();
    });

    // Remove entry
    $(document).on('click', '.remove-entry', function() {
        const entriesCount = $('.temperature-entry').length;
        if (entriesCount > 1) {
            $(this).closest('.temperature-entry').remove();
            updateQuantityDisplays();
        } else {
            toastr.error('At least one temperature entry is required.');
        }
    });

    // Monitor quantity changes
    $(document).on('input', '.quantity-input', function() {
        updateQuantityDisplays();
    });

    // Update output when location changes
    $('#location_id').on('change', function() {
        const locationId = $(this).val();
        updateProductOutput(locationId);
    });

    // When date changes, recalculate the 7 days ago value
    // Update this part in your existing JavaScript
    $('#date').on('change', function() {
        const selectedDate = $(this).val();
        
        // Reset location and output fields
        $('#location_id').val('').trigger('change');
        $('#product_output').val('');
        $('#packing_stock_id').val('');
        
        // Show loading indicator
        const loadingHtml = '<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>';
        $('.box').append(loadingHtml);
        
        $.ajax({
            url: '{{ route("temperature.getProductOutputs") }}',
            type: 'GET',
            data: { date: selectedDate },
            success: function(response) {
                if(response.success) {
                    productOutputs = response.data;
                    
                    // Update the label to show the correct 7-days-ago date
                    const sevenDaysAgo = new Date(selectedDate);
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                    const formattedDate = formatDate(sevenDaysAgo);
                    $('label[for="product_output"]').html(`Product Output (${formattedDate}):*`);
                    
                    console.log('Product outputs loaded:', response.data);
                } else {
                    console.error('Failed to fetch product outputs:', response);
                    toastr.error(response.msg || 'Failed to fetch product outputs');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', { xhr, status, error });
                const errorMsg = xhr.responseJSON?.msg || 'An error occurred while fetching product outputs';
                toastr.error(errorMsg);
            },
            complete: function() {
                // Remove loading indicator
                $('.box .overlay').remove();
            }
        });
    });

    // Form submission handler
    $('#temperature_add_form').on('submit', function(e) {
        e.preventDefault();

        // Check if total quantity exceeds product output
        const totalQuantity = calculateTotalQuantity();
        const productOutput = parseFloat($('#product_output').val()) || 0;

        if (totalQuantity > productOutput) {
            toastr.error('Total quantity cannot exceed product output value');
            return false;
        }

        // Show loading indicator
        const loadingHtml = '<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>';
        $('.box').append(loadingHtml);

        let formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(response) {
                if(response.success) {
                    toastr.success(response.msg);
                    window.location.href = '{{ route("temperature.index") }}';
                } else {
                    toastr.error(response.msg || 'An error occurred while saving the data');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.msg || 'An error occurred while processing your request.';
                toastr.error(errorMsg);
            },
            complete: function() {
                // Remove loading indicator
                $('.box .overlay').remove();
            }
        });
    });
});
</script>
@endsection