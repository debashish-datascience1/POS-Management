<?php

namespace App\Http\Controllers;

use App\Attendance;
use App\User;
use Illuminate\Http\Request;
use DateTime; // If you want to use PHP's built-in DateTime


class AttendanceController extends Controller
{

    public function index()
{
    $attendances = Attendance::with('user')  // Changed from 'employee' to 'user'
        ->orderBy('select_year', 'desc')
        ->orderBy('select_month', 'desc')
        ->orderBy('user_id')
        ->orderBy('day', 'asc')
        ->get()
        ->groupBy(['select_year', 'select_month', 'user_id']);

    return view('attendance.index', compact('attendances'));
}
    public function create()
    {
        $employees = User::all();
        // dd($employees);
        return view('attendance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        // Validate the input
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'select_year' => 'required|integer|min:2000|max:' . date('Y'),
            'select_month' => 'required|integer|between:1,12',
            'status' => 'array', 
        ]);
    
        // Get the days in the selected month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $validatedData['select_month'], $validatedData['select_year']);
    
        // Prepare attendance records
        $attendanceRecords = [];
        
        if (isset($request->status) && is_array($request->status)) {
            foreach ($request->status as $day => $status) {
                // Normalize the status (convert to lowercase)
                $status = strtolower(trim($status));
    
                // Check if the day is a weekend
                $currentDate = new DateTime("{$validatedData['select_year']}-{$validatedData['select_month']}-{$day}");
                $isWeekend = in_array($currentDate->format('w'), ['0', '6']);
    
                // If the day is a weekend, force the status to 'weekend'
                if ($isWeekend) {
                    $status = 'weekend';
                }
    
                // Prepare the attendance record for this day
                $attendanceRecords[] = [
                    'user_id' => $validatedData['user_id'],
                    'select_year' => $validatedData['select_year'],
                    'select_month' => $validatedData['select_month'],
                    'day' => $day,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
    
            // First, check if attendance for this employee and month/year already exists
            Attendance::where([
                'user_id' => $validatedData['user_id'],
                'select_year' => $validatedData['select_year'],
                'select_month' => $validatedData['select_month']
            ])->delete();
    
            // Bulk insert the attendance records
            $newAttendances = Attendance::insert($attendanceRecords);
    
            // If AJAX request, return the new records in JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'msg' => 'Attendance recorded successfully.',
                    'attendances' => $attendanceRecords, // You could return a newly inserted record here
                ]);
            }
    
            return redirect()->route('attendance.index')
                ->with('success', 'Attendance recorded successfully.');
        }
    
        return back()->with('error', 'No attendance data to save.');
    }
    
    // Show the form for editing attendance
    public function edit($id)
    {
        // Get the attendance record you want to edit
        $attendance = Attendance::findOrFail($id);
    
        // Get the attendance data for the selected employee, year, and month
        $attendanceData = Attendance::where('user_id', $attendance->user_id)
                                    ->where('select_year', $attendance->select_year)
                                    ->where('select_month', $attendance->select_month)
                                    ->get();
    
        // Get all employees for the select input
        $employees = User::all(); // Adjust this if you have a specific model for employees
    
        // Pass attendance data and employee list to the view
        return view('attendance.edit', compact('attendance', 'attendanceData', 'employees'));
    }
    


    // Update the attendance data
    public function update(Request $request, $id)
    {
        // Validate incoming data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'select_year' => 'required|integer',
            'select_month' => 'required|integer',
            'status' => 'required|array', // Ensure the status is an array
        ]);

        // Find the attendance record to update
        $attendance = Attendance::findOrFail($id);

        // Update the attendance record
        $attendance->user_id = $request->user_id;
        $attendance->select_year = $request->select_year;
        $attendance->select_month = $request->select_month;
        $attendance->save();

        // Update the attendance status for each day
        foreach ($request->status as $day => $status) {
            // Find or create the attendance record for each day
            Attendance::updateOrCreate(
                [
                    'user_id' => $attendance->user_id,
                    'select_year' => $attendance->select_year,
                    'select_month' => $attendance->select_month,
                    'day' => $day,
                ],
                ['status' => $status]
            );
        }

        // Redirect back with success message
        return redirect()->route('attendance.index')->with('success', 'Attendance updated successfully.');
    }


    public function destroy(Attendance $attendance)
    {
        try {
            // Find all related attendance records
            $relatedAttendances = Attendance::where([
                'user_id' => $attendance->user_id,
                'select_year' => $attendance->select_year,
                'select_month' => $attendance->select_month
            ])->get();
    
            // Delete all related attendance records
            foreach ($relatedAttendances as $relatedAttendance) {
                $relatedAttendance->delete();
            }
    
            // Prepare success message
            $successMessage = "Attendance for {$attendance->employee->name} in " . 
                DateTime::createFromFormat('!m', $attendance->select_month)->format('F') . 
                " {$attendance->select_year} deleted successfully!";
    
            return response()->json([
                'success' => true,
                'msg' => $successMessage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => "Error deleting attendance: " . $e->getMessage()
            ], 500);
        }
    }
    

// Optional: Add a method to handle AJAX delete requests
public function ajaxDestroy($id)
{
    try {
        $attendance = Attendance::findOrFail($id);

        // Find all related attendance records
        $relatedAttendances = Attendance::where([
            'user_id' => $attendance->user_id,
            'select_year' => $attendance->select_year,
            'select_month' => $attendance->select_month
        ])->get();

        // Delete all related attendance records
        foreach ($relatedAttendances as $relatedAttendance) {
            $relatedAttendance->delete();
        }

        return response()->json([
            'success' => true,
            'msg' => "Attendance deleted successfully."
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'msg' => "Error deleting attendance: " . $e->getMessage()
        ], 500);
    }
}
}
