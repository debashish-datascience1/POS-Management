@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Add New Attendance</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('attendance.index') }}"> Back</a>
            </div>
        </div>
    </div>
   
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
   
    <form action="{{ route('attendance.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Employee Name:</strong>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Select Year:</strong>
                    <select name="select_year" id="select_year" class="form-control" required>
                        <option value="">--Select Year--</option>
                        @foreach(range(date('Y'), 2000) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <strong>Select Month:</strong>
                    <select name="select_month" id="select_month" class="form-control" required>
                        <option value="">--Select Month--</option>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}">{{ DateTime::createFromFormat('!m', $month)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12 text-center">
                <button type="button" class="btn btn-primary" onclick="showDates()">Filter</button>
            </div>
        </div>

        <div id="datesContainer" class="mt-4"></div>
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-success mt-3" id="saveAttendance" style="display:none;">Save Attendance</button>
        </div>
    </form>
</div>

<script>
    function showDates() {
        const employeeSelect = document.getElementById('user_id');
        const selectedEmployee = employeeSelect.value;
        const monthSelect = document.getElementById('select_month');
        const selectedMonth = monthSelect.value;
        const yearSelect = document.getElementById('select_year');
        const selectedYear = yearSelect.value;
        const datesContainer = document.getElementById('datesContainer');
        const saveButton = document.getElementById('saveAttendance');
        datesContainer.innerHTML = '';  // Clear the container each time the button is clicked

        if (selectedEmployee && selectedMonth && selectedYear) {
            const daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();

            for (let day = 1; day <= daysInMonth; day++) {
                // Check if the day is a weekend (Saturday or Sunday)
                const currentDate = new Date(selectedYear, selectedMonth - 1, day);
                const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;

                const dateDiv = document.createElement('div');
                dateDiv.className = 'row mb-2';

                const dateLabel = document.createElement('label');
                dateLabel.className = 'col-md-2 col-form-label';
                dateLabel.innerText = `Date: ${day}/${selectedMonth}/${selectedYear}`;

                // Creating a dropdown for status
                const statusSelect = document.createElement('select');
                statusSelect.name = `status[${day}]`; 
                statusSelect.className = 'form-select col-md-4 ms-2';
                
                // Define status options
                const statusOptions = isWeekend 
                    ? ['Weekend'] 
                    : ['Present', 'Absent', 'Half Day', 'Leave'];

                statusOptions.forEach(status => {
                    const option = document.createElement('option');
                    option.value = status;
                    option.innerText = status;
                    
                    // If it's a weekend, automatically select 'Weekend'
                    if (isWeekend && status === 'Weekend') {
                        option.selected = true;
                    }
                    
                    statusSelect.appendChild(option);
                });

                // Appending the elements to the container
                dateDiv.appendChild(dateLabel);
                dateDiv.appendChild(statusSelect);
                datesContainer.appendChild(dateDiv);
            }

            // Show save button
            saveButton.style.display = 'block';
        } else {
            alert('Please select Employee, Month, and Year');
        }
    }
</script>
@endsection