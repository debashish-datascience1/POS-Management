@extends('layouts.app')

@section('title', __('lang_v1.packing'))

@section('content')

<section class="content-header">
    <h1>@lang('lang_v1.packing')
        <small>@lang('lang_v1.manage_packing')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_packing')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary"
                   href="{{ action([\App\Http\Controllers\PackingController::class, 'create']) }}">
                   <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="packing_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('lang_v1.product')</th>
                        <th>@lang('lang_v1.product_output')</th>
                        <th>@lang('lang_v1.mix')</th>
                        <th>@lang('lang_v1.packing')</th>
                        <th>@lang('lang_v1.total')</th>
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
        var packing_table = $('#packing_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/production/packing',
            columns: [
                { data: 'date', name: 'date' },
                { data: 'product_id', name: 'product_id' },
                { data: 'product_output', name: 'product_output' },
                { data: 'mix', name: 'mix' },
                { data: 'packing', name: 'packing' },
                { data: 'total', name: 'total' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
        });

        $(document).on('click', '.delete_packing_button', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_packing,
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
                                packing_table.ajax.reload();
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