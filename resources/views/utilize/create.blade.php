@extends('layouts.app')
@section('title', __('lang_v1.add_utilized_material'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_utilized_material')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\UtilizeController::class, 'store']), 'method' => 'post', 'id' => 'utilized_material_add_form' ]) !!}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('name', __('lang_v1.material_name') . ':*') !!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.material_name')]); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('quantity', __('lang_v1.quantity') . ':*') !!}
                        {!! Form::number('quantity', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.quantity')]); !!}
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        {!! Form::label('date', __('lang_v1.date') . ':*') !!}
                        {!! Form::date('date', \Carbon\Carbon::now(), ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function(){
        $('#utilized_material_add_form').validate();
    });
</script>
@endsection