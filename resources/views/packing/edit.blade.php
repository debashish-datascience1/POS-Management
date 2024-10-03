@extends('layouts.app')

@section('title', __('lang_v1.edit_packing'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.edit_packing')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::model($packing, ['url' => action([\App\Http\Controllers\PackingController::class, 'update'], [$packing->id]), 'method' => 'put', 'id' => 'packing_edit_form' ]) !!}
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('date', __('messages.date') . ':*') !!}
                    {!! Form::date('date', $packing->date, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                    @show_tooltip(__('tooltip.purchase_location'))
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('product_output', __('lang_v1.product_output') . ':*') !!}
                    {!! Form::number('product_output', null, [
                        'class' => 'form-control',
                        'id' => 'product_output',
                        'step' => 'any',
                        'min' => '0',
                        'required',
                        'placeholder' => 'Enter value'
                    ]); !!}                    
                    <span class="help-block" id="packing_stock_info" style="color: #3c8dbc;"></span>
                    <span class="help-block error-msg" id="stock_error" style="color: #dd4b39; display: none;"></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('mix', __('lang_v1.mix') . ':*') !!}
                    {!! Form::number('mix', null, ['class' => 'form-control', 'required', 'min' => 0, 'step' => 'any', 'id' => 'mix']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('total', __('lang_v1.total_after_mix') . ':') !!}
                    {!! Form::number('total', null, ['class' => 'form-control', 'readonly', 'id' => 'total']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('grand_total', __('lang_v1.grand_total') . ':') !!}
                    {!! Form::number('grand_total', null, ['class' => 'form-control', 'readonly', 'id' => 'grand_total']); !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('jar', __('lang_v1.jar') . ':*') !!}
                    <div id="jar_container">
                        <!-- Existing jar options will be loaded here -->
                    </div>
                    <button type="button" class="btn btn-primary mt-2" id="add_jar">Add Jar</button>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('packet', __('lang_v1.packet') . ':*') !!}
                    <div id="packet_container">
                        <!-- Existing packet options will be loaded here -->
                    </div>
                    <button type="button" class="btn btn-primary mt-2" id="add_packet">Add Packet</button>
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
@endsection

@section('javascript')
<script>
    $(document).ready(function(){
        const jarOptions = ['5L', '10L', '20L'];
        const packetOptions = ['100ML', '200ML', '500ML'];
        let jarCount = 0;
        let packetCount = 0;
        let originalStock = 0;
        let currentLocationId = '{{ $packing->location_id }}';
        let originalProductOutput = {{ $packing->product_output ?? 0 }};

        function addOption(type, data = null) {
            let options = type === 'jar' ? jarOptions : packetOptions;
            let count = type === 'jar' ? jarCount++ : packetCount++;
            let html = `
                <div class="${type}-option mb-2">
                    <div style="width: 30%; display: inline-block;">
                        <label>Choose:</label>
                        <select name="${type}[${count}][size]" class="form-control ${type}-size">
                            ${options.map(option => `<option value="${option}" ${data && data.size === option ? 'selected' : ''}>${option}</option>`).join('')}
                        </select>
                    </div>
                    <div style="width: 30%; display: inline-block;">
                        <label>Qty:</label>
                        <input type="number" name="${type}[${count}][quantity]" class="form-control ${type}-quantity" min="1" value="${data ? data.quantity : 1}">
                    </div>
                    <div style="width: 30%; display: inline-block;">
                        <label>Price:</label>
                        <input type="number" name="${type}[${count}][price]" class="form-control ${type}-price" min="0" step="0.01" value="${data ? data.price : 0}">
                    </div>
                    <button type="button" class="btn btn-danger remove-${type}">X</button>
                </div>
            `;
            $(`#${type}_container`).append(html);
            calculateGrandTotal();
        }

        $('#add_jar').click(() => addOption('jar'));
        $('#add_packet').click(() => addOption('packet'));

        $(document).on('click', '.remove-jar, .remove-packet', function() {
            $(this).closest('.jar-option, .packet-option').remove();
            calculateGrandTotal();
        });

        // Load existing jar and packet data
        @if(isset($packing->jar))
            @php
                $jars = explode(',', $packing->jar);
            @endphp
            @foreach($jars as $jar)
                @php
                    list($size, $quantity, $price) = explode(':', $jar);
                @endphp
                addOption('jar', {size: '{{ $size }}', quantity: {{ $quantity }}, price: {{ $price }}});
            @endforeach
        @endif

        @if(isset($packing->packet))
            @php
                $packets = explode(',', $packing->packet);
            @endphp
            @foreach($packets as $packet)
                @php
                    list($size, $quantity, $price) = explode(':', $packet);
                @endphp
                addOption('packet', {size: '{{ $size }}', quantity: {{ $quantity }}, price: {{ $price }}});
            @endforeach
        @endif


        function formatNumber(num) {
            return parseFloat(num).toFixed(2);
        }

        // Update the location change handler
        $('#location_id').change(function(){
            currentLocationId = $(this).val();
            if(currentLocationId) {
                fetchPackingStock(currentLocationId);
            } else {
                $('#product_output').val('');
                $('#packing_stock_info').html('');
                originalStock = 0;
            }
        });

        function fetchPackingStock(locationId) {
            $.ajax({
                url: '/get-packing-stock/' + locationId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        originalStock = parseFloat(response.data.total) + originalProductOutput;
                        updateStockInfo(originalProductOutput);
                    } else {
                        $('#packing_stock_info').html(response.message);
                    }
                },
                error: function() {
                    $('#packing_stock_info').html('Error fetching packing stock information');
                }
            });
        }

        function updateStockInfo(productOutput) {
            let remainingStock = originalStock - productOutput;
            $('#packing_stock_info').html('Remaining Stock: ' + formatNumber(remainingStock));
        }

        // Handle product output changes
        $('#product_output').on('input', function(e) {
            let value = $(this).val();
            
            if (value === '') {
                updateStockInfo(0);
                $('#stock_error').hide();
                $('#total').val('0.00');
                $('#grand_total').val('0.00');
                return;
            }
            
            value = value.replace(/[^\d.]/g, '');
            
            let decimalCount = (value.match(/\./g) || []).length;
            if (decimalCount > 1) {
                value = value.replace(/\.+$/, '');
            }

            $(this).val(value);

            let productOutput = parseFloat(value);
            if (!isNaN(productOutput)) {
                if (productOutput > originalStock) {
                    $('#stock_error').html('Insufficient stock available').show();
                } else {
                    $('#stock_error').hide();
                    updateStockInfo(productOutput);
                }
            }

            calculateTotal();
        });
        
        function validateStock(value) {
            if (value === '') {
                $('#packing_stock_info').html('Packing Stock: ' + originalStock);
                $('#stock_error').hide();
                return;
            }

            $.ajax({
                url: '/validate-packing-stock',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    location_id: currentLocationId,
                    amount: value,
                    packing_id: '{{ $packing->id }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.isValid) {
                            $('#stock_error').hide();
                            $('#packing_stock_info').html('Remaining Stock: ' + response.data.remaining);
                            calculateTotal();
                        } else {
                            $('#stock_error').html('Insufficient stock available').show();
                        }
                    }
                },
                error: function() {
                    $('#stock_error').html('Error validating stock').show();
                }
            });
        }

        $('#packing_edit_form').on('submit', function(e) {
            let productOutput = parseFloat($('#product_output').val());
            if (isNaN(productOutput) || productOutput <= 0 || productOutput > originalStock) {
                e.preventDefault();
                alert('Invalid product output amount');
                return false;
            }
        });

        $('#mix').on('input', function() {
            calculateTotal();
        });

        function calculateTotal() {
            var productOutput = parseFloat($('#product_output').val()) || 0;
            var mix = parseFloat($('#mix').val()) || 0;
            var total = productOutput + (productOutput * mix / 100);
            $('#total').val(total.toFixed(2));
            calculateGrandTotal();
        }

        $(document).on('input', '.jar-quantity, .jar-price, .packet-quantity, .packet-price', function() {
            calculateGrandTotal();
        });

        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.jar-option, .packet-option').each(function() {
                const quantity = parseFloat($(this).find('.jar-quantity, .packet-quantity').val()) || 0;
                const price = parseFloat($(this).find('.jar-price, .packet-price').val()) || 0;
                grandTotal += quantity * price;
            });
            $('#grand_total').val(grandTotal.toFixed(2));
        }

        // Initial calculations and data loading
        fetchPackingStock(currentLocationId);
        calculateTotal();
        calculateGrandTotal();
    });
</script>
@endsection