@extends('layouts.app')
<<<<<<< HEAD
@section('content')
<div class="container">
    <h1>Final Products</h1>
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <a href="{{ route('final-product.create') }}" class="btn btn-primary mb-3">Add New Final Product</a>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Sum</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($finalProducts as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>{{ $product->product_name }}</td>
                <td>{{ $product->description }}</td>
                <td>{{ $product->quantity }}</td>
                <td>{{ number_format($product->sum, 2) }}</td>
                <td>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('final-product.edit', $product->id) }}" 
                           class="btn btn-warning btn-sm w-100 d-flex align-items-center justify-content-center">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <form action="{{ route('final-product.destroy', $product->id) }}" 
                              method="POST" 
                              class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="btn btn-danger btn-sm w-100 d-flex align-items-center justify-content-center"
                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
@endpush
=======

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
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
