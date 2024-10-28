@extends('layouts.app')

@section('title', __('lang_v1.production_unit'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.production_unit')
        <small>@lang('lang_v1.all_production_units')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_production_units')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="utilize_table">
                <thead>
                    <tr>
                        <th>@lang('messages.name')</th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('business.location')</th>
                        <th>@lang('lang_v1.product')</th>
                        <th>@lang('lang_v1.raw_materials')</th>
                        <th>@lang('lang_v1.total_quantity')</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align:right">Total:</th>
                        <th id="total_quantity"></th>
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
        var utilize_table = $('#utilize_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/utilize',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'date', name: 'date' },
                { data: 'location_name', name: 'location.name' },
                { data: 'products', name: 'product_id' },
                { data: 'raw_materials', name: 'raw_material' },
                { data: 'total_quantity', name: 'total_quantity' }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var total = api.column(5).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                
                $(api.column(5).footer()).html(total.toFixed(2));
            }
        });
    });
</script>
@endsection