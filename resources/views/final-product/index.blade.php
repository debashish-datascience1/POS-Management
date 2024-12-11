@extends('layouts.app')

@section('title', __('lang_v1.final_product'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.final_product')
        <small>@lang('lang_v1.manage_final_product')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_final_product')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary"
                   href="{{ action([\App\Http\Controllers\FinalProductController::class, 'create']) }}">
                   <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="final_product_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('lang_v1.temperature')</th>
                        <th>@lang('lang_v1.product_temperature')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('lang_v1.mix')</th>
                        <th>@lang('lang_v1.total')</th>
                        <th>@lang('lang_v1.grand_total')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTables will fill this -->
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var final_product_table = $('#final_product_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/production/final-product',
        columns: [
            { data: 'date', name: 'date' },
            { data: 'temperature', name: 'temperature' },
            { data: 'product_temperature', name: 'product_temperature' },
            { data: 'quantity', name: 'quantity' },
            { data: 'mix', name: 'mix' },
            { data: 'total', name: 'total' },
            { data: 'grand_total', name: 'grand_total' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        createdRow: function(row, data, dataIndex) {
            // Add vertical-align middle to all cells
            $('td', row).css('vertical-align', 'middle');
        }
    });

    $(document).on('click', '.delete_final_product_button', function(e) {
        e.preventDefault();
        var url = $(this).data('href');
        
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_final_product,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    method: "DELETE",
                    url: url,
                    dataType: "json",
                    success: function(result) {
                        if(result.success == true) {
                            toastr.success(result.msg);
                            final_product_table.ajax.reload();
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

<style>
    #final_product_table td {
        white-space: pre-line;
    }
</style>
@endsection