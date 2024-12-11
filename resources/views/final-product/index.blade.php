@extends('layouts.app')
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