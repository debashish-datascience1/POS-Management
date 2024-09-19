@extends('layouts.app')
@section('title', __('lang_v1.packing'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.packing')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{ action([\App\Http\Controllers\ProductionController::class, 'createPacking']) }}" 
                    data-container=".packing_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="packing_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('lang_v1.product')</th>
                        <th>@lang('lang_v1.quantity')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    // Add your JavaScript code here for handling the packing functionality
</script>
@endsection