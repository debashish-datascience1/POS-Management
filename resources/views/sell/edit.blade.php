@extends('layouts.app')

@php
    if (!empty($status) && $status == 'quotation') {
        $title = __('lang_v1.add_quotation');
    } elseif (!empty($status) && $status == 'draft') {
        $title = __('lang_v1.add_draft');
    } else {
        $title = __('sale.add_sale');
    }

    if ($sale_type == 'sales_order') {
        $title = __('lang_v1.sales_order');
    }
@endphp

@section('title', $title)

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ $title }}</h1>
    </section>
    <!-- Main content -->
    <section class="content no-print">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">
        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif
        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif
        @if (count($business_locations) > 0)
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            {!! Form::select(
                                'select_location_id',
                                $business_locations,
                                $default_location->id ?? null,
                                ['class' => 'form-control input-sm', 'id' => 'select_location_id', 'required', 'autofocus'],
                                $bl_attributes,
                            ) !!}
                            <span class="input-group-addon">
                                @show_tooltip(__('tooltip.sale_location'))
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @php
            $custom_labels = json_decode(session('business.custom_labels'), true);
            $common_settings = session()->get('business.common_settings');
        @endphp
        <input type="hidden" id="item_addition_method" value="{{ $business_details->item_addition_method }}">
        	{!! Form::open(['url' => action([\App\Http\Controllers\SellController::class, 'update']), 'method' => 'put', 'id' => 'edit_sell_form', 'files' => true ]) !!}

        @if (!empty($sale_type))
            <input type="hidden" id="sale_type" name="type" value="{{ $sale_type }}">
        @endif
        <div class="row">
            <div class="col-md-12 col-sm-12">
                @component('components.widget', ['class' => 'box-solid'])
                    {!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null, [
                        'id' => 'location_id',
                        'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                            ? $default_location->receipt_printer_type
                            : 'browser',
                        'data-default_payment_accounts' => !empty($default_location) ? $default_location->default_payment_accounts : '',
                    ]) !!}

                    @if (!empty($price_groups))
                        @if (count($price_groups) > 1)
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fas fa-money-bill-alt"></i>
                                        </span>
                                        @php
                                            reset($price_groups);
                                            $selected_price_group =
                                                !empty($default_price_group_id) &&
                                                array_key_exists($default_price_group_id, $price_groups)
                                                    ? $default_price_group_id
                                                    : null;
                                        @endphp
                                        {!! Form::hidden('hidden_price_group', key($price_groups), ['id' => 'hidden_price_group']) !!}
                                        {!! Form::select('price_group', $price_groups, $selected_price_group, [
                                            'class' => 'form-control select2',
                                            'id' => 'price_group',
                                        ]) !!}
                                        <span class="input-group-addon">
                                            @show_tooltip(__('lang_v1.price_group_help_text'))
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @else
                            @php
                                reset($price_groups);
                            @endphp
                            {!! Form::hidden('price_group', key($price_groups), ['id' => 'price_group']) !!}
                        @endif
                    @endif

                    {!! Form::hidden('default_price_group', null, ['id' => 'default_price_group']) !!}

                    @if (in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
                        <div class="col-md-4 col-sm-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-external-link-square-alt text-primary service_modal_btn"></i>
                                    </span>
                                    {!! Form::select('types_of_service_id', $types_of_service, null, [
                                        'class' => 'form-control',
                                        'id' => 'types_of_service_id',
                                        'style' => 'width: 100%;',
                                        'placeholder' => __('lang_v1.select_types_of_service'),
                                    ]) !!}

                                    {!! Form::hidden('types_of_service_price_group', null, ['id' => 'types_of_service_price_group']) !!}

                                    <span class="input-group-addon">
                                        @show_tooltip(__('lang_v1.types_of_service_help'))
                                    </span>
                                </div>
                                <small>
                                    <p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'): <span></span></p>
                                </small>
                            </div>
                        </div>
                    @endif

                    @if (in_array('subscription', $enabled_modules))
                        <div class="col-md-4 pull-right col-sm-6">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' => 'is_recurring']) !!} @lang('lang_v1.subscribe')?
                                </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal"
                                    class="btn btn-link"><i
                                        class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
                            </div>
                        </div>
                    @endif
                    <div class="clearfix"></div>
                    <div class="@if (!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
                        <div class="form-group">
                            {!! Form::label('contact_id', __('contact.customer') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                <input type="hidden" id="default_customer_id" value="{{ $walk_in_customer['id'] }}">
                                <input type="hidden" id="default_customer_name" value="{{ $walk_in_customer['name'] }}">
                                <input type="hidden" id="default_customer_balance"
                                    value="{{ $walk_in_customer['balance'] ?? '' }}">
                                <input type="hidden" id="default_customer_address"
                                    value="{{ $walk_in_customer['shipping_address'] ?? '' }}">
                                @if (
                                    !empty($walk_in_customer['price_calculation_type']) &&
                                        $walk_in_customer['price_calculation_type'] == 'selling_price_group')
                                    <input type="hidden" id="default_selling_price_group"
                                        value="{{ $walk_in_customer['selling_price_group_id'] ?? '' }}">
                                @endif
                                {!! Form::select('contact_id', [], null, [
                                    'class' => 'form-control mousetrap',
                                    'id' => 'customer_id',
                                    'placeholder' => 'Enter Customer name / phone',
                                    'required',
                                ]) !!}
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat add_new_customer"
                                        data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                                </span>
                            </div>
                            <small class="text-danger hide contact_due_text"><strong>@lang('account.customer_due'):</strong>
                                <span></span></small>
                        </div>
                        <small>
                            <strong>
                                @lang('lang_v1.billing_address'):
                            </strong>
                            <div id="billing_address_div">
                                {!! $walk_in_customer['contact_address'] ?? '' !!}
                            </div>
                            <br>
                            <strong>
                                @lang('lang_v1.shipping_address'):
                            </strong>
                            <div id="shipping_address_div">
                                {{ $walk_in_customer['supplier_business_name'] ?? '' }},<br>
                                {{ $walk_in_customer['name'] ?? '' }},<br>
                                {{ $walk_in_customer['shipping_address'] ?? '' }}
                            </div>
                        </small>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <div class="multi-input">
                                @php
                                    $is_pay_term_required = !empty($pos_settings['is_pay_term_required']);
                                @endphp
                                {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
                                <br />
                                {!! Form::number('pay_term_number', $walk_in_customer['pay_term_number'], [
                                    'class' => 'form-control width-40 pull-left',
                                    'placeholder' => __('contact.pay_term'),
                                    'required' => $is_pay_term_required,
                                ]) !!}

                                {!! Form::select(
                                    'pay_term_type',
                                    ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')],
                                    $walk_in_customer['pay_term_type'],
                                    [
                                        'class' => 'form-control width-60 pull-left',
                                        'placeholder' => __('messages.please_select'),
                                        'required' => $is_pay_term_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    </div>

                    @if (!empty($commission_agent))
                        @php
                            $is_commission_agent_required = !empty($pos_settings['is_commission_agent_required']);
                        @endphp
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':') !!}
                                {!! Form::select('commission_agent', $commission_agent, null, [
                                    'class' => 'form-control select2',
                                    'id' => 'commission_agent',
                                    'required' => $is_commission_agent_required,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    <div class="@if (!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('transaction_date', $default_datetime, ['class' => 'form-control', 'readonly', 'required']) !!}
                            </div>
                        </div>
                    </div>
                    @if (!empty($status))
                        <input type="hidden" name="status" id="status" value="{{ $status }}">

                        @if (in_array($status, ['draft', 'quotation']))
                            <input type="hidden" id="disable_qty_alert">
                        @endif
                    @else
                        <div class="@if (!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
                            <div class="form-group">
                                {!! Form::label('status', __('sale.status') . ':*') !!}
                                {!! Form::select('status', $statuses, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('messages.please_select'),
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if ($sale_type != 'sales_order')
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':') !!}
                                {!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('messages.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @can('edit_invoice_number')
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label(
                                    'invoice_no',
                                    $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no') . ':',
                                ) !!}
                                {!! Form::text('invoice_no', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $sale_type == 'sales_order' ? __('restaurant.order_no') : __('sale.invoice_no'),
                                ]) !!}
                                <p class="help-block">@lang('lang_v1.keep_blank_to_autogenerate')</p>
                            </div>
                        </div>
                    @endcan

                    @php
                        $custom_field_1_label = !empty($custom_labels['sell']['custom_field_1'])
                            ? $custom_labels['sell']['custom_field_1']
                            : '';

                        $is_custom_field_1_required =
                            !empty($custom_labels['sell']['is_custom_field_1_required']) &&
                            $custom_labels['sell']['is_custom_field_1_required'] == 1
                                ? true
                                : false;

                        $custom_field_2_label = !empty($custom_labels['sell']['custom_field_2'])
                            ? $custom_labels['sell']['custom_field_2']
                            : '';

                        $is_custom_field_2_required =
                            !empty($custom_labels['sell']['is_custom_field_2_required']) &&
                            $custom_labels['sell']['is_custom_field_2_required'] == 1
                                ? true
                                : false;

                        $custom_field_3_label = !empty($custom_labels['sell']['custom_field_3'])
                            ? $custom_labels['sell']['custom_field_3']
                            : '';

                        $is_custom_field_3_required =
                            !empty($custom_labels['sell']['is_custom_field_3_required']) &&
                            $custom_labels['sell']['is_custom_field_3_required'] == 1
                                ? true
                                : false;

                        $custom_field_4_label = !empty($custom_labels['sell']['custom_field_4'])
                            ? $custom_labels['sell']['custom_field_4']
                            : '';

                        $is_custom_field_4_required =
                            !empty($custom_labels['sell']['is_custom_field_4_required']) &&
                            $custom_labels['sell']['is_custom_field_4_required'] == 1
                                ? true
                                : false;
                    @endphp
                    @if (!empty($custom_field_1_label))
                        @php
                            $label_1 = $custom_field_1_label . ':';
                            if ($is_custom_field_1_required) {
                                $label_1 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('custom_field_1', $label_1) !!}
                                {!! Form::text('custom_field_1', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $custom_field_1_label,
                                    'required' => $is_custom_field_1_required,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_field_2_label))
                        @php
                            $label_2 = $custom_field_2_label . ':';
                            if ($is_custom_field_2_required) {
                                $label_2 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('custom_field_2', $label_2) !!}
                                {!! Form::text('custom_field_2', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $custom_field_2_label,
                                    'required' => $is_custom_field_2_required,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_field_3_label))
                        @php
                            $label_3 = $custom_field_3_label . ':';
                            if ($is_custom_field_3_required) {
                                $label_3 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('custom_field_3', $label_3) !!}
                                {!! Form::text('custom_field_3', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $custom_field_3_label,
                                    'required' => $is_custom_field_3_required,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($custom_field_4_label))
                        @php
                            $label_4 = $custom_field_4_label . ':';
                            if ($is_custom_field_4_required) {
                                $label_4 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('custom_field_4', $label_4) !!}
                                {!! Form::text('custom_field_4', null, [
                                    'class' => 'form-control',
                                    'placeholder' => $custom_field_4_label,
                                    'required' => $is_custom_field_4_required,
                                ]) !!}
                            </div>
                        </div>
                    @endif
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('upload_document', __('purchase.attach_document') . ':') !!}
                            {!! Form::file('sell_document', [
                                'id' => 'upload_document',
                                'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types'))),
                            ]) !!}
                            <p class="help-block">
                                @lang('purchase.max_file_size', ['size' => config('constants.document_size_limit') / 1000000])
                                @includeIf('components.document_help_text')
                            </p>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    @if ((!empty($pos_settings['enable_sales_order']) && $sale_type != 'sales_order') || $is_order_request_enabled)
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('sales_order_ids', __('lang_v1.sales_order') . ':') !!}
                                {!! Form::select('sales_order_ids[]', [], null, [
                                    'class' => 'form-control select2',
                                    'multiple',
                                    'id' => 'sales_order_ids',
                                ]) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    @endif
                    <!-- Call restaurant module if defined -->
                    @if (in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules))
                        <span id="restaurant_module_span">
                        </span>
                    @endif
                    <div id="product-groups-container">
                        <div class="product-group">
                            <!-- Product Temperature Section -->
                            <div class="row product-row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="product_temperature_1">Product Temperature:*</label>
                                        <select name="product_temperature[]" id="product_temperature_1"
                                            class="form-control product-temperature-select" required>
                                            <option value="">Please select</option>
                                            @foreach ($product_temperatures as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Jar Details Section -->
                                <div class="col-md-6">
                                    <h4>Jar Details</h4>
                                    <div class="jar-container">
                                        <div class="jar-row">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="jar_type_1">Jar:</label>
                                                        <select name="jar_type[]" id="jar_type_1" class="form-control">
                                                            <option value="">Select Jar</option>
                                                            @foreach ($jarOptions as $option)
                                                                <option value="{{ $option }}">{{ $option }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="jar_value_1">Value:</label>
                                                        <input type="text" name="jar_value[]" id="jar_value_1"
                                                            class="form-control" placeholder="Enter Value">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="jar_price_1">Jar Price:</label>
                                                        <input type="number" name="jar_price[]" id="jar_price_1"
                                                            class="form-control" placeholder="Enter Price" step="0.01"
                                                            min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-success add-jar-row">Add More
                                                            Jar</button>
                                                        <button type="button" class="btn btn-danger remove-jar-row"
                                                            style="display: none;">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Packet Details Section -->
                                <div class="col-md-6">
                                    <h4>Packet Details</h4>
                                    <div class="packet-container">
                                        <div class="packet-row">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="packet_type_1">Packet:</label>
                                                        <select name="packet_type[]" id="packet_type_1" class="form-control">
                                                            <option value="">Select Packet</option>
                                                            @foreach ($packetOptions as $option)
                                                                <option value="{{ $option }}">{{ $option }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="packet_value_1">Value:</label>
                                                        <input type="text" name="packet_value[]" id="packet_value_1"
                                                            class="form-control" placeholder="Enter Value">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="packet_price_1">Packet Price:</label>
                                                        <input type="number" name="packet_price[]" id="packet_price_1"
                                                            class="form-control" placeholder="Enter Price" step="0.01"
                                                            min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-success add-packet-row">Add More
                                                            Packet</button>
                                                        <button type="button" class="btn btn-danger remove-packet-row"
                                                            style="display: none;">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Remove Product Group Button -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-danger remove-product-group"
                                        style="display: none;">Remove Product Group</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add More Product Group Button -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary add-product-group">Add More Product</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="grand_total">Grand Total:</label>
                                <input type="number" id="grand_total" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                @endcomponent
                @component('components.widget', ['class' => 'box-solid'])
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_details', __('sale.shipping_details')) !!}
                            {!! Form::textarea('shipping_details', null, [
                                'class' => 'form-control',
                                'placeholder' => __('sale.shipping_details'),
                                'rows' => '3',
                                'cols' => '30',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_address', __('lang_v1.shipping_address')) !!}
                            {!! Form::textarea('shipping_address', null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.shipping_address'),
                                'rows' => '3',
                                'cols' => '30',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_charges', __('sale.shipping_charges')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::text('shipping_charges', @num_format(0.0), [
                                    'class' => 'form-control input_number',
                                    'placeholder' => __('sale.shipping_charges'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_status', __('lang_v1.shipping_status')) !!}
                            {!! Form::select('shipping_status', $shipping_statuses, null, [
                                'class' => 'form-control',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':') !!}
                            {!! Form::text('delivered_to', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.delivered_to')]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('delivery_person', __('lang_v1.delivery_person') . ':') !!}
                            {!! Form::select('delivery_person', $users, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('messages.please_select'),
                            ]) !!}
                        </div>
                    </div>
                    @php
                        $shipping_custom_label_1 = !empty($custom_labels['shipping']['custom_field_1'])
                            ? $custom_labels['shipping']['custom_field_1']
                            : '';

                        $is_shipping_custom_field_1_required =
                            !empty($custom_labels['shipping']['is_custom_field_1_required']) &&
                            $custom_labels['shipping']['is_custom_field_1_required'] == 1
                                ? true
                                : false;

                        $shipping_custom_label_2 = !empty($custom_labels['shipping']['custom_field_2'])
                            ? $custom_labels['shipping']['custom_field_2']
                            : '';

                        $is_shipping_custom_field_2_required =
                            !empty($custom_labels['shipping']['is_custom_field_2_required']) &&
                            $custom_labels['shipping']['is_custom_field_2_required'] == 1
                                ? true
                                : false;

                        $shipping_custom_label_3 = !empty($custom_labels['shipping']['custom_field_3'])
                            ? $custom_labels['shipping']['custom_field_3']
                            : '';

                        $is_shipping_custom_field_3_required =
                            !empty($custom_labels['shipping']['is_custom_field_3_required']) &&
                            $custom_labels['shipping']['is_custom_field_3_required'] == 1
                                ? true
                                : false;

                        $shipping_custom_label_4 = !empty($custom_labels['shipping']['custom_field_4'])
                            ? $custom_labels['shipping']['custom_field_4']
                            : '';

                        $is_shipping_custom_field_4_required =
                            !empty($custom_labels['shipping']['is_custom_field_4_required']) &&
                            $custom_labels['shipping']['is_custom_field_4_required'] == 1
                                ? true
                                : false;

                        $shipping_custom_label_5 = !empty($custom_labels['shipping']['custom_field_5'])
                            ? $custom_labels['shipping']['custom_field_5']
                            : '';

                        $is_shipping_custom_field_5_required =
                            !empty($custom_labels['shipping']['is_custom_field_5_required']) &&
                            $custom_labels['shipping']['is_custom_field_5_required'] == 1
                                ? true
                                : false;
                    @endphp

                    @if (!empty($shipping_custom_label_1))
                        @php
                            $label_1 = $shipping_custom_label_1 . ':';
                            if ($is_shipping_custom_field_1_required) {
                                $label_1 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_1', $label_1) !!}
                                {!! Form::text(
                                    'shipping_custom_field_1',
                                    !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_1'])
                                        ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_1']
                                        : null,
                                    [
                                        'class' => 'form-control',
                                        'placeholder' => $shipping_custom_label_1,
                                        'required' => $is_shipping_custom_field_1_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($shipping_custom_label_2))
                        @php
                            $label_2 = $shipping_custom_label_2 . ':';
                            if ($is_shipping_custom_field_2_required) {
                                $label_2 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_2', $label_2) !!}
                                {!! Form::text(
                                    'shipping_custom_field_2',
                                    !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_2'])
                                        ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_2']
                                        : null,
                                    [
                                        'class' => 'form-control',
                                        'placeholder' => $shipping_custom_label_2,
                                        'required' => $is_shipping_custom_field_2_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($shipping_custom_label_3))
                        @php
                            $label_3 = $shipping_custom_label_3 . ':';
                            if ($is_shipping_custom_field_3_required) {
                                $label_3 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_3', $label_3) !!}
                                {!! Form::text(
                                    'shipping_custom_field_3',
                                    !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_3'])
                                        ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_3']
                                        : null,
                                    [
                                        'class' => 'form-control',
                                        'placeholder' => $shipping_custom_label_3,
                                        'required' => $is_shipping_custom_field_3_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($shipping_custom_label_4))
                        @php
                            $label_4 = $shipping_custom_label_4 . ':';
                            if ($is_shipping_custom_field_4_required) {
                                $label_4 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_4', $label_4) !!}
                                {!! Form::text(
                                    'shipping_custom_field_4',
                                    !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_4'])
                                        ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_4']
                                        : null,
                                    [
                                        'class' => 'form-control',
                                        'placeholder' => $shipping_custom_label_4,
                                        'required' => $is_shipping_custom_field_4_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    @endif
                    @if (!empty($shipping_custom_label_5))
                        @php
                            $label_5 = $shipping_custom_label_5 . ':';
                            if ($is_shipping_custom_field_5_required) {
                                $label_5 .= '*';
                            }
                        @endphp

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('shipping_custom_field_5', $label_5) !!}
                                {!! Form::text(
                                    'shipping_custom_field_5',
                                    !empty($walk_in_customer['shipping_custom_field_details']['shipping_custom_field_5'])
                                        ? $walk_in_customer['shipping_custom_field_details']['shipping_custom_field_5']
                                        : null,
                                    [
                                        'class' => 'form-control',
                                        'placeholder' => $shipping_custom_label_5,
                                        'required' => $is_shipping_custom_field_5_required,
                                    ],
                                ) !!}
                            </div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('shipping_documents', __('lang_v1.shipping_documents') . ':') !!}
                            {!! Form::file('shipping_documents[]', [
                                'id' => 'shipping_documents',
                                'multiple',
                                'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types'))),
                            ]) !!}
                            <p class="help-block">
                                @lang('purchase.max_file_size', ['size' => config('constants.document_size_limit') / 1000000])
                                @includeIf('components.document_help_text')
                            </p>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-12 text-center">
                        <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white"
                            id="toggle_additional_expense"> <i class="fas fa-plus"></i> @lang('lang_v1.add_additional_expenses') <i
                                class="fas fa-chevron-down"></i></button>
                    </div>
                    <div class="col-md-8 col-md-offset-4" id="additional_expenses_div" style="display: none;">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.additional_expense_name')</th>
                                    <th>@lang('sale.amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        {!! Form::text('additional_expense_key_1', null, [
                                            'class' => 'form-control',
                                            'id' => 'additional_expense_key_1',
                                        ]) !!}
                                    </td>
                                    <td>
                                        {!! Form::text('additional_expense_value_1', 0, [
                                            'class' => 'form-control input_number',
                                            'id' => 'additional_expense_value_1',
                                        ]) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {!! Form::text('additional_expense_key_2', null, [
                                            'class' => 'form-control',
                                            'id' => 'additional_expense_key_2',
                                        ]) !!}
                                    </td>
                                    <td>
                                        {!! Form::text('additional_expense_value_2', 0, [
                                            'class' => 'form-control input_number',
                                            'id' => 'additional_expense_value_2',
                                        ]) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {!! Form::text('additional_expense_key_3', null, [
                                            'class' => 'form-control',
                                            'id' => 'additional_expense_key_3',
                                        ]) !!}
                                    </td>
                                    <td>
                                        {!! Form::text('additional_expense_value_3', 0, [
                                            'class' => 'form-control input_number',
                                            'id' => 'additional_expense_value_3',
                                        ]) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {!! Form::text('additional_expense_key_4', null, [
                                            'class' => 'form-control',
                                            'id' => 'additional_expense_key_4',
                                        ]) !!}
                                    </td>
                                    <td>
                                        {!! Form::text('additional_expense_value_4', 0, [
                                            'class' => 'form-control input_number',
                                            'id' => 'additional_expense_value_4',
                                        ]) !!}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4 col-md-offset-8">
                        @if (!empty($pos_settings['amount_rounding_method']) && $pos_settings['amount_rounding_method'] > 0)
                            <small id="round_off"><br>(@lang('lang_v1.round_off'): <span id="round_off_text">0</span>)</small>
                            <br />
                            <input type="hidden" name="round_off_amount" id="round_off_amount" value=0>
                        @endif
                        <div><b>@lang('sale.total_payable'): </b>
                            <input type="hidden" name="final_total" id="final_total_input">
                            <span id="total_payable">0</span>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        @if (!empty($common_settings['is_enabled_export']) && $sale_type != 'sales_order')
            @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.export')])
                <div class="col-md-12 mb-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_export" class="form-check-input" id="is_export"
                            @if (!empty($walk_in_customer['is_export'])) checked @endif>
                        <label class="form-check-label" for="is_export">@lang('lang_v1.is_export')</label>
                    </div>
                </div>
                @php
                    $i = 1;
                @endphp
                @for ($i; $i <= 6; $i++)
                    <div class="col-md-4 export_div" @if (empty($walk_in_customer['is_export'])) style="display: none;" @endif>
                        <div class="form-group">
                            {!! Form::label('export_custom_field_' . $i, __('lang_v1.export_custom_field' . $i) . ':') !!}
                            {!! Form::text(
                                'export_custom_fields_info[' . 'export_custom_field_' . $i . ']',
                                !empty($walk_in_customer['export_custom_field_' . $i]) ? $walk_in_customer['export_custom_field_' . $i] : null,
                                [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.export_custom_field' . $i),
                                    'id' => 'export_custom_field_' . $i,
                                ],
                            ) !!}
                        </div>
                    </div>
                @endfor
            @endcomponent
        @endif
        @php
            $is_enabled_download_pdf = config('constants.enable_download_pdf');
            $payment_body_id = 'payment_rows_div';
            if ($is_enabled_download_pdf) {
                $payment_body_id = '';
            }
        @endphp
        @if (
            (empty($status) || !in_array($status, ['quotation', 'draft']) || $is_enabled_download_pdf) &&
                $sale_type != 'sales_order')
            @can('sell.payments')
                @component('components.widget', [
                    'class' => 'box-solid',
                    'id' => $payment_body_id,
                    'title' => __('purchase.add_payment'),
                ])
                    @if ($is_enabled_download_pdf)
                        <div class="well row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('prefer_payment_method', __('lang_v1.prefer_payment_method') . ':') !!}
                                    @show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fas fa-money-bill-alt"></i>
                                        </span>
                                        {!! Form::select('prefer_payment_method', $payment_types, 'cash', [
                                            'class' => 'form-control',
                                            'style' => 'width:100%;',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('prefer_payment_account', __('lang_v1.prefer_payment_account') . ':') !!}
                                    @show_tooltip(__('lang_v1.this_will_be_shown_in_pdf'))
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fas fa-money-bill-alt"></i>
                                        </span>
                                        {!! Form::select('prefer_payment_account', $accounts, null, [
                                            'class' => 'form-control',
                                            'style' => 'width:100%;',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (empty($status) || !in_array($status, ['quotation', 'draft']))
                        <div class="payment_row" @if ($is_enabled_download_pdf) id="payment_rows_div" @endif>
                            <div class="row">
                                <div class="col-md-12 mb-12">
                                    <strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
                                    {!! Form::hidden('advance_balance', null, [
                                        'id' => 'advance_balance',
                                        'data-error-msg' => __('lang_v1.required_advance_balance_not_available'),
                                    ]) !!}
                                </div>
                            </div>
                            @include('sale_pos.partials.payment_row_form', [
                                'row_index' => 0,
                                'show_date' => true,
                                'show_denomination' => true,
                            ])
                        </div>
                        <div class="payment_row">
                            <div class="row">
                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.change_return'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold change_return_span">0</span>
                                    {!! Form::hidden('change_return', $change_return['amount'], [
                                        'class' => 'form-control change_return input_number',
                                        'required',
                                        'id' => 'change_return',
                                    ]) !!}
                                    <!-- <span class="lead text-bold total_quantity">0</span> -->
                                    @if (!empty($change_return['id']))
                                        <input type="hidden" name="change_return_id" value="{{ $change_return['id'] }}">
                                    @endif
                                </div>
                            </div>
                            <div class="row hide payment_row" id="change_return_payment_data">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('change_return_method', __('lang_v1.change_return_payment_method') . ':*') !!}
                                        <div class="input-group">
                                            <span class="input-group-addon">
                                                <i class="fas fa-money-bill-alt"></i>
                                            </span>
                                            @php
                                                $_payment_method =
                                                    empty($change_return['method']) &&
                                                    array_key_exists('cash', $payment_types)
                                                        ? 'cash'
                                                        : $change_return['method'];

                                                $_payment_types = $payment_types;
                                                if (isset($_payment_types['advance'])) {
                                                    unset($_payment_types['advance']);
                                                }
                                            @endphp
                                            {!! Form::select('payment[change_return][method]', $_payment_types, $_payment_method, [
                                                'class' => 'form-control col-md-12 payment_types_dropdown',
                                                'id' => 'change_return_method',
                                                'style' => 'width:100%;',
                                            ]) !!}
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($accounts))
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('change_return_account', __('lang_v1.change_return_payment_account') . ':') !!}
                                            <div class="input-group">
                                                <span class="input-group-addon">
                                                    <i class="fas fa-money-bill-alt"></i>
                                                </span>
                                                {!! Form::select(
                                                    'payment[change_return][account_id]',
                                                    $accounts,
                                                    !empty($change_return['account_id']) ? $change_return['account_id'] : '',
                                                    ['class' => 'form-control select2', 'id' => 'change_return_account', 'style' => 'width:100%;'],
                                                ) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @include('sale_pos.partials.payment_type_details', [
                                    'payment_line' => $change_return,
                                    'row_index' => 'change_return',
                                ])
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span
                                            class="balance_due">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcomponent
            @endcan
        @endif

        <div class="row">
            <div class="col-sm-12 text-center tw-mt-4">
                <button type="submit" id="save-sell"
                    class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
            </div>
        </div>

        @if (empty($pos_settings['disable_recurring_invoice']))
            @include('sale_pos.partials.recurring_invoice_modal')
        @endif

        {!! Form::close() !!}
    </section>

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>
    <!-- /.content -->
    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- quick product modal -->
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

    <div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>


    @include('sale_pos.partials.configure_search_modal')

@stop

@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>

    <!-- Call restaurant module if defined -->
    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            $('#status').change(function() {
                if ($(this).val() == 'final') {
                    $('#payment_rows_div').removeClass('hide');
                } else {
                    $('#payment_rows_div').addClass('hide');
                }
            });
            $('.paid_on').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $('#shipping_documents').fileinput({
                showUpload: false,
                showPreview: false,
                browseLabel: LANG.file_browse_label,
                removeLabel: LANG.remove,
            });

            $(document).on('change', '#prefer_payment_method', function(e) {
                var default_accounts = $('select#select_location_id').length ?
                    $('select#select_location_id')
                    .find(':selected')
                    .data('default_payment_accounts') : $('#location_id').data('default_payment_accounts');
                var payment_type = $(this).val();
                if (payment_type) {
                    var default_account = default_accounts && default_accounts[payment_type]['account'] ?
                        default_accounts[payment_type]['account'] : '';
                    var account_dropdown = $('select#prefer_payment_account');
                    if (account_dropdown.length && default_accounts) {
                        account_dropdown.val(default_account);
                        account_dropdown.change();
                    }
                }
            });

            function setPreferredPaymentMethodDropdown() {
                var payment_settings = $('#location_id').data('default_payment_accounts');
                payment_settings = payment_settings ? payment_settings : [];
                enabled_payment_types = [];
                for (var key in payment_settings) {
                    if (payment_settings[key] && payment_settings[key]['is_enabled']) {
                        enabled_payment_types.push(key);
                    }
                }
                if (enabled_payment_types.length) {
                    $("#prefer_payment_method > option").each(function() {
                        if (enabled_payment_types.indexOf($(this).val()) != -1) {
                            $(this).removeClass('hide');
                        } else {
                            $(this).addClass('hide');
                        }
                    });
                }
            }

            setPreferredPaymentMethodDropdown();

            $('#is_export').on('change', function() {
                if ($(this).is(':checked')) {
                    $('div.export_div').show();
                } else {
                    $('div.export_div').hide();
                }
            });

            if ($('.payment_types_dropdown').length) {
                $('.payment_types_dropdown').change();
            }

        });
        $(document).ready(function() {
            function updateRowButtons() {
                $('.jar-row .add-jar-row, .packet-row .add-packet-row').hide();
                $('.jar-row:last .add-jar-row, .packet-row:last .add-packet-row').show();
                $('.jar-row .remove-jar-row').toggle($('.jar-row').length > 1);
                $('.packet-row .remove-packet-row').toggle($('.packet-row').length > 1);
            }

            // Function to update all button states
            function updateAllButtons() {
                updateProductGroupButtons();
                updateJarButtons();
                updatePacketButtons();
            }

            // Update product group buttons visibility
            function updateProductGroupButtons() {
                $('.remove-product-group').toggle($('.product-group').length > 1);
            }

            // Update jar buttons visibility within each product group
            function updateJarButtons() {
                $('.product-group').each(function() {
                    const $jarRows = $(this).find('.jar-row');
                    $jarRows.find('.add-jar-row').hide();
                    $jarRows.last().find('.add-jar-row').show();
                    $jarRows.find('.remove-jar-row').toggle($jarRows.length > 1);
                });
            }

            // Update packet buttons visibility within each product group
            function updatePacketButtons() {
                $('.product-group').each(function() {
                    const $packetRows = $(this).find('.packet-row');
                    $packetRows.find('.add-packet-row').hide();
                    $packetRows.last().find('.add-packet-row').show();
                    $packetRows.find('.remove-packet-row').toggle($packetRows.length > 1);
                });
            }

            // Add new product group
            $(document).on('click', '.add-product-group', function() {
                const $lastGroup = $('.product-group:last');
                const newGroup = $lastGroup.clone(true);
                const newIndex = $('.product-group').length + 1;

                // Reset all inputs and selects
                newGroup.find('input').val('');
                newGroup.find('select').prop('selectedIndex', 0);

                // Remove any existing stock info
                newGroup.find('.jar-stock-info, .packet-stock-info').remove();

                // Update IDs for all elements in the new group
                newGroup.find('[id]').each(function() {
                    const oldId = $(this).attr('id');
                    const newId = oldId.replace(/\_\d+$/, `_${newIndex}`);
                    $(this).attr('id', newId);
                });

                // Keep only one jar row and one packet row in the new group
                newGroup.find('.jar-row:not(:first)').remove();
                newGroup.find('.packet-row:not(:first)').remove();

                // Append the new group and update buttons
                $('#product-groups-container').append(newGroup);
                updateAllButtons();
                updateGrandTotal();
            });

            // Remove product group
            $(document).on('click', '.remove-product-group', function() {
                if ($('.product-group').length > 1) {
                    $(this).closest('.product-group').remove();
                    updateAllButtons();
                    updateGrandTotal();
                }
            });

            function getGroupTemperature($element) {
                return $element.closest('.product-group').find('select[name="product_temperature[]"]').val();
            }

            function updateGrandTotal() {
                let grandTotal = 0;
                $('.product-group').each(function() {
                    $(this).find('.jar-row').each(function() {
                        const value = parseFloat($(this).find('input[name="jar_value[]"]').val()) ||
                            0;
                        const price = parseFloat($(this).find('input[name="jar_price[]"]').val()) ||
                            0;
                        grandTotal += value * price;
                    });
                    $(this).find('.packet-row').each(function() {
                        const value = parseFloat($(this).find('input[name="packet_value[]"]')
                            .val()) || 0;
                        const price = parseFloat($(this).find('input[name="packet_price[]"]')
                            .val()) || 0;
                        grandTotal += value * price;
                    });
                });
                $('#grand_total').val(grandTotal.toFixed(2));
            }

            function validateAndUpdateStock($input) {
                var $row = $input.closest('.jar-row, .packet-row');
                var type = $row.hasClass('jar-row') ? 'jar' : 'packet';
                var $stockInfo = $row.find(`.${type}-stock-info`);
                var inputValue = parseFloat($input.val()) || 0;

                // Retrieve original stock
                var originalStock = parseInt($stockInfo.data('original-stock')) ||
                    parseInt($stockInfo.text().replace('Available Stock: ', ''));

                if (inputValue > originalStock) {
                    alert(`Cannot exceed available stock of ${originalStock}`);
                    $input.val(originalStock);
                    inputValue = originalStock;
                }

                // Update available stock display
                var updatedStock = originalStock - inputValue;
                $stockInfo.text(`Available Stock: ${updatedStock}`)
                    .data('original-stock', originalStock);

                updateGrandTotal();
            }

            // Jar row handling
            $(document).on('click', '.add-jar-row', function() {
                const $container = $(this).closest('.product-group').find('.jar-container');
                const $lastRow = $container.find('.jar-row:last');
                const newRow = $lastRow.clone(true);
                const rowIndex = $container.find('.jar-row').length + 1;

                // Reset inputs and update IDs
                newRow.find('input').val('');
                newRow.find('select').prop('selectedIndex', 0);
                newRow.find('.jar-stock-info').remove();

                newRow.find('[id]').each(function() {
                    const oldId = $(this).attr('id');
                    const newId = oldId.replace(/\_\d+$/, `_${rowIndex}`);
                    $(this).attr('id', newId);
                });

                $container.append(newRow);
                updateAllButtons();
                updateGrandTotal();
            });

            // Your existing packet row handling
            $(document).on('click', '.add-packet-row', function() {
                const $container = $(this).closest('.product-group').find('.packet-container');
                const $lastRow = $container.find('.packet-row:last');
                const newRow = $lastRow.clone(true);
                const rowIndex = $container.find('.packet-row').length + 1;

                // Reset inputs and update IDs
                newRow.find('input').val('');
                newRow.find('select').prop('selectedIndex', 0);
                newRow.find('.packet-stock-info').remove();

                newRow.find('[id]').each(function() {
                    const oldId = $(this).attr('id');
                    const newId = oldId.replace(/\_\d+$/, `_${rowIndex}`);
                    $(this).attr('id', newId);
                });

                $container.append(newRow);
                updateAllButtons();
                updateGrandTotal();
            });

            // Remove jar row
            $(document).on('click', '.remove-jar-row', function() {
                const $container = $(this).closest('.jar-container');
                if ($container.find('.jar-row').length > 1) {
                    $(this).closest('.jar-row').remove();
                    updateAllButtons();
                    updateGrandTotal();
                }
            });

            // Remove packet row
            $(document).on('click', '.remove-packet-row', function() {
                const $container = $(this).closest('.packet-container');
                if ($container.find('.packet-row').length > 1) {
                    $(this).closest('.packet-row').remove();
                    updateAllButtons();
                    updateGrandTotal();
                }
            });


            // Event listeners for value inputs
            $(document).on('input', 'input[name="jar_value[]"], input[name="packet_value[]"]', function() {
                validateAndUpdateStock($(this));
            });

            // Event listeners for price inputs to recalculate grand total
            $(document).on('input', 'input[name="jar_price[]"], input[name="packet_price[]"]', function() {
                updateGrandTotal();
            });

            // Stock and price fetching for dynamically added rows
            $(document).on('change', 'select[name="jar_type[]"], select[name="packet_type[]"]', function() {
                var $select = $(this);
                var type = $select.attr('name').includes('jar_type') ? 'jar' : 'packet';
                var selectedValue = $select.val();
                var $row = $select.closest(`.${type}-row`);
                var rowNumber = $row.index() + 1;

                // Get the temperature value from the same product group
                var temperature = getGroupTemperature($select);

                // Don't proceed if temperature isn't selected
                if (!temperature) {
                    alert('Please select a temperature first');
                    $select.val(''); // Reset the selection
                    return;
                }

                if (selectedValue && selectedValue !== "") {
                    $.ajax({
                        url: '/get-stock-and-price',
                        method: 'GET',
                        data: {
                            value: selectedValue,
                            temperature: temperature
                        },
                        success: function(response) {
                            if (response.error) {
                                alert(response.error);
                                $select.val('');
                                return;
                            }

                            // Set the price in the price field
                            $row.find(`#${type}_price_${rowNumber}`).val(0);

                            // Remove any existing stock information
                            $row.find(`.${type}-stock-info`).remove();

                            // Add available stock information below the type field
                            var $stockInfo = $(`<small class="${type}-stock-info text-muted">
                        Available Stock: ${response.total_stock}
                    </small>`);

                            // Store original stock as a data attribute
                            $stockInfo.data('original-stock', response.total_stock);

                            $select.after($stockInfo);

                            // Reset previous value
                            $row.find(`input[name="${type}_value[]"]`).val('').data(
                                'previous-value', 0);

                            // Update grand total
                            updateGrandTotal();
                        },
                        error: function(xhr) {
                            console.error('Error fetching stock and price:', xhr.responseText);
                            alert('Error fetching stock information');
                            $select.val('');
                        }
                    });
                }
            });

            // Add validation for temperature selection
            $(document).on('change', 'select[name="product_temperature[]"]', function() {
                var $productGroup = $(this).closest('.product-group');

                // Reset jar and packet selections when temperature changes
                $productGroup.find('select[name="jar_type[]"], select[name="packet_type[]"]').val('');
                $productGroup.find('.jar-stock-info, .packet-stock-info').remove();

                // Reset all values
                $productGroup.find('input[name="jar_value[]"], input[name="packet_value[]"]').val('');
                updateGrandTotal();
            });

            // Initial setup
            updateRowButtons();
            updateGrandTotal();
        });
    </script>
@endsection
