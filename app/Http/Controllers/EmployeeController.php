<?php
namespace App\Http\Controllers;

use App\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::paginate(10);
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required',
            'age' => 'required|integer',
            'idprove' => 'required',
            'phone' => 'required',
            'gender' => 'required',
            'address' => 'required',
            'file_url' => 'nullable|file',
        ]);

        // Store the employee
        $employee = new Employee();
        $employee->name = $request->name;
        $employee->age = $request->age;
        $employee->idprove = $request->idprove;
        $employee->phone = $request->phone;
        $employee->gender = $request->gender;
        $employee->address = $request->address;

        if ($request->hasFile('file_url')) {
            $file = $request->file('file_url');
            $path = $file->store('files', 'public');
            $employee->fileattach = $path;
        }

        $employee->save();

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);
        if ($employee) {
            $employee->delete();
            return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
        }
        return redirect()->route('employees.index')->with('error', 'Employee not found.');
    }

    public function edit($id)
    {
        $employee = Employee::find($id);
        if ($employee) {
            return view('employees.edit', compact('employee'));
        }
        return redirect()->route('employees.index')->with('error', 'Employee not found.');
    }




    public function update(Request $request, $id)
{
    // Validate incoming request
    $validatedData = $request->validate([
        'name' => 'required',
        'age' => 'required|integer',
        'idprove' => 'required',
        'phone' => 'required',
        'gender' => 'required',
        'address' => 'required',
        'file_url' => 'nullable|file',
    ]);

    // Find the employee
    $employee = Employee::findOrFail($id);

    // Update employee details
    $employee->name = $request->name;
    $employee->age = $request->age;
    $employee->idprove = $request->idprove;
    $employee->phone = $request->phone;
    $employee->gender = $request->gender;
    $employee->address = $request->address;

    // Handle file upload if a new file is present
    if ($request->hasFile('file_url')) {
        // Delete old file if it exists
        if ($employee->fileattach) {
            Storage::disk('public')->delete($employee->fileattach);
        }

        // Store new file
        $file = $request->file('file_url');
        $path = $file->store('files', 'public');
        $employee->fileattach = $path;
    }

    // Save the updated employee
    $employee->save();

    // Redirect with success message
    return redirect()->route('employees.index')
        ->with('success', 'Employee updated successfully.');
}

    
}
