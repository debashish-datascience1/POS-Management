@extends('layouts.app')

@section('title', __('employee_advance.employee_advance_list'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('Employee Advance List')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Employee Advance Table')</h3>
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary" href="{{ route('employee_advance.create') }}">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </a>
                    </div>
                </div>

                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="advances_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.id')</th>
                                    <th>@lang('lang_v1.user_name')</th> <!-- Change 'employee_name' to 'user_name' -->
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
                                    <!-- Ensure employee is loaded correctly -->
                                    <td>{{ $advance->user ? $advance->user->first_name . ' ' . $advance->user->last_name : 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($advance->date)->format('d-m-Y') }}</td>
                                    <td>{{ number_format($advance->balance, 2) }}</td>
                                    <td>{{ $advance->refund }}</td>
                                    <td>{{ $advance->refund_date ? \Carbon\Carbon::parse($advance->refund_date)->format('d-m-Y') : 'N/A' }}</td>
                                    <td>{{ number_format($advance->refund_amount, 2) }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">
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
                                                    <a href="#" class="delete_advance" data-href="{{ route('employee_advance.destroy', $advance->id) }}">
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
                </div>
            </div>
        </div>
    </div>
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
                { data: 'user_name', name: 'user_name' }, // This column will show employee name
                { data: 'date', name: 'date' },
                { data: 'balance', name: 'balance' },
                { data: 'refund', name: 'refund' },
                { data: 'refund_date', name: 'refund_date' },
                { data: 'refund_amount', name: 'refund_amount' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            processing: false,
            language: {
                processing: ""
            }
        });

        // Delete function
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
