<?php

namespace App\Http\Controllers;

use App\Salary;
use App\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class SalaryController extends Controller
{
    public function index()
    {
        // Check user permission
        $this->authorize('salary.view');

        //$salaries = Salary::with('user')->get();
        $salaries = Salary::leftJoin('users', 'salaries.user_id', '=', 'users.id')
        ->select('users.first_name', 'users.last_name', 'salaries.*')
        ->get();
        return view('salary.index', compact('salaries'));
    }

    public function create()
    {
        // Check user permission
        $this->authorize('salary.create');

        // Fetch users 
        $users = User::all();
        return view('salary.create', compact('users'));
    }

    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
                'user_id' => [
                'required',
                'exists:users,id'
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
                'nullable',
                'string'
            ],
            'payment_mode' => [
                'required',
                'in:cash,bank_transfer,cheque'
            ],
            'salary_payment_mode' => [
                'required',
                'in:monthly,weekly,daily'
            ]
        ], [
            'user_id.exists' => 'Selected employee does not exist.',
            'salary_date.before_or_equal' => 'Salary date cannot be in the future.'
        ]);
    
        // If validation fails
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
    
        // Calculate salary details
        $basicSalary = $request->basic_salary;
        $deduction = $request->deduction ?? 0;
        $taxDeduction = $request->tax_deduction ?? 0;
    
        // Calculate deduction and tax amounts
        $deductionAmount = ($basicSalary * $deduction) / 100;
        $taxDeductionAmount = ($basicSalary * $taxDeduction) / 100;
        $netSalary = $basicSalary - $deductionAmount - $taxDeductionAmount;
    
        // Prepare salary data
        $salaryData = $request->except(['_token']);
        $salaryData['user_id'] = $request->user_id; // Map user_id to user_id
        $salaryData['deduction'] = $deductionAmount;
        $salaryData['tax_deduction'] = $taxDeductionAmount;
        $salaryData['net_salary'] = $netSalary;
        $salaryData['payment_mode'] = $request->payment_mode;
    
        // Create salary record
        try {
            $salary = Salary::create($salaryData);
            return redirect()->route('salaries.index')
                ->with('success', 'Salary record created successfully');
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error("Salary record creation failed: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create salary record. Please try again.')
                ->withInput();
        }
    }
    

    public function edit(Salary $salary)
    {
        $users = User::all();
        return view('salary.edit', compact('salary', 'users'));
    }

    public function update(Request $request, Salary $salary)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'salary_date' => 'required|date',
            'basic_salary' => 'required|numeric',
            'deduction' => 'nullable|numeric',
            'tax_deduction' => 'nullable|numeric',
            'net_salary' => 'nullable|numeric',
            'bank_account_number' => 'nullable|string',
            'payment_mode' => 'required|in:cash,bank_transfer,cheque',
            'salary_payment_mode' => 'required|in:monthly,weekly,daily',
        ]);
        
        // Prepare data
        $updateData = $request->all();
        $updateData['employee_id'] = $request->user_id;
        
        $salary->update($updateData);

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