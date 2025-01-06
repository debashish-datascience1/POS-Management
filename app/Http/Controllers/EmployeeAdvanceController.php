<?php

namespace App\Http\Controllers;

use App\User;  // Use User model instead of Employee model
use App\EmployeeAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeAdvanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:employee.view')->only(['index', 'show']);
        $this->middleware('can:employee.create')->only(['create', 'store']);
        $this->middleware('can:employee.edit')->only(['edit', 'update']);
        $this->middleware('can:employee.delete')->only('destroy');
    }

    // Show all employee advances
    public function index(Request $request)
{
    // Get all employee advances with user data (first_name, last_name)
    $employeeAdvances = EmployeeAdvance::with('user')->get();

    if ($request->ajax()) {
        return datatables()->of($employeeAdvances)
            ->addColumn('user_name', function($row) {
                // Combine first_name and last_name to create full name
                return $row->user ? $row->user->first_name . ' ' . $row->user->last_name : 'N/A';
            })
            ->make(true);
    }

    return view('employee_advance.index', compact('employeeAdvances'));
}

    

    // Show form to create a new employee advance
    public function create()
    {
        // Use User model instead of Employee model
        $employees = User::select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"), 'username')->get();
        
        return view('employee_advance.create', compact('employees'));
    }

    // Store a new employee advance
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',  // Ensure employee exists (use User model)
            'date' => 'required|date',
            'refund' => 'nullable|string',
            'refund_date' => 'nullable|date',
            'refund_amount' => 'nullable|numeric',
            'balance' => 'required|numeric',
        ]);

        // Store the new advance
        EmployeeAdvance::create($request->all());

        return redirect()->route('employee_advance.index')
            ->with('success', 'Employee advance added successfully');
    }

    // Show the form to edit an existing advance
    public function edit(EmployeeAdvance $employeeAdvance)
    {
        // Use User model instead of Employee model
        $employees = User::select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"), 'username')->get();
        
        return view('employee_advance.edit', compact('employeeAdvance', 'employees'));
    }

    // Update an existing employee advance
    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id', // Validate with User model
            'date' => 'required|date',
            'refund' => 'nullable|string',
            'refund_date' => 'nullable|date',
            'refund_amount' => 'nullable|numeric',
            'balance' => 'required|numeric',
        ]);

        $employeeAdvance->update($request->all());

        return redirect()->route('employee_advance.index')
            ->with('success', 'Employee advance updated successfully');
    }

    // Delete an employee advance
    public function destroy(EmployeeAdvance $employeeAdvance)
    {
        $employeeAdvance->delete();
        return back()->with('success', 'Employee advance deleted successfully');
    }

    // Fetch employee balance on a specific date
    public function getEmployeeBalance(Request $request)
    {
        $request->validate([
            'employee_name' => 'required|string',
            'employee_id' => 'required|integer|exists:users,id',  // Validate against User model
            'date' => 'required|date',
        ]);

        $employeeBalance = EmployeeAdvance::join('users', 'employee_advance.user_id', '=', 'users.id')
            ->select(
                'users.id as employee_id',
                'users.first_name',  // Replace 'name' with 'first_name' (from User model)
                'users.last_name',
                'users.username',
                'employee_advance.date as advance_date',
                'employee_advance.refund',
                'employee_advance.refund_date',
                'employee_advance.refund_amount',
                'employee_advance.balance'
            )
            ->where('users.first_name', $request->employee_name)  // Search based on user's first name
            ->where('users.id', $request->employee_id)
            ->where('employee_advance.date', '<=', $request->date)
            ->orderBy('employee_advance.date', 'DESC')
            ->first();

        if ($employeeBalance) {
            return response()->json([
                'success' => true,
                'employee' => $employeeBalance,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No advance data found for the given employee on the specified date.',
            ]);
        }
    }
}
