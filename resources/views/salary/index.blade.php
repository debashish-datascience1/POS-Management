@extends('layouts.app')
@section('title', __('lang_v1.salary'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.salary')
        <small>@lang('lang_v1.manage_salary')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_salaries')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('salaries.create') }}">
                   <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="salary_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.employee')</th>
                        <th>@lang('lang_v1.salary_date')</th>
                        <th>@lang('lang_v1.basic_salary')</th>
                        <th>@lang('lang_v1.deduction')</th>
                        <th>@lang('lang_v1.tax_deduction')</th>
                        <th>@lang('lang_v1.net_salary')</th>
                        <th>@lang('lang_v1.bank_account_number')</th>
                        <th>@lang('lang_v1.payment_mode')</th>
                        <th>@lang('lang_v1.salary_payment_mode')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salaries as $salary)
                    <tr>
                        <td>{{ $salary->first_name }} {{ $salary->last_name }}</td>
                        <td>{{ Carbon\Carbon::parse($salary->salary_date)->format('d-m-Y') }}</td>
                        <td>{{ $salary->basic_salary }}</td>
                        <td>{{ $salary->deduction }}</td>
                        <td>{{ $salary->tax_deduction }}</td>
                        <td>{{ $salary->net_salary }}</td>
                        <td>{{ $salary->bank_account_number }}</td>
                        <td>{{ $salary->payment_mode }}</td>
                        <td>{{ $salary->salary_payment_mode }}</td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">
                                    @lang('messages.action')
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                        <a href="{{ route('salaries.edit', $salary->id) }}">
                                            <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="delete_salary" 
                                           data-href="{{ route('salaries.destroy', $salary->id) }}">
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
    var salary_table = $('#salary_table').DataTable({
        serverSide: true,
        ajax: '{{ route("salaries.index") }}',
        columns: [
            { data: 'employee.name', name: 'employee.name' },
            { data: 'salary_date', name: 'salary_date' },
            { data: 'basic_salary', name: 'basic_salary' },
            { data: 'deduction', name: 'deduction' },
            { data: 'tax_deduction', name: 'tax_deduction' },
            { data: 'net_salary', name: 'net_salary' },
            { data: 'bank_account_number', name: 'bank_account_number' },
            { data: 'payment_mode', name: 'payment_mode' },
            { data: 'salary_payment_mode', name: 'salary_payment_mode' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    $(document).on('click', '.delete_salary', function(e) {
        e.preventDefault();
        var href = $(this).attr('data-href');
        
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_salary,
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
                            // Reload the table to reflect the changes
                            salary_table.ajax.reload(null, false); // false ensures current page is preserved
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
