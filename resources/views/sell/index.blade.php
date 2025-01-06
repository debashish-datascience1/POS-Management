@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('content')
<section class="content-header">
    <h1>@lang('sale.sells')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @can('sell.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{ route('sells.create') }}">
                        <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="sells_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('lang_v1.contact_no')</th>
                        <th>@lang('sale.total_items')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('lang_v1.shipping_status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
    $(document).ready(function() {
        var sells_table = $('#sells_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/sells',
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'customer_name', name: 'contacts.name' },
                { data: 'mobile', name: 'contacts.mobile' },
                { data: 'items_detail', name: 'items_detail' },
                { data: 'final_total', name: 'final_total' },
                { data: 'shipping_status', name: 'shipping_status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            "order": [[ 0, "desc" ]],  // Updated order index since action is now last
            createdRow: function(row, data, dataIndex) {
                $(row).find('td:not(:last-child)').addClass('clickable')
                    .on('click', function() {
                        window.location.href = '/sells/' + data.id;
                    });
            }
        });

        // Handle delete action
        $(document).on('click', '.delete-sell', function(e) {
            e.preventDefault();
            var sellId = $(this).data('sell-id');
            
            if (confirm("Are you sure you want to delete this sale?")) {
                $.ajax({
                    url: '/sells/' + sellId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        sells_table.ajax.reload();
                        toastr.success('Sale deleted successfully');
                    },
                    error: function(error) {
                        toastr.error('Error deleting sale');
                    }
                });
            }
        });
    });
</script>
@endsection