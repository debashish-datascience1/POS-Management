@extends('layouts.app')
@section('title', 'Employee Advances')
@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.employee_advances')
        <small>@lang('lang_v1.manage_employee_advances')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_employee_advances')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('employee_advance.create') }}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="advances_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.employee_name')</th>
                        <th>@lang('lang_v1.advance_date')</th>
                        <th>@lang('lang_v1.balance')</th>
                        <th>@lang('lang_v1.refund')</th>
                        <th>@lang('lang_v1.refund_date')</th>
                        <th>@lang('lang_v1.refund_amount')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeAdvances as $advance)
                    <tr>
                        <td>{{ $advance->id }}</td>
                        <td>{{ $advance->employee ? $advance->employee->name : 'N/A' }}</td>
                        <td>{{ \Carbon\Carbon::parse($advance->date)->format('d M Y') }}</td>
                        <td>{{ number_format($advance->balance, 2) }}</td>
                        <td>{{ $advance->refund }}</td>
                        <td>{{ $advance->refund_date ? \Carbon\Carbon::parse($advance->refund_date)->format('d M Y') : 'N/A' }}</td>
                        <td>{{ $advance->refund_amount ? number_format($advance->refund_amount, 2) : 'N/A' }}</td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">
                                    @lang('messages.action')
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                        <a href="{{ route('employee_advance.edit', $advance->id) }}">
                                            <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="delete_advance" 
                                           data-href="{{ route('employee_advance.destroy', $advance->id) }}">
                                            <i class="fa fa-trash"></i> @lang('messages.delete')
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        var advances_table = $('#advances_table').DataTable({
            serverSide: true,
            ajax: '{{ route("employee_advance.index") }}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'employee_name', name: 'employee_name' },
                { data: 'date', name: 'date' },
                { data: 'balance', name: 'balance' },
                { data: 'refund', name: 'refund' },
                { data: 'refund_date', name: 'refund_date' },
                { data: 'refund_amount', name: 'refund_amount' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            processing: false, // Disable the processing indicator
            language: {
                processing: ""  // Clear the processing message
            }
        });

        $(document).on('click', '.delete_advance', function(e) {
            e.preventDefault();
            var href = $(this).attr('data-href');
            
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_advance,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        success: function(result) {
                            if(result.success == true) {
                                toastr.success(result.msg);
                                advances_table.ajax.reload();
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
