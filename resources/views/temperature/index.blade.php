@extends('layouts.app')

@section('title', __('temperature.temperature_records'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1>@lang('lang_v1.temperature_records')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" 
                   href="{{action([\App\Http\Controllers\TemperatureController::class, 'create'])}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="temperature_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('Product Output')</th>
                        <th>@lang('lang_v1.temperature')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var temperature_table = $('#temperature_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{action([\App\Http\Controllers\TemperatureController::class, 'index'])}}',
            columns: [
                {data: 'date', name: 'date'},
                {data: 'product_output', name: 'product_output'},
                {data: 'temperature', name: 'temperature'},
                {data: 'quantity', name: 'quantity'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
        });

        // Handle Delete Action
        $(document).on('click', '.delete-temperature', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_temperature,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: href,
                        method: 'DELETE',
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                temperature_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Handle Edit Action
        $(document).on('click', '.edit-temperature', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            window.location.href = href;
        });
    });
</script>
@endsection