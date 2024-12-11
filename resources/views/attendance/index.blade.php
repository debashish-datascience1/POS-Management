@extends('layouts.app')
@section('title', __('Attendance'))
@section('content')
<section class="content-header">
    <h1>@lang('Attendance')
        <small>@lang('Manage Attendance')</small>
    </h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Attendance')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('attendance.create') }}">
                   <i class="fa fa-plus"></i> @lang('Add Attendance')</a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="attendance_table">
                <thead>
                    <tr>
                        <th>@lang('Employee Name')</th>
                        <th>@lang('Date')</th>
                        <th>@lang('Check-in Time')</th>
                        <th>@lang('Check-out Time')</th>
                        <th>@lang('Leave Type')</th>
                        <th>@lang('Total Hours Worked')</th>
                        <th>@lang('Overtime Hours')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DataTable will populate here -->
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@endsection
@section('javascript')
<script>
    $(document).ready(function() {
        var attendance_table = $('#attendance_table').DataTable({
            serverSide: true,
            ajax: '{{ route("attendance.data") }}',
            columns: [
                { data: 'employee.name', name: 'employee.name' },
                { data: 'date', name: 'date' },
                { data: 'check_in_time', name: 'check_in_time' },
                { data: 'check_out_time', name: 'check_out_time' },
                { data: 'leave_type', name: 'leave_type' },
                { data: 'total_hours_worked', name: 'total_hours_worked' },
                { data: 'overtime_hours', name: 'overtime_hours' },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        $(document).on('click', '.delete-attendance', function(e) {
            e.preventDefault();
            var href = $(this).attr('data-href');
            
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete,
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
                                attendance_table.ajax.reload();
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
