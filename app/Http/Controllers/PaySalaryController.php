<?php

namespace App\Http\Controllers;

use App\User;   // Import the User model
use App\SalaryPayment;  // Import the SalaryPayment model
use Illuminate\Http\Request;

class PaySalaryController extends Controller
{
    public function __construct()
    {
        // Ensure the user has 'pay_salary.view' permission
        $this->middleware('can:pay_salary.view');
    }

    // Display the Pay Salary page with a list of employees and their salary details
    public function index()
    {
        // Get the list of users (employees) along with their salary, advance, and last paid month details
        $users = User::with(['salaryPayments' => function($query) {
            $query->orderBy('payment_date', 'desc')->first();  // Get the latest salary payment record
        }])->get();

        return view('pay_salary.index', compact('users'));
    }

    // Process the salary payment for the selected user
    public function paySalary(Request $request, $userId)
    {
        // Validation
        $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
        ]);

        // Find the user (employee)
        $user = User::findOrFail($userId);

        // Create the salary payment record
        $salaryPayment = SalaryPayment::create([
            'user_id' => $user->id,  // Assuming the foreign key is 'user_id' in the salary payments table
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
        ]);

        // Optionally: Add success message or redirect
        return redirect()->route('pay_salary.index')->with('success', 'Salary paid successfully!');
    }
}
