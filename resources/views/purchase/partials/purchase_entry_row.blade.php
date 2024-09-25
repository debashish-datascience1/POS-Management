@foreach($variations as $variation)
<tr @if(!empty($purchase_order_line)) data-purchase_order_id="{{$purchase_order_line->transaction_id}}" @endif
    @if(!empty($purchase_requisition_line))
    data-purchase_requisition_id="{{$purchase_requisition_line->transaction_id}}" @endif>
    <td><span class="sr_number"></span></td>
    <td>
        {{ $product->name }} ({{$variation->sub_sku}})
        @if($product->type == 'variable')
        <br />
        (<b>{{ $variation->product_variation->name }}</b> : {{ $variation->name }})
        @endif
        @if($product->enable_stock == 1)
        <br>
        <small class="text-muted" style="white-space: nowrap;">@lang('report.current_stock'):
            @if(!empty($variation->variation_location_details->first()))
            {{ @num_format($variation->variation_location_details->first()->qty_available) }} @else 0 @endif {{
            $product->unit->short_name }}</small>
        @endif
    </td>
    <td>
        @if(!empty($purchase_order_line))
        {!! Form::hidden('purchases[' . $row_count . '][purchase_order_line_id]', $purchase_order_line->id); !!}
        @endif
        @if(!empty($purchase_requisition_line))
        {!! Form::hidden('purchases[' . $row_count . '][purchase_requisition_line_id]', $purchase_requisition_line->id);
        !!}
        @endif
        {!! Form::hidden('purchases[' . $row_count . '][product_id]', $product->id); !!}
        {!! Form::hidden('purchases[' . $row_count . '][variation_id]', $variation->id, ['class' =>
        'hidden_variation_id']); !!}

        @php
        $check_decimal = 'false';
        if($product->unit->allow_decimal == 0){
        $check_decimal = 'true';
        }
        $quantity_value = !empty($purchase_order_line) ? $purchase_order_line->quantity : 1;
        @endphp

        <input type="text" name="purchases[{{$row_count}}][quantity]" value="{{@format_quantity($quantity_value)}}"
            class="form-control input-sm purchase_quantity input_number" required data-rule-abs_digit={{$check_decimal}}
            data-msg-abs_digit="{{__('lang_v1.decimal_value_not_allowed')}}">

        <input type="hidden" class="base_unit_cost" value="{{$variation->default_purchase_price}}">
        <input type="hidden" class="base_unit_selling_price" value="{{$variation->sell_price_inc_tax}}">

        <input type="hidden" name="purchases[{{$row_count}}][product_unit_id]" value="{{$product->unit->id}}">
    </td>
    <td>
        {!! Form::text('purchases[' . $row_count . '][purchase_unit_cost]',
        number_format($variation->default_purchase_price, 2), ['class' => 'form-control input-sm purchase_unit_cost
        input_number', 'required']); !!}
    </td>
    <td>
        {!! Form::text('purchases[' . $row_count . '][gst]', null, ['class' => 'form-control input-sm gst',
        'placeholder' => 'GST %', 'required']); !!}
    </td>
    {{-- <td>
        <span class="row_subtotal_before_tax display_currency">0</span>
        <input type="hidden" class="row_subtotal_before_tax_hidden" value="0">
    </td> --}}
    {{-- <td>
        <div class="input-group">
            <select name="purchases[{{ $row_count }}][purchase_line_tax_id]"
                class="form-control select2 input-sm purchase_line_tax_id" placeholder="'Please Select'">
                <option value="" data-tax_amount="0">@lang('lang_v1.none')</option>
                @foreach($taxes as $tax)
                <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}">{{ $tax->name }}</option>
                @endforeach
            </select>
            {!! Form::hidden('purchases[' . $row_count . '][item_tax]', 0, ['class' => 'purchase_product_unit_tax']);
            !!}
            <span class="input-group-addon purchase_product_unit_tax_text">0.00</span>
        </div>
    </td> --}}
    <td class="hidden">
        {!! Form::text('purchases[' . $row_count . '][purchase_price_inc_tax]', number_format($variation->dpp_inc_tax,
        2), ['class' => 'form-control input-sm purchase_unit_cost_after_tax input_number', 'required']); !!}
    </td>
    <td class="hidden">
        <span class="row_subtotal_after_tax display_currency">0</span>
        <input type="hidden" class="row_subtotal_after_tax_hidden" value="0">
    </td>
    <td class="hidden">
        {!! Form::text('purchases[' . $row_count . '][profit_percent]', number_format($variation->profit_percent, 2),
        ['class' => 'form-control input-sm input_number profit_percent', 'required']); !!}
    </td>
    <td class="hidden">
        {!! Form::text('purchases[' . $row_count . '][lot_number]', null, ['class' => 'form-control input-sm']); !!}
    </td>
    <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
</tr>
@endforeach

<input type="hidden" id="row_count" value="{{ $row_count }}">

<style>
    .hidden {
        display: none;
    }
</style>