<?php

namespace App\Http\Controllers;

use App\FinalProduct;
use Illuminate\Http\Request;

class FinalProductController extends Controller
{
    /**
     * Display a listing of final products.
     */
    public function index()
    {
        $finalProducts = FinalProduct::all();
        return view('final-product.index', compact('finalProducts'));
    }

    /**
     * Show the form for creating a new final product.
     */
    public function create()
    {
        return view('final-product.create');
    }

    /**
     * Store a newly created final product in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_name' => 'required|max:255',
            'description' => 'nullable',
            'quantity' => 'required|integer|min:0',
            'sum' => 'required|numeric|min:0'
        ]);

        FinalProduct::create($validatedData);

        return redirect()->route('final-product.index')
            ->with('success', 'Final Product created successfully.');
    }

    /**
     * Show the form for editing the specified final product.
     */
    public function edit($id)
    {
        $finalProduct = FinalProduct::findOrFail($id);
        return view('final-product.edit', compact('finalProduct'));
    }

    /**
     * Update the specified final product in storage.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'product_name' => 'required|max:255',
            'description' => 'nullable',
            'quantity' => 'required|integer|min:0',
            'sum' => 'required|numeric|min:0'
        ]);

        $finalProduct = FinalProduct::findOrFail($id);
        $finalProduct->update($validatedData);

        return redirect()->route('final-product.index')
            ->with('success', 'Final Product updated successfully.');
    }

    /**
     * Remove the specified final product from storage.
     */
    public function destroy($id)
    {
        $finalProduct = FinalProduct::findOrFail($id);
        $finalProduct->delete();

        return redirect()->route('final-product.index')
            ->with('success', 'Final Product deleted successfully.');
    }
}