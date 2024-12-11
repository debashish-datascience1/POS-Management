<?php

namespace App\Http\Controllers;

use App\Attendance;
use App\Employee;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class AttendanceController extends Controller
{
    // Show all attendances
    public function index()
    {
        return view('attendance.index');
    }

    // Fetch data for DataTable
    public function getData()
    {
        $attendances = Attendance::with('employee')->get(); // Include employee details

        return DataTables::of($attendances)
            ->addColumn('action', function ($attendance) {
                return '<a href="' . route('attendance.edit', $attendance->id) . '" class="btn btn-xs btn-primary">Edit</a>
                        <a href="#" data-href="' . route('attendance.destroy', $attendance->id) . '" class="btn btn-xs btn-danger delete-attendance">Delete</a>';
            })
            ->make(true);
    }

    // Show create form
    public function create()
    {
        $employees = Employee::all();
        return view('attendance.create', compact('employees'));
    }

    // Store new attendance
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',
            'leave_type' => 'nullable|string',
            'total_hours_worked' => 'required|numeric',
            'overtime_hours' => 'nullable|numeric',
        ]);

        $attendance = new Attendance();
        $attendance->employee_id = $request->employee_id;
        $attendance->date = $request->date;
        $attendance->check_in_time = $request->check_in_time;
        $attendance->check_out_time = $request->check_out_time;
        $attendance->leave_type = $request->leave_type;
        $attendance->total_hours_worked = $request->total_hours_worked;
        $attendance->overtime_hours = $request->overtime_hours;
        $attendance->save();
 

        return redirect()->route('attendance.index')->with('success', 'Attendance added successfully!');
        dd($request::all());
        
    }

    // Show edit form
    public function edit($id)
    {
        $attendance = Attendance::findOrFail($id);
        $employees = Employee::all();
        return view('attendance.edit', compact('attendance', 'employees'));
    }

    // Update attendance
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',
            'leave_type' => 'nullable|string',
            'total_hours_worked' => 'required|numeric',
            'overtime_hours' => 'nullable|numeric',
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->employee_id = $request->employee_id;
        $attendance->date = $request->date;
        $attendance->check_in_time = $request->check_in_time;
        $attendance->check_out_time = $request->check_out_time;
        $attendance->leave_type = $request->leave_type;
        $attendance->total_hours_worked = $request->total_hours_worked;
        $attendance->overtime_hours = $request->overtime_hours;
        $attendance->save();

        return redirect()->route('attendance.index')->with('success', 'Attendance updated successfully!');
    }

    // Delete attendance
    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        return response()->json(['success' => true, 'msg' => 'Attendance deleted successfully!']);
    }
}
