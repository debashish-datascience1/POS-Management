@extends('layouts.app')

@section('title', __('lang_v1.edit_packing'))

@section('content')
    <section class="content-header">
        <h1>@lang('lang_v1.edit_packing')</h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
            {!! Form::model($packing, [
                'url' => action([\App\Http\Controllers\PackingController::class, 'update'], [$packing->id]),
                'method' => 'put',
                'id' => 'packing_edit_form',
            ]) !!}
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('date', __('messages.date') . ':*') !!}
                        {!! Form::date('date', \Carbon\Carbon::parse($packing->date)->format('Y-m-d'), [
                            'class' => 'form-control',
                            'required',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select(
                            'location_id',
                            $business_locations,
                            null,
                            ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'],
                            $bl_attributes,
                        ) !!}
                    </div>
                </div>
            </div>

            <div class="packing-section">
                @php
                    // Ensure temperatures is an array
                    $temperatures = is_string($packing->temperature)
                        ? json_decode($packing->temperature)
                        : $packing->temperature;
                    $product_temperatures_data = is_string($packing->product_temperature)
                        ? json_decode($packing->product_temperature)
                        : $packing->product_temperature;
                    $quantities = is_string($packing->quantity) ? json_decode($packing->quantity) : $packing->quantity;
                    $mixes = is_string($packing->mix) ? json_decode($packing->mix) : $packing->mix;
                    $totals = is_string($packing->total) ? json_decode($packing->total) : $packing->total;
                    $jars = is_string($packing->jar) ? json_decode($packing->jar) : $packing->jar;
                    $packets = is_string($packing->packet) ? json_decode($packing->packet) : $packing->packet;
                @endphp

                @foreach ($temperatures as $index => $temperature)
                    <div class="packing-row">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('temperatures[]', __('lang_v1.temperature') . ':*') !!}
                                    <select name="temperatures[]" class="form-control temperature-select" required>
                                        <option value="">@lang('messages.please_select')</option>
                                        @foreach ($temperatures_list as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ trim($temperature) == trim($key) ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('quantities[]', __('lang_v1.quantity') . ':') !!}
                                    {!! Form::text('quantities[]', $quantities[$index], [
                                        'class' => 'form-control quantity-input',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('mix[]', __('lang_v1.mix') . ':*') !!}
                                    {!! Form::number('mix[]', $mixes[$index], [
                                        'class' => 'form-control mix-input',
                                        'required',
                                        'min' => 0,
                                        'step' => 'any',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('product_temperature[]', __('lang_v1.product_temperature') . ':*') !!}
                                    {!! Form::select('product_temperature[]', $product_temperatures, 
                                        isset($product_temperatures_data[$index]) ? $product_temperatures_data[$index] : null, 
                                        [
                                            'class' => 'form-control product-temperature-select',
                                            'placeholder' => __('messages.please_select'),
                                            'required',
                                        ]) !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('total[]', __('lang_v1.total_after_mix') . ':') !!}
                                    {!! Form::text('total[]', $totals[$index], [
                                        'class' => 'form-control total-input',
                                        'readonly',
                                        'style' => 'background-color: #eee;',
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-sm-8">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            {!! Form::label('jar[]', __('lang_v1.jar') . ':*') !!}
                                            <div class="jar-container">
                                                @if (isset($jars[$index]))
                                                    @php
                                                        $jarData = is_string($jars[$index])
                                                            ? explode(',', $jars[$index])
                                                            : $jars[$index];
                                                    @endphp
                                                    @foreach ($jarData as $jar)
                                                        @php
                                                            $jarParts = is_string($jar) ? explode(':', $jar) : $jar;
                                                            $size = $jarParts[0] ?? '';
                                                            $quantity = $jarParts[1] ?? '';
                                                            $price = $jarParts[2] ?? '';
                                                        @endphp
                                                        <div class="jar-option mb-2">
                                                            <div class="row">
                                                                <div class="col-sm-4">
                                                                    <label>Jar Size:</label>
                                                                    <select
                                                                        name="jars[{{ $index }}][{{ $loop->index }}][size]"
                                                                        class="form-control jar-size">
                                                                        <option value="5L"
                                                                            {{ $size == '5L' ? 'selected' : '' }}>5L</option>
                                                                        <option value="5L(sp)"
                                                                            {{ $size == '5L(sp)' ? 'selected' : '' }}>5L(sp)</option>
                                                                        <option value="10L"
                                                                            {{ $size == '10L' ? 'selected' : '' }}>10L</option>
                                                                        <option value="10L(sp)"
                                                                            {{ $size == '10L(sp)' ? 'selected' : '' }}>10L(sp)</option>
                                                                        <option value="20L"
                                                                            {{ $size == '20L' ? 'selected' : '' }}>20L</option>
                                                                        <option value="20L(sp)"
                                                                            {{ $size == '20L(sp)' ? 'selected' : '' }}>20L(sp)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-sm-3">
                                                                    <label>Quantity:</label>
                                                                    <input type="number"
                                                                        name="jars[{{ $index }}][{{ $loop->index }}][quantity]"
                                                                        class="form-control jar-quantity" min="1"
                                                                        value="{{ $quantity }}">
                                                                </div>
                                                                <div class="col-sm-3">
                                                                    <label>Price:</label>
                                                                    <input type="number"
                                                                        name="jars[{{ $index }}][{{ $loop->index }}][price]"
                                                                        class="form-control jar-price" min="0"
                                                                        step="0.01" value="{{ $price }}">
                                                                </div>
                                                                <div class="col-sm-2">
                                                                    <label>&nbsp;</label>
                                                                    <button type="button"
                                                                        class="btn btn-danger btn-sm remove-jar"
                                                                        style="margin-top: 25px;">X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 add-jar">Add Jar</button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            {!! Form::label('packet[]', __('lang_v1.packet') . ':*') !!}
                                            <div class="packet-container">
                                                @if (isset($packets[$index]))
                                                    @php
                                                        $packetData = is_string($packets[$index])
                                                            ? explode(',', $packets[$index])
                                                            : $packets[$index];
                                                    @endphp
                                                    @foreach ($packetData as $packet)
                                                        @php
                                                            $packetParts = is_string($packet)
                                                                ? explode(':', $packet)
                                                                : $packet;
                                                            $size = $packetParts[0] ?? '';
                                                            $quantity = $packetParts[1] ?? '';
                                                            $price = $packetParts[2] ?? '';
                                                        @endphp
                                                        <div class="packet-option mb-2">
                                                            <div class="row">
                                                                <div class="col-sm-4">
                                                                    <label>Packet Size:</label>
                                                                    <select
                                                                        name="packets[{{ $index }}][{{ $loop->index }}][size]"
                                                                        class="form-control packet-size">
                                                                        <option value="100ML"
                                                                            {{ $size == '100ML' ? 'selected' : '' }}>100ML</option>
                                                                        <option value="100ML(sp)"
                                                                            {{ $size == '100ML(sp)' ? 'selected' : '' }}>100ML(sp)</option>
                                                                        <option value="200ML"
                                                                            {{ $size == '200ML' ? 'selected' : '' }}>200ML</option>
                                                                        <option value="200ML(sp)"
                                                                            {{ $size == '200ML(sp)' ? 'selected' : '' }}>200ML(sp)</option>
                                                                        <option value="500ML"
                                                                            {{ $size == '500ML' ? 'selected' : '' }}>500ML</option>
                                                                        <option value="500ML(sp)"
                                                                            {{ $size == '500ML(sp)' ? 'selected' : '' }}>500ML(sp)</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-sm-3">
                                                                    <label>Quantity:</label>
                                                                    <input type="number"
                                                                        name="packets[{{ $index }}][{{ $loop->index }}][quantity]"
                                                                        class="form-control packet-quantity" min="1"
                                                                        value="{{ $quantity }}">
                                                                </div>
                                                                <div class="col-sm-3">
                                                                    <label>Price:</label>
                                                                    <input type="number"
                                                                        name="packets[{{ $index }}][{{ $loop->index }}][price]"
                                                                        class="form-control packet-price" min="0"
                                                                        step="0.01" value="{{ $price }}">
                                                                </div>
                                                                <div class="col-sm-2">
                                                                    <label>&nbsp;</label>
                                                                    <button type="button"
                                                                        class="btn btn-danger btn-sm remove-packet"
                                                                        style="margin-top: 25px;">X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 add-packet">Add
                                                Packet</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                @endforeach
            </div>

            <div class="row mt-2 mb-3">
                <div class="col-sm-12">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" id="add_more_section">Add More</button>
                        <button type="button" class="btn btn-danger remove-section"
                            {{ count($temperatures) > 1 ? '' : 'style=display:none;' }}>Remove</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6 offset-sm-6">
                    <div class="form-group">
                        {!! Form::label('grand_total', __('lang_v1.grand_total') . ':') !!}
                        {!! Form::text('grand_total', null, [
                            'class' => 'form-control',
                            'readonly',
                            'id' => 'grand_total',
                            'style' => 'background-color: #eee;',
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
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            const jarOptions = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)'];
            const packetOptions = ['100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

            // Initialize on load
            initializeSelect2();
            calculateGrandTotal();

            function initializeSelect2() {
                $('.temperature-select').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            width: '100%'
                        });
                    }
                });
            }

            function addOption(type, $container, sectionIndex, data = null) {
                const options = type === 'jar' ? jarOptions : packetOptions;
                const optionIndex = $container.find(`.${type}-option`).length;

                let size = '',
                    quantity = 1,
                    price = 0;
                if (data) {
                    [size, quantity, price] = data.split(':');
                }

                const html = `
            <div class="${type}-option mb-2">
                <div class="row">
                    <div class="col-sm-4">
                        <label>${type.charAt(0).toUpperCase() + type.slice(1)} Size:</label>
                        <select name="${type}s[${sectionIndex}][${optionIndex}][size]" class="form-control ${type}-size">
                            ${options.map(option => `<option value="${option}" ${data && size === option ? 'selected' : ''}>${option}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>Quantity:</label>
                        <input type="number" name="${type}s[${sectionIndex}][${optionIndex}][quantity]" class="form-control ${type}-quantity" min="1" value="${quantity}">
                    </div>
                    <div class="col-sm-3">
                        <label>Price:</label>
                        <input type="number" name="${type}s[${sectionIndex}][${optionIndex}][price]" class="form-control ${type}-price" min="0" step="0.01" value="${price}">
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm remove-${type}" style="margin-top: 25px;">X</button>
                    </div>
                </div>
            </div>
        `;
                $container.append(html);
                calculateGrandTotal();
            }

            function calculateTotal($row) {
                const quantity = parseFloat($row.find('.quantity-input').val()) || 0;
                const mix = parseFloat($row.find('.mix-input').val()) || 0;
                const total = quantity + (quantity * mix / 100);
                $row.find('.total-input').val(total.toFixed(2));
                calculateGrandTotal();
            }

            function calculateGrandTotal() {
                let grandTotal = 0;
                $('.packing-row').each(function() {
                    let sectionTotal = 0;
                    $(this).find('.jar-option').each(function() {
                        const quantity = parseFloat($(this).find('.jar-quantity').val()) || 0;
                        const price = parseFloat($(this).find('.jar-price').val()) || 0;
                        sectionTotal += quantity * price;
                    });
                    $(this).find('.packet-option').each(function() {
                        const quantity = parseFloat($(this).find('.packet-quantity').val()) || 0;
                        const price = parseFloat($(this).find('.packet-price').val()) || 0;
                        sectionTotal += quantity * price;
                    });
                    grandTotal += sectionTotal;
                });
                $('#grand_total').val(grandTotal.toFixed(2));
            }

            // Add more section
            $('#add_more_section').click(function() {
                const newSectionIndex = $('.packing-row').length;
                const $newSection = $('.packing-row').first().clone();

                // Reset values and classes
                $newSection.find('input').val('');
                $newSection.find('.select2-container').remove();
                $newSection.find('.temperature-select').removeClass('select2-hidden-accessible').val('');
                $newSection.find('.jar-container, .packet-container').empty();

                // Add initial jar and packet options
                addOption('jar', $newSection.find('.jar-container'), newSectionIndex);
                addOption('packet', $newSection.find('.packet-container'), newSectionIndex);

                $('.packing-section').append($newSection);
                initializeSelect2();

                if ($('.packing-row').length > 1) {
                    $('.remove-section').show();
                }
            });

            // Remove section
            $('.remove-section').click(function() {
                if ($('.packing-row').length > 1) {
                    $('.packing-row').last().remove();
                    if ($('.packing-row').length === 1) {
                        $(this).hide();
                    }
                    calculateGrandTotal();
                }
            });

            // Add jar/packet
            $(document).on('click', '.add-jar', function() {
                const sectionIndex = $(this).closest('.packing-row').index();
                addOption('jar', $(this).siblings('.jar-container'), sectionIndex);
            });

            $(document).on('click', '.add-packet', function() {
                const sectionIndex = $(this).closest('.packing-row').index();
                addOption('packet', $(this).siblings('.packet-container'), sectionIndex);
            });

            // Remove jar/packet
            $(document).on('click', '.remove-jar', function() {
                const $container = $(this).closest('.jar-container');
                $(this).closest('.jar-option').remove();
                // Reindex remaining options
                $container.find('.jar-option').each(function(index) {
                    $(this).find('[name*="jars"]').each(function() {
                        const name = $(this).attr('name');
                        const newName = name.replace(/\[\d+\]\[\d+\]/,
                            `[${$container.closest('.packing-row').index()}][${index}]`);
                        $(this).attr('name', newName);
                    });
                });
                calculateGrandTotal();
            });

            $(document).on('click', '.remove-packet', function() {
                const $container = $(this).closest('.packet-container');
                $(this).closest('.packet-option').remove();
                // Reindex remaining options
                $container.find('.packet-option').each(function(index) {
                    $(this).find('[name*="packets"]').each(function() {
                        const name = $(this).attr('name');
                        const newName = name.replace(/\[\d+\]\[\d+\]/,
                            `[${$container.closest('.packing-row').index()}][${index}]`);
                        $(this).attr('name', newName);
                    });
                });
                calculateGrandTotal();
            });

            // Temperature selection handler
            $(document).on('change', '.temperature-select', function() {
                const $row = $(this).closest('.packing-row');
                const temperature = $(this).val();

                if (temperature) {
                    $.ajax({
                        url: '/packing/get-temperature-quantity',
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            temperature: temperature
                        },
                        success: function(response) {
                            if (response.success) {
                                $row.find('.quantity-input').val(response.data.temp_quantity);
                                calculateTotal($row);
                            } else {
                                toastr.error(response.message);
                                $row.find('.quantity-input').val('');
                                calculateTotal($row);
                            }
                        },
                        error: function() {
                            toastr.error('Error fetching temperature quantity');
                            $row.find('.quantity-input').val('');
                            calculateTotal($row);
                        }
                    });
                } else {
                    $row.find('.quantity-input').val('');
                    calculateTotal($row);
                }
            });

            // Mix input handler
            $(document).on('input', '.mix-input', function() {
                calculateTotal($(this).closest('.packing-row'));
            });

            // Price and quantity change handlers
            $(document).on('input', '.jar-quantity, .jar-price, .packet-quantity, .packet-price', function() {
                calculateGrandTotal();
            });

            // Form validation
            $('#packing_edit_form').on('submit', function(e) {
                let isValid = true;

                $('.temperature-select').each(function() {
                    if (!$(this).val()) {
                        toastr.error('Please select temperature for all sections');
                        isValid = false;
                        return false;
                    }
                });

                $('.product-temperature-select').each(function() {
                    if (!$(this).val()) {
                        toastr.error('Please select product temperature for all sections');
                        isValid = false;
                        return false;
                    }
                });

                $('.packing-row').each(function(index) {
                    const $row = $(this);
                    const hasJars = $row.find('.jar-option').length > 0;
                    const hasPackets = $row.find('.packet-option').length > 0;

                    if (!hasJars && !hasPackets) {
                        toastr.error(`Section ${index + 1}: Please add at least one jar or packet`);
                        isValid = false;
                        return false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endsection
