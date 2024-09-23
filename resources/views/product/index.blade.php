@extends('layouts.app')
@section('title', __('product.raw_material'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('product.raw_material')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('product.create')
            <div class="row">
                <div class="col-sm-12">
                    <a class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white pull-right" 
                       href="{{action([\App\Http\Controllers\ProductController::class, 'create'])}}?type=raw_material">
                        <i class="fa fa-plus"></i> @lang('product.add_new_raw_material')
                    </a>
                </div>
            </div>
            <br>
        @endcan

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="raw_materials_table">
                <thead>
                    <tr>
                        <th>@lang('product.raw_material_name')</th>
                        <th>@lang('product.unit')</th>
                        <th>@lang('product.brand')</th>
                        <th>@lang('business.business_locations')</th>
                        <th>@lang('product.alert_quantity')</th>
                        <th>@lang('product.manage_stock')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var raw_materials_table = $('#raw_materials_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/products?type=raw_material',
                dataSrc: function(json) {
                    if (json.debug_info) {
                        $('#total-raw-materials').text(json.debug_info.total_raw_materials);
                        $('#last-sql-query').text(json.debug_info.sql_query);
                        $('#sql-bindings').text(JSON.stringify(json.debug_info.sql_bindings));
                    }
                    return json.data;
                }
            },
            columns: [
                { data: 'name', name: 'products.name' },
                { data: 'unit', name: 'units.name' },
                { data: 'brand', name: 'brands.name' },
                { data: 'product_locations', name: 'product_locations' },
                { data: 'alert_quantity', name: 'products.alert_quantity' },
                { data: 'enable_stock', name: 'products.enable_stock' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        // Delete button click handler
        $('#raw_materials_table').on('click', '.delete_product_button', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_product,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        dataType: 'json',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                raw_materials_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error(LANG.something_went_wrong);
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
