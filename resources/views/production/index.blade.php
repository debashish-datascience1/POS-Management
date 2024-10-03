@extends('layouts.app')

@section('title', __('lang_v1.production_unit'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.production_unit')
        <small>@lang('lang_v1.manage_production_units')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_production_units')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary"
                   href="{{ action([\App\Http\Controllers\ProductionController::class, 'create']) }}">
                   <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="production_table">
                <thead>
                    <tr>
                        <th>@lang('messages.name')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.product')</th>
                        <th>@lang('lang_v1.raw_materials')</th>
                        <th>@lang('lang_v1.total_quantity')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables will fill this -->
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align:right">Total:</th>
                        <th id="total_quantity"></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var production_table = $('#production_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/production/unit',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'date', name: 'date' },
                { data: 'location_name', name: 'location.name' },
                { data: 'products', name: 'product_id' },
                { data: 'raw_materials', name: 'raw_material' },
                { data: 'total_quantity', name: 'total_quantity' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var total = api.column(5).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                
                $(api.column(5).footer()).html(total.toFixed(2));
            }
        });

        $(document).on('click', '.delete_production_unit', function(e) {
            e.preventDefault();
            var href = $(this).attr('data-href');
            
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_production_unit,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if(result.success == true) {
                                toastr.success(result.msg);
                                production_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection