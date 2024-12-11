<?php

namespace App\Http\Controllers;

use App\Employee;
use App\EmployeeAdvance;
use Illuminate\Http\Request;

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
    public function index()
    {
        abort_unless(auth()->user()->can('employee.view'), 403);

        $employeeAdvances = EmployeeAdvance::with('employee')->get(); // Get all advances with employee data
        return view('employee_advance.index', compact('employeeAdvances'));
    }

    // Show form to create a new employee advance
    public function create()
    {
        $employees = Employee::all(); // Get all employees for dropdown
        return view('employee_advance.create', compact('employees'));
    }

    // Store a new employee advance
    public function store(Request $request)
    {
        $request->validate([
            'empid' => 'required|exists:employees,id',  // Ensure employee exists
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
        $employees = Employee::all(); // Get all employees for dropdown
        return view('employee_advance.edit', compact('employeeAdvance', 'employees'));
    }

    // Update an existing employee advance
    public function update(Request $request, EmployeeAdvance $employeeAdvance)
    {
        $request->validate([
            'empid' => 'required|exists:employees,id',
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
            'employee_id' => 'required|integer|exists:employees,id',
            'date' => 'required|date',
        ]);

        $employeeBalance = EmployeeAdvance::join('employees', 'employee_advance.empid', '=', 'employees.id')
            ->select(
                'employees.id as employee_id',
                'employees.name',
                'employees.age',
                'employees.salary',
                'employees.gender',
                'employees.address',
                'employees.phone',
                'employee_advance.date as advance_date',
                'employee_advance.refund',
                'employee_advance.refund_date',
                'employee_advance.refund_amount',
                'employee_advance.balance'
            )
            ->where('employees.name', $request->employee_name)
            ->where('employees.id', $request->employee_id)
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
