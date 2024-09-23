<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'saveQuickProduct']), 'method' => 'post', 'id' => 'quick_add_product_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title" id="modalTitle">@lang('product.add_new_raw_material')</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('name', __('product.raw_material_name') . ':*') !!}
            {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('product.raw_material_name')]); !!}
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('unit_id', __('product.unit') . ':*') !!}
            <div class="input-group">
              {!! Form::select('unit_id', $units, session('business.default_unit'), ['class' => 'form-control select2', 'required']); !!}
              <span class="input-group-btn">
                <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
              </span>
            </div>
          </div>
        </div>

        <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
          <div class="form-group">
            {!! Form::label('brand_id', __('product.brand') . ':') !!}
            <div class="input-group">
              {!! Form::select('brand_id', $brands, null, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
              <span class="input-group-btn">
                <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
              </span>
            </div>
          </div>
        </div>

        <div class="clearfix"></div>

        @php
          $default_location = null;
          if(count($business_locations) == 1){
            $default_location = array_key_first($business_locations->toArray());
          }
        @endphp
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
            {!! Form::select('product_locations[]', $business_locations, $default_location, ['class' => 'form-control select2', 'multiple', 'id' => 'product_locations']); !!}
          </div>
        </div>

        <div class="col-sm-4">
          <div class="form-group">
            <br>
            <label>
              {!! Form::checkbox('enable_stock', 1, true, ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
            </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
          </div>
        </div>

        <div class="col-sm-4" id="alert_quantity_div">
          <div class="form-group">
            {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
            {!! Form::number('alert_quantity', null, ['class' => 'form-control', 'placeholder' => __('product.alert_quantity'), 'min' => '0', 'step' => '1']); !!}
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="submit_quick_product">@lang('messages.save')</button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    $("form#quick_add_product_form").validate({
      rules: {
        name: {
          required: true,
        },
        unit_id: {
          required: true,
        },
      },
      submitHandler: function (form) {
        var form = $("form#quick_add_product_form");
        var url = form.attr('action');
        form.find('button[type="submit"]').attr('disabled', true);
        $.ajax({
          method: "POST",
          url: url,
          dataType: 'json',
          data: $(form).serialize(),
          success: function(data){
            $('.quick_add_product_modal').modal('hide');
            if(data.success){
              toastr.success(data.msg);
              if (typeof get_purchase_entry_row !== 'undefined') {
                var selected_location = $('#location_id').val();
                var location_check = true;
                if (data.locations && selected_location && data.locations.indexOf(selected_location) == -1) {
                  location_check = false;
                }
                if (location_check) {
                  get_purchase_entry_row(data.product.id, 0);
                }
              }
              $(document).trigger({type: "quickProductAdded", 'product': data.product, 'variation': data.variation });
            } else {
              toastr.error(data.msg);
            }
          }
        });
        return false;
      }
    });
  });
</script>