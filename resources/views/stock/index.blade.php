@extends('layouts.app')
@section('title', __('lang_v1.stocks'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.stocks')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.stocks')])
            <!-- Purchase History Table -->
            <div class="row">
                <div class="col-md-12">
                    <h4>@lang('lang_v1.purchase_history')</h4>
                    <table class="table table-bordered table-striped" id="stock_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.product_name')</th>
                                <th>@lang('lang_v1.date')</th>
                                <th>@lang('lang_v1.quantity')</th>
                                <th>@lang('lang_v1.unit_price')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <hr class="tw-my-4">

            <!-- Available Stock Table -->
            <div class="row">
                <div class="col-md-12">
                    <h4>@lang('lang_v1.available_stock')</h4>
                    <table class="table table-bordered table-striped" id="available_stock_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.product_name')</th>
                                <th>@lang('lang_v1.total_purchased')</th>
                                <th>@lang('lang_v1.used_in_production')</th>
                                <th>@lang('lang_v1.available_quantity')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($availableStocks as $stock)
                                <tr>
                                    <td>{{ $stock->product_name }}</td>
                                    <td>{{ number_format($stock->total_purchased, 2) }}</td>
                                    <td>{{ number_format($stock->total_used, 2) }}</td>
                                    <td>{{ number_format($stock->available_quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endcomponent
    </section>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize Purchase History table
            var stock_table = $('#stock_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                ajax: "{{ action([\App\Http\Controllers\StockController::class, 'index']) }}",
                columns: [
                    {
                        data: 'product_name',
                        name: 'products.name'
                    },
                    {
                        data: 'created_at',
                        name: 'purchase_lines.created_at'
                    },
                    {
                        data: 'quantity',
                        name: 'purchase_lines.quantity'
                    },
                    {
                        data: 'unit_price',
                        name: 'purchase_lines.purchase_price_inc_tax'
                    }
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#stock_table'));
                }
            });

            // Initialize Available Stock table
            $('#available_stock_table').DataTable({
                dom: 'Bfrtip',
                ordering: true,
                searching: true,
                pageLength: 10
            });
        });
    </script>
@endsection