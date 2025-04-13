@extends('layouts.app')
@section('title', 'Edit Final Product Sale')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Final Product Sale</h3>
                    </div>
                    <div class="box-body">
                        {!! Form::open([
                            'url' => action([\App\Http\Controllers\FinalProductSellController::class, 'update'], [$final_product_sell->id]),
                            'method' => 'PUT',
                            'id' => 'final_product_sell_form',
                        ]) !!}

                        <div class="row">
                            <!-- Date Field -->
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('date', __('messages.date') . ':*') !!}
                                    {!! Form::date('date', $final_product_sell->date, ['class' => 'form-control', 'required']) !!}
                                </div>
                            </div>

                            <!-- Business Location -->
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                                    @show_tooltip(__('tooltip.purchase_location'))
                                    {!! Form::select(
                                        'location_id',
                                        $business_locations,
                                        $final_product_sell->location_id,
                                        ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'],
                                        $bl_attributes,
                                    ) !!}
                                </div>
                            </div>

                            <!-- Customer Selection -->
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('contact_id', __('contact.customer') . ':*') !!}
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        {!! Form::select(
                                            'contact_id',
                                            [$final_product_sell->contact_id => $final_product_sell->contact->name],
                                            $final_product_sell->contact_id,
                                            ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'required'],
                                        ) !!}
                                        <span class="input-group-btn">
                                            <button type="button"
                                                class="btn btn-default bg-white btn-flat add_new_customer">
                                                <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product rows container -->
                        <div id="product_rows">
                            @foreach ($final_product_sell->sell_lines as $index => $line)
                                <div class="product-row" data-row="{{ $index }}">
                                    <div class="row">
                                        <!-- Product Temperature -->
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                {!! Form::label('product_temperature[]', __('lang_v1.product_temperature') . ':*') !!}
                                                <div>
                                                    {!! Form::select('product_temperature[]', $product_temperatures, $line->product_temperature, [
                                                        'class' => 'form-control select2 product-temperature',
                                                        'required',
                                                    ]) !!}
                                                    <small class="available-qty-text text-muted"></small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                {!! Form::label('quantity[]', __('lang_v1.quantity') . ':*') !!}
                                                {!! Form::number('quantity[]', $line->quantity, [
                                                    'class' => 'form-control quantity-input',
                                                    'required',
                                                    'min' => '0.01',
                                                    'step' => '0.01',
                                                ]) !!}
                                            </div>
                                        </div>

                                        <!-- Amount -->
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                {!! Form::label('amount[]', __('lang_v1.amount') . ':*') !!}
                                                {!! Form::number('amount[]', $line->amount, [
                                                    'class' => 'form-control amount-input',
                                                    'required',
                                                    'min' => '0.01',
                                                    'step' => '0.01',
                                                ]) !!}
                                            </div>
                                        </div>

                                        @if (!$loop->first)
                                            <!-- Remove Button -->
                                            <div class="col-sm-12">
                                                <button type="button" class="btn btn-danger remove-row"
                                                    style="margin-top: 10px;">
                                                    <i class="fa fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add More Button -->
                        <div class="row">
                            <div class="col-sm-12">
                                <button type="button" class="btn btn-success" id="add_more_btn">
                                    <i class="fa fa-plus"></i> Add More
                                </button>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="row" style="margin-top: 20px;">
                            <div class="col-sm-offset-8 col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('grand_total', __('lang_v1.grand_total') . ':') !!}
                                    {!! Form::text('grand_total', $final_product_sell->grand_total, [
                                        'class' => 'form-control',
                                        'readonly',
                                        'id' => 'grand_total',
                                    ]) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12">
                                <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
                            </div>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact add modal --}}
        <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
            @include('contact.create', ['quick_add' => true])
        </div>
    </section>
@endsection

