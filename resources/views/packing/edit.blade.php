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
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('date', __('messages.date') . ':*') !!}
                    {!! Form::date('date', null, ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location').':*') !!}
                    @show_tooltip(__('tooltip.purchase_location'))
                    {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'], $bl_attributes); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('product_id', __('lang_v1.product') . ':*') !!}
                    {!! Form::select('product_id', $products, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'product_id']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('product_output', __('lang_v1.product_output') . ':') !!}
                    {!! Form::number('product_output', null, ['class' => 'form-control', 'id' => 'product_output']); !!}
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
                        <!-- Dynamic jar options will be added here -->
                    </div>
                    <button type="button" class="btn btn-primary mt-2" id="add_jar">Add Jar</button>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('packet', __('lang_v1.packet') . ':*') !!}
                    <div id="packet_container">
                        <!-- Dynamic packet options will be added here -->
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
            @foreach(explode(',', $packing->jar) as $jar)
                @php
                    list($size, $quantity, $price) = explode(':', $jar);
                @endphp
                addOption('jar', {size: '{{ $size }}', quantity: {{ $quantity }}, price: {{ $price }}});
            @endforeach
        @endif

        @if(isset($packing->packet))
            @foreach(explode(',', $packing->packet) as $packet)
                @php
                    list($size, $quantity, $price) = explode(':', $packet);
                @endphp
                addOption('packet', {size: '{{ $size }}', quantity: {{ $quantity }}, price: {{ $price }}});
            @endforeach
        @endif

        $('#product_id').change(function(){
            var productId = $(this).val();
            if(productId) {
                $.ajax({
                    url: '/get-product-output/' + productId,
                    type: "GET",
                    dataType: "json",
                    success:function(data) {
                        $('#product_output').val(data.raw_material);
                        calculateTotal();
                    }
                });
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

        // Trigger calculations on page load
        calculateTotal();
        calculateGrandTotal();
    });
</script>
@endsection