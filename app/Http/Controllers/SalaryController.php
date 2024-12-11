<?php

namespace App\Http\Controllers;

use App\Salary;
use App\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;  // Correct import for Validator

class SalaryController extends Controller
{
    public function index()
    {
        // Check user permission
        $this->authorize('salary.view');

        $salaries = Salary::with('employee')->get();
        return view('salary.index', compact('salaries'));
    }

    public function create()
    {
        // Check user permission
        $this->authorize('salary.create');

        $employees = Employee::all();
        return view('salary.create', compact('employees'));
    }
    public function store(Request $request)
{
    // Debugging: Comment out this line in production
    // dd($request->all());

    // Advanced validation with custom rules
    $validator = Validator::make($request->all(), [
        'employee_id' => [
            'required',
            'exists:employees,id'
        ],
        'salary_date' => [
            'required',
            'date',
            'before_or_equal:' . Carbon::today()->format('Y-m-d')
        ],
        'basic_salary' => [
            'required',
            'numeric',
            'min:0'
        ],
        'deduction' => [
            'nullable',
            'numeric',
            'min:0',
            'max:100'
        ],
        'tax_deduction' => [
            'nullable',
            'numeric',
            'min:0',
            'max:100'
        ],
        'bank_account_number' => [
            'required',
            'string',
            'regex:/^[0-9]+$/'
        ],
        'payment_mode' => [
            'required',
            'in:Cash,Bank Transfer,Cheque'
        ],
        'salary_payment_mode' => [
            'required',
            'in:Monthly,Weekly,Daily'
        ]
    ], [
        'bank_account_number.regex' => 'Bank account number must contain only digits.',
        'salary_date.before_or_equal' => 'Salary date cannot be in the future.'
    ]);

    // Check validation
   
    //  dd($request->all());
    // Calculate salary details
    $basicSalary = $request->basic_salary;
    $deduction = $request->deduction ?? 0; // Ensure deduction defaults to 0 if null
    $taxDeduction = $request->tax_deduction ?? 0; // Ensure tax deduction defaults to 0 if null

    // Calculate deduction and tax amounts
    $deductionAmount = ($basicSalary * $deduction) / 100;
    $taxDeductionAmount = ($basicSalary * $taxDeduction) / 100;
    $netSalary = $basicSalary - $deductionAmount - $taxDeductionAmount;

    // Prepare salary data (excluding token and salary_date)
    $salaryData = $request->except(['_token', 'salary_date']);
    $salaryData['deduction'] = $deductionAmount;
    $salaryData['tax_deduction'] = $taxDeductionAmount;
    $salaryData['net_salary'] = $netSalary;

    // Create salary record
    try {
        $salary = Salary::create($salaryData);

        return redirect()->route('salaries.index')
            ->with('success', 'Salary record created successfully');
    } catch (\Exception $e) {
        // Log the exception and details for debugging
        \Log::error("Salary record creation failed: " . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Failed to create salary record. Please try again.')
            ->withInput();
    }
}


    public function edit(Salary $salary)
    {
        $employees = Employee::all();
        return view('salary.edit', compact('salary', 'employees'));
    }

    public function update(Request $request, Salary $salary)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'salary_date' => 'required|date',
            'basic_salary' => 'required|numeric',
            'deduction' => 'nullable|numeric',
            'tax_deduction' => 'nullable|numeric',
            'net_salary' => 'nullable|numeric',
            'bank_account_number' => 'required|string',
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque',
            'salary_payment_mode' => 'required|in:Monthly,Weekly,Daily',
        ]);
        
        $salary->update($request->all());

        return redirect()->route('salaries.index')->with('success', 'Salary record updated successfully');
    }

    public function destroy(Salary $salary)
{
    try {
        // Delete the salary record
        $salary->delete();

        // Return a success response
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        // Return an error response in case of failure
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
}

}
