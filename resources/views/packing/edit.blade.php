@extends('layouts.app')

@section('title', __('lang_v1.edit_packing'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.edit_packing')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::model($packing, ['url' => action([\App\Http\Controllers\PackingController::class, 'update'], [$packing->id]), 'method' => 'PUT', 'id' => 'packing_edit_form' ]) !!}
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('date', __('messages.date') . ':*') !!}
                    {!! Form::date('date', null, ['class' => 'form-control', 'required']); !!}
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
                    {!! Form::label('packing', __('lang_v1.packing') . ':*') !!}
                    <div id="packing_container">
                        @php
                            $packing_items = is_array($packing->packing) ? $packing->packing : explode(',', $packing->packing);
                        @endphp
                        @foreach($packing_items as $index => $item)
                            @php
                                if (is_array($item)) {
                                    $size = $item['size'];
                                    $quantity = $item['quantity'];
                                } else {
                                    list($size, $quantity) = explode(':', $item);
                                }
                            @endphp
                            <div class="packing-option mb-2">
                                <select name="packing[{{ $index }}][size]" class="form-control packing-size" style="width: 40%; display: inline-block;">
                                    @foreach($packing_options as $option)
                                        <option value="{{ $option }}" {{ $size == $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <input type="number" name="packing[{{ $index }}][quantity]" class="form-control packing-quantity" min="1" value="{{ $quantity }}" style="width: 40%; display: inline-block;">
                                <button type="button" class="btn btn-danger remove-packing">X</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-primary mt-2" id="add_packing">Add Packing</button>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('total', __('lang_v1.total') . ':') !!}
                    {!! Form::number('total', null, ['class' => 'form-control', 'readonly', 'id' => 'total']); !!}
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
        const packingOptions = {!! json_encode($packing_options) !!};
        let packingCount = {{ count(is_array($packing->packing) ? $packing->packing : explode(',', $packing->packing)) }};

        function addPackingOption(size = null, quantity = 1) {
            let html = `
                <div class="packing-option mb-2">
                    <select name="packing[${packingCount}][size]" class="form-control packing-size" style="width: 40%; display: inline-block;">
                        ${packingOptions.map(option => `<option value="${option}" ${size === option ? 'selected' : ''}>${option}</option>`).join('')}
                    </select>
                    <input type="number" name="packing[${packingCount}][quantity]" class="form-control packing-quantity" min="1" value="${quantity}" style="width: 40%; display: inline-block;">
                    <button type="button" class="btn btn-danger remove-packing">X</button>
                </div>
            `;
            $('#packing_container').append(html);
            packingCount++;
        }

        $('#add_packing').click(() => addPackingOption());

        $(document).on('click', '.remove-packing', function() {
            $(this).closest('.packing-option').remove();
        });

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

        // Trigger calculation on page load
        calculateTotal();
    });
</script>
@endsection