@extends('layouts.app')

@section('title', __('lang_v1.add_packing'))

@section('content')
    <section class="content-header">
        <h1>@lang('lang_v1.add_packing')</h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
            {!! Form::open([
                'url' => action([\App\Http\Controllers\FinalProductController::class, 'store']),
                'method' => 'post',
                'id' => 'packing_form',
            ]) !!}
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('date', __('messages.date') . ':*') !!}
                        {!! Form::date('date', \Carbon\Carbon::now(), ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        @show_tooltip(__('tooltip.purchase_location'))
                        {!! Form::select(
                            'location_id',
                            $business_locations,
                            $default_location,
                            ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'],
                            $bl_attributes,
                        ) !!}
                    </div>
                </div>
            </div>

            <div class="packing-section">
                <div class="packing-row">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('temperatures[]', __('lang_v1.temperature') . ':*') !!}
                                        <select name="temperatures[]" class="form-control temperature-select" required>
                                            <option value="">@lang('messages.please_select')</option>
                                            @foreach ($temperatures as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('quantity[]', __('lang_v1.quantity') . ':') !!}
                                        {!! Form::text('quantity[]', null, [
                                            'class' => 'form-control quantity-input',
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('mix[]', __('lang_v1.mix') . ':*') !!}
                                        {!! Form::number('mix[]', null, ['class' => 'form-control mix-input', 'required', 'min' => 0, 'step' => 'any']) !!}
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('product_temperature[]', __('lang_v1.product_temperature') . ':*') !!}
                                        {!! Form::select('product_temperature[]', $product_temperatures, null, [
                                            'class' => 'form-control product-temperature-select',
                                            'placeholder' => __('messages.please_select'),
                                            'required',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                {!! Form::label('total[]', __('lang_v1.total_after_mix') . ':') !!}
                                {!! Form::text('total[]', null, [
                                    'class' => 'form-control total-input',
                                    'readonly',
                                    'style' => 'background-color: #eee;',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
            </div>

            <div class="row mt-2 mb-3">
                <div class="col-sm-12">
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" id="add_more_section">Add More</button>
                        <button type="button" class="btn btn-danger remove-section" style="display: none;">Remove</button>
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
                    <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                </div>
            </div>
            {!! Form::close() !!}
        @endcomponent
    </section>
@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            function initializeSelect2() {
                $('.temperature-select, .product-temperature-select').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            width: '100%'
                        });
                    }
                });
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
                $('.total-input').each(function() {
                    const total = parseFloat($(this).val()) || 0;
                    grandTotal += total;
                });
                $('#grand_total').val(grandTotal.toFixed(2));
            }

            // Initialize first section
            initializeSelect2();

            // Add more section
            $('#add_more_section').click(function() {
                const newSectionIndex = $('.packing-row').length;
                const $newSection = $('.packing-row').first().clone();

                $newSection.find('input').val('');
                $newSection.find('.select2-container').remove();
                $newSection.find('.temperature-select, .product-temperature-select').removeClass('select2-hidden-accessible').val('');

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

            // Temperature selection handler
            $(document).on('change', '.temperature-select', function() {
                const $row = $(this).closest('.packing-row');
                const temperature = $(this).val();

                if (temperature) {
                    $.ajax({
                        url: '/packing/get-temperature-quantity1',
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

            // Form validation
            $('#packing_form').on('submit', function(e) {
                let isValid = true;

                $('.product-temperature-select').each(function() {
                    if (!$(this).val()) {
                        toastr.error('Please select product temperature for all sections');
                        isValid = false;
                        return false;
                    }
                });

                $('.temperature-select').each(function() {
                    if (!$(this).val()) {
                        toastr.error('Please select temperature for all sections');
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