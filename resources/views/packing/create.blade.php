@extends('layouts.app')

@section('title', __('lang_v1.add_packing'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.add_packing')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action([\App\Http\Controllers\PackingController::class, 'store']), 'method' => 'post', 'id' => 'packing_form' ]) !!}
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('date', __('messages.date') . ':*') !!}
                    {!! Form::date('date', \Carbon\Carbon::now(), ['class' => 'form-control', 'required']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('product_id', __('lang_v1.product') . ':*') !!}
                    {!! Form::select('product_id', $products, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'product_id']); !!}
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('product_output', __('lang_v1.product_output') . ':') !!}
                    {!! Form::number('product_output', null, ['class' => 'form-control', 'readonly', 'id' => 'product_output']); !!}
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
                <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
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

        function addOption(type) {
            let options = type === 'jar' ? jarOptions : packetOptions;
            let count = type === 'jar' ? jarCount++ : packetCount++;
            let html = `
                <div class="${type}-option mb-2">
                    <div style="width: 30%; display: inline-block;">
                        <label>Choose:</label>
                        <select name="${type}[${count}][size]" class="form-control ${type}-size">
                            ${options.map(option => `<option value="${option}">${option}</option>`).join('')}
                        </select>
                    </div>
                    <div style="width: 30%; display: inline-block;">
                        <label>Qty:</label>
                        <input type="number" name="${type}[${count}][quantity]" class="form-control ${type}-quantity" min="1" value="1">
                    </div>
                    <div style="width: 30%; display: inline-block;">
                        <label>Price:</label>
                        <input type="number" name="${type}[${count}][price]" class="form-control ${type}-price" min="0" step="0.01" value="0">
                    </div>
                    <button type="button" class="btn btn-danger remove-${type}">X</button>
                </div>
            `;
            $(`#${type}_container`).append(html);
            calculateGrandTotal();
        }

        $('#add_jar').click(() => addOption('jar'));
        $('#add_packet').click(() => addOption('packet'));

        $(document).on('click', '.remove-jar', function() {
            $(this).closest('.jar-option').remove();
            calculateGrandTotal();
        });

        $(document).on('click', '.remove-packet', function() {
            $(this).closest('.packet-option').remove();
            calculateGrandTotal();
        });

        // Add one jar and one packet option by default
        addOption('jar');
        addOption('packet');

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
    });
</script>
@endsection