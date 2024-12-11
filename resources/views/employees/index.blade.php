@extends('layouts.app')
@section('title', __('lang_v1.employee'))
@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.employee')
        <small>@lang('lang_v1.manage_employee')</small>
    </h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_employees')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('employees.create') }}">
                   <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="employee_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('lang_v1.age')</th>
                        <th>@lang('lang_v1.id_prove')</th>
                        <th>@lang('lang_v1.phone')</th>
                        <th>@lang('lang_v1.gender')</th>
                        <th>@lang('lang_v1.address')</th>
                        <th>@lang('lang_v1.attached_file')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                    <tr>
                        <td>{{ $employee->name }}</td>
                        <td>{{ $employee->age }}</td>
                        <td>{{ $employee->idprove }}</td>
                        <td>{{ $employee->phone }}</td>
                        <td>{{ $employee->gender }}</td>
                        <td>{{ $employee->address }}</td>
                        <td>
                            @if($employee->file_url)
                                <a href="{{ Storage::url($employee->file_url) }}" target="_blank">@lang('lang_v1.view_file')</a>
                            @else
                                @lang('lang_v1.no_file')
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">
                                    @lang('messages.action')
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                        <a href="{{ route('employees.edit', $employee->id) }}">
                                            <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="delete_employee" 
                                           data-href="{{ route('employees.destroy', $employee->id) }}">
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
        var employee_table = $('#employee_table').DataTable({
            serverSide: true,
            ajax: '{{ route("employees.index") }}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'age', name: 'age' },
                { data: 'idprove', name: 'idprove' },
                { data: 'phone', name: 'phone' },
                { data: 'gender', name: 'gender' },
                { data: 'address', name: 'address' },
                { data: 'file_url', name: 'file_url', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            processing: false, // This removes the processing time indicator
            language: {
                processing: ""  // You can also clear the processing message if you want it to be empty
            }
        });

        $(document).on('click', '.delete_employee', function(e) {
            e.preventDefault();
            var href = $(this).attr('data-href');
            
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_employee,
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
                                employee_table.ajax.reload();
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