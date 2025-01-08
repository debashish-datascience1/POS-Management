@extends('layouts.app')
@section('title', __('lang_v1.add_balance'))

@section('content')
    <section class="content-header">
        <h1>@lang('lang_v1.add_balance')</h1>
    </section>

    <section class="content">
        {!! Form::open([
            'url' => action([\App\Http\Controllers\BalanceController::class, 'store']),
            'method' => 'post',
            'id' => 'balance_add_form',
        ]) !!}
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <!-- Date and Location Section -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('date', __('messages.date') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('date', @format_date('now'), [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('messages.date'),
                                        'id' => 'date',
                                        // Remove the 'readonly' => true
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                                {!! Form::select('location_id', $business_locations, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('messages.please_select'),
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Raw Materials Section -->
                    <div id="raw_materials">
                        <div class="raw-material-entry">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('product_id[]', __('lang_v1.raw_materials') . ':*') !!}
                                        {!! Form::select('product_id[]', $products->pluck('name', 'id'), null, [
                                            'class' => 'form-control select2 product_id',
                                            'required',
                                            'placeholder' => __('messages.please_select'),
                                        ]) !!}
                                        <span class="current_stock text-muted"></span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        {!! Form::label('raw_material[]', __('lang_v1.quantity') . ':*') !!}
                                        {!! Form::number('raw_material[]', null, [
                                            'class' => 'form-control raw_material',
                                            'required',
                                            'placeholder' => __('lang_v1.quantity'),
                                            'step' => '0.01',
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="col-md-2" style="display: none;">
                                    <div class="form-group">
                                        {!! Form::label('current_amount[]', __('lang_v1.current_amount') . ':') !!}
                                        {!! Form::text('current_amount[]', '0', ['class' => 'form-control current_amount', 'readonly']) !!}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-material"
                                        style="margin-top: 25px;">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Quantity and Production Output -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('total_quantity', __('lang_v1.total_quantity') . ':') !!}
                                {!! Form::text('total_quantity', '0', ['class' => 'form-control', 'readonly', 'id' => 'total_quantity']) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Add More Raw Materials -->
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" id="add_more" class="btn btn-primary">Add More</button>
                        </div>
                    </div>

                    <!-- Temperature Entries -->
                    <div id="temperature-entries">
                        <div class="temperature-entry row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    {!! Form::label('temperatures[]', __('lang_v1.temperature.temperature') . ':*') !!}
                                    {!! Form::text('temperatures[]', null, [
                                        'class' => 'form-control',
                                        'required',
                                        'placeholder' => __('Enter temperature'),
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    {!! Form::label('quantities[]', __('lang_v1.quantity') . ':*') !!}
                                    {!! Form::number('quantities[]', null, [
                                        'class' => 'form-control quantity-input',
                                        'required',
                                        'placeholder' => __('lang_v1.quantity'),
                                        'step' => 'any',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-entry" style="margin-top: 25px;">
                                    @lang('lang_v1.messages.remove')
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Add More Temperature Entries -->
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-success" id="add-more-entry">
                                <i class="fa fa-plus"></i> @lang('lang_v1.messages.add_more')
                            </button>
                        </div>
                    </div>
                    <!-- Add after the "Add More" button div -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div id="jar-entries">
                                <div class="jar-entry row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            {!! Form::label('jar_sizes[]', __('lang_v1.jar_size') . ':*') !!}
                                            {!! Form::select(
                                                'jar_sizes[]',
                                                [
                                                    '5L' => '5L',
                                                    '5L(sp)' => '5L(sp)',
                                                    '10L' => '10L',
                                                    '10L(sp)' => '10L(sp)',
                                                    '20L' => '20L',
                                                    '20L(sp)' => '20L(sp)',
                                                ],
                                                null,
                                                ['class' => 'form-control jar-size-select', 'required', 'placeholder' => __('messages.please_select')],
                                            ) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            {!! Form::label('jar_quantities', __('lang_v1.quantity') . ':*') !!}
                                            {!! Form::number('jar_quantities', null, [
                                                'class' => 'form-control jar-quantity-input',
                                                '',
                                                'placeholder' => __('lang_v1.quantity'),
                                                'step' => 'any',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <button type="button" class="btn btn-danger remove-jar"
                                            style="margin-top: 25px;">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success" id="add-more-jar">
                                <i class="fa fa-plus"></i> Add More Jar
                            </button>
                        </div>
                    </div>
                    <!-- Packet Section -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div id="packet-entries">
                                <div class="packet-entry row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            {!! Form::label('packet_sizes[]', __('lang_v1.packet_size') . ':*') !!}
                                            {!! Form::select(
                                                'packet_sizes[]',
                                                [
                                                    '100ML' => '100ML',
                                                    '100ML(sp)' => '100ML(sp)',
                                                    '200ML' => '200ML',
                                                    '200ML(sp)' => '200ML(sp)',
                                                    '500ML' => '500ML',
                                                    '500ML(sp)' => '500ML(sp)',
                                                ],
                                                null,
                                                ['class' => 'form-control packet-size-select', 'required', 'placeholder' => __('messages.please_select')],
                                            ) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            {!! Form::label('packet_quantities', __('lang_v1.quantity') . ':*') !!}
                                            {!! Form::number('packet_quantities', null, [
                                                'class' => 'form-control packet-quantity-input',
                                                '',
                                                'placeholder' => __('lang_v1.quantity'),
                                                'step' => 'any',
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-packet"
                                            style="margin-top: 25px;">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success" id="add-more-packet">
                                <i class="fa fa-plus"></i> Add More Packet
                            </button>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <!-------------------------------------------- Submit Button --------------------------------------------->
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary pull-right">@lang('lang_v1.messages.save')</button>
            </div>
        </div>
        {!! Form::close() !!}
    </section>

@endsection

@section('javascript')

    <script>
        $(document).ready(function() {

            // Initialize Select2 for packet size dropdowns
            function initializePacketSelect2() {
                // Destroy any existing Select2 instances first
                $('.packet-size-select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                // Reinitialize Select2
                $('.packet-size-select').select2({
                    width: '100%',
                    dropdownParent: $('body') // This ensures dropdowns appear over other elements
                });
            }

            function initializeJarSelect2() {
                // Destroy any existing Select2 instances first
                $('.jar-size-select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });

                // Reinitialize Select2
                $('.jar-size-select').select2({
                    width: '100%',
                    dropdownParent: $('body') // This ensures dropdowns appear over other elements
                });
            }


            setTimeout(function() {
                initializePacketSelect2();
                initializeJarSelect2();
            }, 100);


            // Initialize Select2 on page load

            // Add more packet entry
            $('#add-more-packet').click(function() {
                const packetEntryTemplate = `
                    <div class="packet-entry row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="packet_sizes[]">Packet Size:*</label>
                                <select name="packet_sizes[]" class="form-control packet-size-select" required>
                                    <option value="">Please select</option>
                                    <option value="100ML">100ML</option>
                                    <option value="100ML(sp)">100ML(sp)</option>
                                    <option value="200ML">200ML</option>
                                    <option value="200ML(sp)">200ML(sp)</option>
                                    <option value="500ML">500ML</option>
                                    <option value="500ML(sp)">500ML(sp)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="packet_quantities[]">Quantity:*</label>
                                <input type="number" name="packet_quantities[]" class="form-control packet-quantity-input" required placeholder="Enter quantity" step="any">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-packet" style="margin-top: 25px;">Remove</button>
                        </div>
                    </div>
                `;

                $('#packet-entries').append(packetEntryTemplate);
                initializePacketSelect2(); // Reinitialize all packet selects
            });


            // Initialize Select2 for jar size dropdowns
            function initializeJarSelect2() {
                $('.jar-size-select').select2({
                    width: '100%'
                });
            }

            // Initialize Select2 on page load
            initializeJarSelect2();

            // Add more jar entry
            $('#add-more-jar').click(function() {
                const jarEntryTemplate = `
                    <div class="jar-entry row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="jar_sizes[]">Jar Size:*</label>
                                <select name="jar_sizes[]" class="form-control jar-size-select" required>
                                    <option value="">Please select</option>
                                    <option value="5L">5L</option>
                                    <option value="5L(sp)">5L(sp)</option>
                                    <option value="10L">10L</option>
                                    <option value="10L(sp)">10L(sp)</option>
                                    <option value="20L">20L</option>
                                    <option value="20L(sp)">20L(sp)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="jar_quantities[]">Quantity:*</label>
                                <input type="number" name="jar_quantities[]" class="form-control jar-quantity-input" required placeholder="Enter quantity" step="any">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-jar" style="margin-top: 25px;">Remove</button>
                        </div>
                    </div>
                `;

                $('#jar-entries').append(jarEntryTemplate);
                initializeJarSelect2(); // Reinitialize all jar selects
            });


            // Remove jar entry
            $(document).on('click', '.remove-packet', function() {
                const entriesCount = $('.packet-entry').length;
                if (entriesCount > 1) {
                    $(this).closest('.packet-entry').remove();
                    initializePacketSelect2(); // Reinitialize remaining selects
                } else {
                    toastr.error('At least one packet entry is required.');
                }
            });

            // Remove jar entry
            $(document).on('click', '.remove-jar', function() {
                const entriesCount = $('.jar-entry').length;
                if (entriesCount > 1) {
                    $(this).closest('.jar-entry').remove();
                    initializeJarSelect2(); // Reinitialize remaining selects
                } else {
                    toastr.error('At least one jar entry is required.');
                }
            });


            // Other existing logic goes here...



            // Add jar validation to your form submission handler
            let productOutputs = {};

            // Function to calculate total quantity from all entries
            function calculateTotalQuantity() {
                let total = 0;
                $('.quantity-input').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                return total;
            }

            function updateQuantityDisplays() {
                const totalQuantity = calculateTotalQuantity();
                const productOutput = parseFloat($('#product_output').val()) || 0;
                const remainingQuantity = productOutput - totalQuantity;

                $('#total_quantity').val(totalQuantity.toFixed(2));

                // Update the display of remaining quantity if you have an element for it
                if ($('#remaining-quantity').length) {
                    $('#remaining-quantity').text(remainingQuantity.toFixed(2));
                    $('#remaining-quantity').css('color', remainingQuantity < 0 ? 'red' : 'inherit');
                }
            }

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

            // Initialize datepicker
            $('#date').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd',
                clearBtn: true,
                todayHighlight: true,
                // Optional: Set a reasonable past date limit (e.g., 10 years ago)
                startDate: new Date(new Date().getFullYear() - 10, 0, 1),
                // Optional: Set a reasonable future date limit (e.g., 5 years ahead)
                endDate: new Date(new Date().getFullYear() + 5, 11, 31)
            });

            // Initialize Select2
            function initializeSelect2(context = $('body')) {
                context.find('.select2').select2({
                    width: '100%'
                });
            }
            initializeSelect2();

            // Function to update stock display
            function updateStockDisplay(element) {
                let productId = $(element).val();
                let stockSpan = $(element).closest('.form-group').find('.current_stock');
                let quantityInput = $(element).closest('.raw-material-entry').find('.raw_material');
                let currentAmountInput = $(element).closest('.raw-material-entry').find('.current_amount');

                if (productId) {
                    $.ajax({
                        url: '/products/get-stock/' + productId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            let currentStock = parseFloat(data.current_stock);
                            stockSpan.data('original-stock', currentStock);
                            stockSpan.data('unit', data.unit);
                            currentAmountInput.val(currentStock.toFixed(2));
                            updateRemainingStock(stockSpan, quantityInput, currentStock, data.unit);
                            validateQuantity(quantityInput, currentStock);
                            updateProductionQty(quantityInput.closest('.raw-material-entry'));
                        },
                        error: function() {
                            toastr.error('Unable to fetch stock information');
                            stockSpan.html('Error fetching stock');
                            currentAmountInput.val('0');
                        }
                    });
                } else {
                    stockSpan.html('');
                    currentAmountInput.val('0');
                }
            }

            function validateQuantity(input, maxStock) {
                let quantity = parseFloat(input.val()) || 0;
                input.removeClass('is-invalid');
                return true;
            }

            function updateRemainingStock(stockSpan, quantityInput, currentStock, unit) {
                let quantity = parseFloat(quantityInput.val()) || 0;
                // Add your remaining stock calculation logic here if needed
            }

            function updateProductionQty(entry) {
                let currentAmount = parseFloat(entry.find('.current_amount').val()) || 0;
                let rawMaterial = parseFloat(entry.find('.raw_material').val()) || 0;
                let productionQty = rawMaterial;

                if (entry.find('.production_qty').length) {
                    entry.find('.production_qty').val(productionQty.toFixed(2));
                }
                calculateTotals();
            }

            function calculateTotals() {
                let totalQuantity = 0;
                let totalProduction = 0;

                $('.raw-material-entry').each(function() {
                    let currentAmount = parseFloat($(this).find('.current_amount').val()) || 0;
                    let rawMaterial = parseFloat($(this).find('.raw_material').val()) || 0;
                    totalQuantity += (currentAmount + rawMaterial);
                    totalProduction += parseFloat($(this).find('.production_qty').val()) || 0;
                });

                $('#total_quantity').val(totalQuantity.toFixed(2));
                if ($('#total_production').length) {
                    $('#total_production').val(totalProduction.toFixed(2));
                }
            }

            // Event Handlers
            $(document).on('input', '.quantity-input', function() {
                updateQuantityDisplays();
            });

            $('#location_id').on('change', function() {
                const locationId = $(this).val();
                updateProductOutput(locationId);
            });

            $(document).on('change', '.product_id', function() {
                updateStockDisplay(this);
            });

            $(document).on('input', '.raw_material, .current_amount', function() {
                let entry = $(this).closest('.raw-material-entry');
                let stockSpan = entry.find('.current_stock');
                let originalStock = stockSpan.data('original-stock');

                if (originalStock !== undefined) {
                    updateRemainingStock(stockSpan, $(this), originalStock, stockSpan.data('unit'));
                    validateQuantity($(this), originalStock);
                    updateProductionQty(entry);
                }
            });

            // Add/Remove Entry Handlers
            $('#add_more').click(function() {
                let newEntry = $('.raw-material-entry:first').clone();
                newEntry.find('input').val('0');
                newEntry.find('select').val('').removeClass('select2-hidden-accessible').next(
                    '.select2-container').remove();
                newEntry.find('.current_stock').html('');
                $('#raw_materials').append(newEntry);
                initializeSelect2(newEntry);
            });

            $('#add-more-entry').click(function() {
                const $newEntry = $(getEntryTemplate());
                $('#temperature-entries').append($newEntry);
            });

            $(document).on('click', '.remove-material', function() {
                if ($('.raw-material-entry').length > 1) {
                    $(this).closest('.raw-material-entry').remove();
                    calculateTotals();
                }
            });

            $(document).on('click', '.remove-entry', function() {
                const entriesCount = $('.temperature-entry').length;
                if (entriesCount > 1) {
                    $(this).closest('.temperature-entry').remove();
                    updateQuantityDisplays();
                } else {
                    toastr.error('At least one temperature entry is required.');
                }
            });

            function getEntryTemplate() {
                return `
                            <div class="temperature-entry row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="temperatures[]">Temperature:*</label>
                                        <input type="text" name="temperatures[]" class="form-control" required placeholder="Enter temperature">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="quantities[]">Quantity:*</label>
                                        <input type="number" name="quantities[]" class="form-control quantity-input" required placeholder="Enter quantity" step="any">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-entry" style="margin-top: 25px;">Remove</button>
                                </div>
                            </div>
                            `;
            }

            // Form submission handler
            $('#balance_add_form').on('submit', function(e) {
                e.preventDefault();

                // Validate and reindex all quantities before submission
                let isValid = true;

                // Validate jar entries
                $('.jar-entry').each(function(index) {
                    const jarSize = $(this).find('.jar-size-select').val();
                    const jarQuantity = $(this).find('.jar-quantity-input').val();

                    // Reindex the quantity input
                    $(this).find('.jar-quantity-input').attr('name', `jar_quantities[${index}]`);

                    if (!jarSize || !jarQuantity) {
                        toastr.error('Please fill in all jar size and quantity fields');
                        isValid = false;
                        return false;
                    }
                });

                // Validate packet entries
                $('.packet-entry').each(function(index) {
                    const packetSize = $(this).find('.packet-size-select').val();
                    const packetQuantity = $(this).find('.packet-quantity-input').val();

                    // Reindex the quantity input
                    $(this).find('.packet-quantity-input').attr('name',
                        `packet_quantities[${index}]`);

                    if (!packetSize || !packetQuantity) {
                        toastr.error('Please fill in all packet size and quantity fields');
                        isValid = false;
                        return false;
                    }
                });

                if (!isValid) {
                    return false;
                }

                let formData = $(this).serialize();

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            window.location.href =
                                '{{ action([\App\Http\Controllers\BalanceController::class, 'index']) }}'; // Update with your actual URL
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            toastr.error(xhr.responseJSON.msg);
                        } else {
                            toastr.error('An error occurred while processing your request.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
