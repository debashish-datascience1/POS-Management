@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New Final Product</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('final-product.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" class="form-control" id="product_name" name="product_name" value="{{ old('product_name') }}" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" class="form-control" id="quantity" name="quantity" value="{{ old('quantity') }}" required min="0">
        </div>
        
        <div class="form-group">
            <label for="sum">Sum</label>
            <input type="number" step="0.01" class="form-control" id="sum" name="sum" value="{{ old('sum') }}" required min="0">
        </div>
        
        <button type="submit" class="btn btn-primary">Create Final Product</button>
        <a href="{{ route('final-product.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection