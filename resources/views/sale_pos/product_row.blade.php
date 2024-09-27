@php
    \Log::info('Product data raw product blade:', ['product' => $product->toArray() ?? 'Product is null']);
    \Log::info('Packing data raw product blade:', ['packings' => $product->product->packings ?? 'Packings is null']);

    // Get the first packing or set default values
    $packing = $product->product->packings->first() ?? new \stdClass();
	$qty = $packing->total ?? '';
    $jar = $packing->jar ?? '';
    $packet = $packing->packet ?? '';
    $grandTotal = $packing->grand_total ?? $product->default_sell_price;
@endphp
@php
	$common_settings = session()->get('business.common_settings');
	$multiplier = 1;

	$action = !empty($action) ? $action : '';
@endphp

@foreach($sub_units as $key => $value)
	@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
		@php
			$multiplier = $value['multiplier'];
		@endphp
	@endif
@endforeach

<tr class="product_row" data-row_index="{{$row_count}}" @if(!empty($so_line)) data-so_id="{{$so_line->transaction_id}}" @endif>
	<td>
		@if(!empty($so_line))
			<input type="hidden" 
			name="products[{{$row_count}}][so_line_id]" 
			value="{{$so_line->id}}">
		@endif
		@php
			$product_name = $product->product_name . '<br/>' . $product->sub_sku ;
			if(!empty($product->brand)){ $product_name .= ' ' . $product->brand ;}
		@endphp

		@if( ($edit_price || $edit_discount) && empty($is_direct_sell) )
		<div title="@lang('lang_v1.pos_edit_product_price_help')" style="display: inline">
		<span class="text-link text-info cursor-pointer" data-toggle="modal" data-target="#row_edit_product_price_modal_{{$row_count}}">
			{!! $product_name !!}
			&nbsp;<i class="fa fa-info-circle"></i>
		</span>
		</div>
		@else
			{!! $product_name !!}
		@endif
		<img src="@if(count($product->media) > 0)
						{{$product->media->first()->display_url}}
					@elseif(!empty($product->product_image))
						{{asset('/uploads/img/' . rawurlencode($product->product_image))}}
					@else
						{{asset('/img/default.png')}}
					@endif" alt="product-img" loading="lazy"style="height: 100%;display: inline;margin-left: 3px; border: black;border-radius: 5px; margin-top: 5px; width: 50px;object-fit: cover;">

		<input type="hidden" class="enable_sr_no" value="{{$product->enable_sr_no}}">
		<input type="hidden" 
			class="product_type" 
			name="products[{{$row_count}}][product_type]" 
			value="{{$product->product_type}}">

		@php
			$hide_tax = 'hide';
	        if(session()->get('business.enable_inline_tax') == 1){
	            $hide_tax = '';
	        }
	        
			$tax_id = $product->tax_id;
			$item_tax = !empty($product->item_tax) ? $product->item_tax : 0;
			$unit_price_inc_tax = $product->sell_price_inc_tax;

			if($hide_tax == 'hide'){
				$tax_id = null;
				$unit_price_inc_tax = $product->default_sell_price;
			}

			if(!empty($so_line) && $action !== 'edit') {
				$tax_id = $so_line->tax_id;
				$item_tax = $so_line->item_tax;
				$unit_price_inc_tax = $so_line->unit_price_inc_tax;
			}

			$discount_type = !empty($product->line_discount_type) ? $product->line_discount_type : 'fixed';
			$discount_amount = !empty($product->line_discount_amount) ? $product->line_discount_amount : 0;
			
			if(!empty($discount)) {
				$discount_type = $discount->discount_type;
				$discount_amount = $discount->discount_amount;
			}

			if(!empty($so_line) && $action !== 'edit') {
				$discount_type = $so_line->line_discount_type;
				$discount_amount = $so_line->line_discount_amount;
			}

  			$sell_line_note = '';
  			if(!empty($product->sell_line_note)){
  				$sell_line_note = $product->sell_line_note;
  			}
			  if(!empty($so_line)){
  				$sell_line_note = $so_line->sell_line_note;
  			}
  		@endphp

		@if(!empty($discount))
			{!! Form::hidden("products[$row_count][discount_id]", $discount->id); !!}
		@endif

		@php
			$warranty_id = !empty($action) && $action == 'edit' && !empty($product->warranties->first())  ? $product->warranties->first()->id : $product->warranty_id;

			if($discount_type == 'fixed') {
				$discount_amount = $discount_amount * $multiplier;
			}
		@endphp

		@if(empty($is_direct_sell))
		<div class="modal fade row_edit_product_price_model" id="row_edit_product_price_modal_{{$row_count}}" tabindex="-1" role="dialog">
			@include('sale_pos.partials.row_edit_product_price_modal')
		</div>
		@endif

		<!-- New fields for jar and packet -->
		<input type="hidden" name="products[{{$row_count}}][jar]" value="{{ $jar }}">
		<input type="hidden" name="products[{{$row_count}}][packet]" value="{{ $packet }}">
	</td>

	<td>
		@if(!empty($product->transaction_sell_lines_id))
			<input type="hidden" name="products[{{$row_count}}][transaction_sell_lines_id]" class="form-control" value="{{$product->transaction_sell_lines_id}}">
		@endif

		<input type="hidden" name="products[{{$row_count}}][product_id]" class="form-control product_id" value="{{$product->product_id}}">

		<input type="hidden" value="{{$product->variation_id}}" 
			name="products[{{$row_count}}][variation_id]" class="row_variation_id">

		<input type="hidden" value="{{$product->enable_stock}}" 
			name="products[{{$row_count}}][enable_stock]">
		
		@php
			$allow_decimal = true;
			if($product->unit_allow_decimal != 1) {
				$allow_decimal = false;
			}
		@endphp
		@foreach($sub_units as $key => $value)
        	@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
        		@php
        			$max_qty_rule = $max_qty_rule / $multiplier;
        			$unit_name = $value['name'];
        			$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);

        			if(!empty($product->lot_no_line_id)){
        				$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);
        			}

        			if($value['allow_decimal']) {
        				$allow_decimal = true;
        			}
        		@endphp
        	@endif
        @endforeach
		<input type="text" 
			class="form-control pos_quantity input_number mousetrap input_quantity" 
			value="{{$qty ?? 1}}"
			name="products[{{$row_count}}][quantity]" 
			@if($product->enable_stock) 
				data-rule-max-value="{{$product->qty_available}}" 
				data-msg-max-value="{{$max_qty_msg}}" 
			@endif
			disabled
		>
		
		<input type="hidden" name="products[{{$row_count}}][product_unit_id]" value="{{$product->unit_id}}">
		@if(count($sub_units) > 0)
			<br>
			<select name="products[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit">
                @foreach($sub_units as $key => $value)
                    <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}" data-unit_name="{{$value['name']}}" data-allow_decimal="{{$value['allow_decimal']}}" @if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key) selected @endif>
                        {{$value['name']}}
                    </option>
                @endforeach
           </select>
		@else
			{{$product->unit}}
		@endif
	</td>

	<!-- New columns for jar and packet -->
	<td>
    	<input type="text" class="form-control" value="{{ $jar }}" disabled>
	</td>
	<td>
		<input type="text" class="form-control" value="{{ $packet }}" disabled>
	</td>

	@if(!empty($pos_settings['inline_service_staff']))
		<td>
			<div class="form-group">
				<div class="input-group">
					{!! Form::select("products[" . $row_count . "][res_service_staff_id]", $waiters, !empty($product->res_service_staff_id) ? $product->res_service_staff_id : null, ['class' => 'form-control select2 order_line_service_staff', 'placeholder' => __('restaurant.select_service_staff'), 'required' => (!empty($pos_settings['is_service_staff_required']) && $pos_settings['is_service_staff_required'] == 1) ? true : false ]); !!}
				</div>
			</div>
		</td>
	@endif

	<td>
	<input type="text" name="products[{{$row_count}}][unit_price]" class="form-control pos_unit_price input_number" value="{{$grandTotal}}" readonly>	</td>

	<td @if(!$edit_discount) class="hide" @endif>
		{!! Form::text("products[$row_count][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number row_discount_amount']); !!}<br>
		{!! Form::select("products[$row_count][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type']); !!}
		@if(!empty($discount))
			<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
		@endif
	</td>

	<td class="text-center {{$hide_tax}}">
		{!! Form::hidden("products[$row_count][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}
	
		{!! Form::select("products[$row_count][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); !!}
	</td>

	<td class="{{$hide_tax}}">
		<input type="text" name="products[{{$row_count}}][unit_price_inc_tax]" class="form-control pos_unit_price_inc_tax input_number" value="{{@num_format($unit_price_inc_tax)}}" @if(!$edit_price) readonly @endif>
	</td>

	@if(!empty($common_settings['enable_product_warranty']) && !empty($is_direct_sell))
		<td>
			{!! Form::select("products[$row_count][warranty_id]", $warranties, $warranty_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control']); !!}
		</td>
	@endif

	<td class="text-center">
		@php
			$subtotal_type = !empty($pos_settings['is_pos_subtotal_editable']) ? 'text' : 'hidden';
		@endphp
		<input type="{{$subtotal_type}}" class="form-control pos_line_total @if(!empty($pos_settings['is_pos_subtotal_editable'])) input_number @endif" value="{{@num_format($grandTotal)}}">
		<span class="display_currency pos_line_total_text @if(!empty($pos_settings['is_pos_subtotal_editable'])) hide @endif" data-currency_symbol="true">{{$product->quantity_ordered*$unit_price_inc_tax}}</span>
	</td>

	<td class="text-center v-center">
		<i class="fa fa-times text-danger pos_remove_row cursor-pointer" aria-hidden="true"></i>
	</td>
</tr>