@section('javascript')
    <script>
        // Make product temperatures available to JavaScript
        const product_temperatures = @json($product_temperatures);
        const translations = {
            product_temperature: '@lang('lang_v1.product_temperature')',
            please_select: '@lang('messages.please_select')',
            quantity: '@lang('lang_v1.quantity')',
            amount: '@lang('lang_v1.amount')'
        };

        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();

            // Initialize customer field
            initializeCustomerSelect();

            // Store available quantities for each temperature
            let availableQuantities = {};

            // Fetch initial available quantities
            $('.product-temperature').each(function() {
                const temperature = $(this).val();
                const $row = $(this).closest('.product-row');
                if (temperature) {
                    fetchAvailableQuantity(temperature, $row);
                }
            });

            // Handle product temperature change
            $(document).on('change', '.product-temperature', function() {
                const $row = $(this).closest('.product-row');
                const temperature = $(this).val();

                if (temperature) {
                    fetchAvailableQuantity(temperature, $row);
                } else {
                    $row.find('.available-qty-text').text('');
                }
            });

            // Add this at the start of your document ready function to store initial quantities
            let initialQuantities = {};

            // When page loads, store the initial quantities for each row
            $('.product-row').each(function() {
                const $row = $(this);
                const rowIndex = $row.data('row');
                initialQuantities[rowIndex] = parseFloat($row.find('.quantity-input').val()) || 0;
            });

            function fetchAvailableQuantity(temperature, $row) {
                const $quantityText = $row.find('.available-qty-text');
                const rowIndex = $row.data('row');
                const currentQuantity = parseFloat($row.find('.quantity-input').val()) || 0;

                $.ajax({
                    url: '/packing/get-temperature-quantity',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        temperature: temperature
                    },
                    success: function(response) {
                        if (response.success) {
                            const responseQty = parseFloat(response.data.temp_quantity) || 0;
                            // For initial load, just show the available quantity
                            const totalStock = responseQty + initialQuantities[rowIndex];
                            availableQuantities[temperature] = totalStock;
                            $quantityText.text('Available: ' + responseQty.toFixed(2));
                        } else {
                            toastr.error(response.message);
                            $quantityText.text('');
                        }
                    },
                    error: function() {
                        toastr.error('Error fetching temperature quantity');
                        $quantityText.text('');
                    }
                });
            }

            // Modify the quantity input handler
            $(document).on('input', '.quantity-input', function() {
                const $row = $(this).closest('.product-row');
                const temperature = $row.find('.product-temperature').val();
                const totalStock = availableQuantities[temperature] || 0;
                const enteredQty = parseFloat($(this).val()) || 0;

                if (enteredQty > totalStock) {
                    toastr.error('Quantity cannot exceed available quantity');
                    $(this).val(totalStock.toFixed(2));
                }

                // Calculate remaining based on total stock minus entered quantity
                const remainingQty = (totalStock - enteredQty).toFixed(2);
                $row.find('.available-qty-text').text('Available: ' + remainingQty);

                calculateGrandTotal();
            });

            // Calculate grand total when quantity or amount changes
            $(document).on('input', '.quantity-input, .amount-input', function() {
                calculateGrandTotal();
            });

            // Calculate grand total function
            function calculateGrandTotal() {
                let total = 0;
                $('.product-row').each(function() {
                    const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                    const amount = parseFloat($(this).find('.amount-input').val()) || 0;
                    const rowTotal = quantity * amount;
                    total += rowTotal;
                });
                $('#grand_total').val(total.toFixed(2));
            }

            // Add more button handler
            // Add more button handler
            let rowCount = 0;
            $('#add_more_btn').click(function() {
                rowCount = $('.product-row').length; // Update rowCount based on existing rows
                const newRow = $(`
        <div class="product-row" data-row="${rowCount}">
            <div class="row">
                <!-- Product Temperature -->
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="product_temperature_${rowCount}">${translations.product_temperature}:*</label>
                        <div>
                            <select name="product_temperature[]" class="form-control select2 product-temperature" required>
                                <option value="">${translations.please_select}</option>
                                ${generateTemperatureOptions()}
                            </select>
                            <small class="available-qty-text text-muted"></small>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="quantity_${rowCount}">${translations.quantity}:*</label>
                        <input type="number" name="quantity[]" class="form-control quantity-input" required min="0.01" step="0.01">
                    </div>
                </div>

                <!-- Amount -->
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="amount_${rowCount}">${translations.amount}:*</label>
                        <input type="number" name="amount[]" class="form-control amount-input" required min="0.01" step="0.01">
                    </div>
                </div>

                <!-- Remove Button -->
                <div class="col-sm-12">
                    <button type="button" class="btn btn-danger remove-row" style="margin-top: 10px;">
                        <i class="fa fa-trash"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    `);

                $('#product_rows').append(newRow);

                // Initialize Select2 on the new row
                newRow.find('.select2').select2({
                    width: '100%'
                });

                // Set initial quantity as 0 for new rows
                initialQuantities[rowCount] = 0;

                // Recalculate grand total after adding new row
                calculateGrandTotal();
            });

            // Helper function to generate temperature options
            function generateTemperatureOptions() {
                let options = '';
                if (product_temperatures) {
                    Object.entries(product_temperatures).forEach(([value, label]) => {
                        options += `<option value="${value}">${label}</option>`;
                    });
                }
                return options;
            }
            // Remove row handler
            $(document).on('click', '.remove-row', function() {
                $(this).closest('.product-row').remove();
                calculateGrandTotal();
            });

            // Form validation
            $('#final_product_sell_form').on('submit', function(e) {
                let isValid = true;

                $('.product-row').each(function() {
                    const $row = $(this);
                    const temperature = $row.find('.product-temperature').val();
                    const enteredQty = parseFloat($row.find('.quantity-input').val()) || 0;
                    const amount = parseFloat($row.find('.amount-input').val()) || 0;
                    const availableQty = availableQuantities[temperature] || 0;

                    if (enteredQty > availableQty) {
                        toastr.error('Quantity cannot exceed available quantity');
                        isValid = false;
                    }

                    if (enteredQty <= 0 || amount <= 0) {
                        toastr.error('Please enter valid quantity and amount for all rows');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Recalculate grand total before submission
                calculateGrandTotal();
            });

            // Initialize customer select function
            function initializeCustomerSelect() {
                $('#customer_id').select2({
                    ajax: {
                        url: '/contacts/customers',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        }
                    },
                    minimumInputLength: 1,
                    language: {
                        noResults: function() {
                            return 'No customers found';
                        },
                    },
                    escapeMarkup: function(markup) {
                        return markup;
                    }
                });
            }

            // Quick add customer functionality
            $(document).on('click', '.add_new_customer', function() {
                $('.contact_modal')
                    .find('select#contact_type')
                    .val('customer')
                    .closest('div.contact_type_div')
                    .addClass('hide');
                $('.contact_modal').modal('show');
            });

            // Customer form validation and submission
            $('form#quick_add_contact')
                .submit(function(e) {
                    e.preventDefault();
                })
                .validate({
                    rules: {
                        contact_id: {
                            remote: {
                                url: '/contacts/check-contacts-id',
                                type: 'post',
                                data: {
                                    contact_id: function() {
                                        return $('#contact_id').val();
                                    },
                                    hidden_id: function() {
                                        if ($('#hidden_id').length) {
                                            return $('#hidden_id').val();
                                        } else {
                                            return '';
                                        }
                                    },
                                },
                            },
                        },
                    },
                    messages: {
                        contact_id: {
                            remote: 'Contact ID already exists',
                        },
                    },
                    submitHandler: function(form) {
                        $(form)
                            .find('button[type="submit"]')
                            .attr('disabled', true);
                        var data = $(form).serialize();
                        $.ajax({
                            method: 'POST',
                            url: $(form).attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    $('select#customer_id').append(
                                        $('<option>', {
                                            value: result.data.id,
                                            text: result.data.name
                                        })
                                    );
                                    $('select#customer_id')
                                        .val(result.data.id)
                                        .trigger('change');
                                    $('div.contact_modal').modal('hide');
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    },
                });
        });
    </script>
@endsection
