@extends('layouts.app')

@section('title', __('lang_v1.attendance_summary'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.attendance_summary')</h1>
</section>

<section class="content">
    @php
    function getAttendanceStatusClass($dayStatus) {
        $dayStatus = strtolower(trim($dayStatus));

        $statusMap = [
            'present' => 'status-present',
            'absent' => 'status-absent',
            'half day' => 'status-half-day',
            'halfday' => 'status-half-day',
            'half_day' => 'status-half-day',
            'half' => 'status-half-day',
            'leave' => 'status-leave',
            'weekend' => 'status-weekend',
            'week-end' => 'status-weekend',
            'week end' => 'status-weekend'
        ];

        return $statusMap[$dayStatus] ?? 'status-unknown';
    }
    @endphp

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.all_attendance')</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        </div>

        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="attendance_summary_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.user_name')</th>
                            <th>@lang('lang_v1.attendance_details')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $year => $yearData)
                            @foreach ($yearData as $month => $monthData)
                                @foreach ($monthData as $employeeId => $employeeRecords)
                                    @php
                                        $firstRecord = $employeeRecords->first();
                                        
                                        $monthName = DateTime::createFromFormat('!m', $month)->format('F');
                                        
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                        $fullMonthAttendance = [];
                                        
                                        for ($day = 1; $day <= $daysInMonth; $day++) {
                                            $currentDate = DateTime::createFromFormat('!Y-m-d', "{$year}-{$month}-{$day}");
                                            $isWeekend = in_array($currentDate->format('w'), ['0', '6']);
                                            
                                            $dayRecord = $employeeRecords->first(function($record) use ($day) {
                                                return $record->day == $day;
                                            });
                                            
                                            $dayStatus = $dayRecord ? $dayRecord->status : ($isWeekend ? 'weekend' : 'unknown');
                                            
                                            $fullMonthAttendance[$day] = $dayStatus;
                                        }
                                    @endphp
                                
                                    <tr><td>
                                        {{ $firstRecord->user ? $firstRecord->user->first_name . ' ' . $firstRecord->user->last_name : 'N/A' }}
                                        <br>
                                        <small>{{ $monthName }} {{ $year }}</small>
                                    </td>
                                        <td>
                                            <div class="attendance-horizontal-grid">
                                                @foreach ($fullMonthAttendance as $day => $status)
                                                    @php
                                                        $statusClass = getAttendanceStatusClass($status);
                                                    @endphp
                                                    <div class="attendance-horizontal-day {{ $statusClass }}" 
                                                         title="{{ ucfirst($status) }} on {{ $day }}/{{ $monthName }}">
                                                        {{ $day }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('attendance.edit', $firstRecord->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa fa-pencil"></i> @lang('messages.edit')
                                            </a>
                                            <button data-href="{{ route('attendance.destroy', $firstRecord->id) }}" 
                                                    class="btn btn-danger btn-sm delete_attendance">
                                                <i class="fa fa-trash"></i> @lang('messages.delete')
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Attendance Status Legend -->
        <div class="box-footer">
            <div class="attendance-legend">
                <div class="legend-item">
                    <div class="legend-color status-present"></div>
                    <span>@lang('lang_v1.present')</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-absent"></div>
                    <span>@lang('lang_v1.absent')</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-half-day"></div>
                    <span>@lang('lang_v1.half_day')</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-leave"></div>
                    <span>@lang('lang_v1.leave')</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-weekend"></div>
                    <span>@lang('lang_v1.weekend')</span>
                </div>
            </div>
        </div>
    </div>

       
    </div>

    
</section>
@endsection

@section('css')
<style>
    .attendance-horizontal-grid {
        display: flex;
        flex-direction: row;
        gap: 5px;
        padding: 5px;
        background-color: #f4f6f9;
        border-radius: 8px;
        overflow-x: auto;
        white-space: nowrap;
        max-width: 100%;
    }

    .attendance-horizontal-day {
        min-width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        flex-shrink: 0;
    }

    .attendance-horizontal-day:hover {
        transform: scale(1.2);
        z-index: 10;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .status-present {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
    }

    .status-absent {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }

    .status-half-day {
        background: linear-gradient(135deg, #f39c12, #f1c40f);
    }

    .status-leave {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
    }

    .status-weekend {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .attendance-legend {
        display: flex;
        justify-content: space-around;
        margin-top: 20px;
    }

    .legend-item {
        display: flex;
        align-items: center;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .legend-item span {
        font-size: 14px;
        font-weight: bold;
    }
</style>
@endsection

@section('javascript')
<script>
   $(document).ready(function() {
    // Initialize DataTable
    var attendance_table = $('#attendance_summary_table').DataTable({
        serverSide: true,
        ajax: {
            url: "{{ route('attendance.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'user_name', name: 'user_name' }, // This column will show employee name
            { data: 'attendance_details', name: 'attendance_details', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    // Automatically refresh the table every 30 seconds
    setInterval(function() {
        attendance_table.ajax.reload(null, false);
    }, 30000); // 30 seconds

    // Delete attendance using AJAX
    $(document).on('click', '.delete_attendance', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        
        swal({
            title: "@lang('messages.sure')",
            text: "@lang('messages.delete_confirmation')",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    method: "DELETE",
                    url: href,
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(result) {
                        if(result.success) {
                            // Refresh the table without reloading the page
                            attendance_table.ajax.reload(null, false);

                            // Show success message
                            swal({
                                title: "@lang('messages.deleted')", 
                                text: result.msg, 
                                icon: "success",
                                buttons: false,
                                timer: 2000 // Close the success message after 2 seconds
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle AJAX errors
                        swal({
                            title: "@lang('messages.error')",
                            text: xhr.responseJSON.msg || "@lang('messages.error_occurred')", 
                            icon: "error",
                            buttons: false,
                            timer: 2000 // Close the error message after 2 seconds
                        });
                    }
                });
            }
        });
    });
});

</script>
@endsection
