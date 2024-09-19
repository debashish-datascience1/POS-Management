<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Log;
use App\ProductionUnit;
use App\Product;

class ProductionController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        
    }

    public function index()
    {
        try {
            if (!auth()->user()->can('production.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {
                $production_units = ProductionUnit::with('product')
                    ->where('business_id', $business_id)
                    ->select(['id', 'date', 'raw_material', 'product_id', 'created_at']);

                return datatables()->of($production_units)
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li><a href="' . action([\App\Http\Controllers\ProductionController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                                    <li><a href="#" data-href="' . action([\App\Http\Controllers\ProductionController::class, 'destroy'], [$row->id]) . '" class="delete_production_unit"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                                    </ul>
                                </div>';
                        return $html;
                    })
                    ->editColumn('date', '{{@format_date($date)}}')
                    ->addColumn('product_name', function ($row) {
                        return $row->product ? $row->product->name : '';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('production.index');
        } catch (Exception $e) {
            Log::error('ProductionController@index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        if (!auth()->user()->can('production.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Fetch products for the dropdown
        $products = Product::where('business_id', $business_id)
                           ->select('id', 'name')
                           ->get();

        return view('production.create', compact('business_id', 'products'));
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->can('production.create')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|integer',
                'product_id' => 'required|exists:products,id', // Add this line for product validation
            ]);

            $business_id = $request->session()->get('user.business_id');

            $production_unit = new ProductionUnit();
            $production_unit->business_id = $business_id;
            $production_unit->date = $validated['date'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->product_id = $validated['product_id']; // Add this line to save product_id
            $production_unit->save();

            $output = [
                'success' => true,
                'msg' => __("production.production_add_success")
            ];
        } catch (Exception $e) {
            Log::error('ProductionController@store: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect('production/unit')->with('status', $output);
    }

    public function edit($id)
    {
        if (!auth()->user()->can('production.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

        // Fetch products for the dropdown
        $products = Product::where('business_id', $business_id)
                           ->pluck('name', 'id');

        return view('production.edit', compact('production_unit', 'products'));
    }

    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->can('production.update')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|integer',
                'product_id' => 'required|exists:products,id',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

            $production_unit->date = $validated['date'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->product_id = $validated['product_id'];
            $production_unit->save();

            $output = [
                'success' => true,
                'msg' => __("production.production_update_success")
            ];
        } catch (Exception $e) {
            Log::error('ProductionController@update: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect('production/unit')->with('status', $output);
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('production.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);
            $production_unit->delete();

            $output = [
                'success' => true,
                'msg' => __("production.production_delete_success")
            ];
        } catch (Exception $e) {
            Log::error('ProductionController@destroy: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function packing()
    {
        try {
            if (!auth()->user()->can('production.view')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = request()->session()->get('user.business_id');

            return view('production.packing')->with(compact('business_id'));
        } catch (\Exception $e) {
            Log::error('ProductionController@packing: ' . $e->getMessage());
            return response()->view('errors.500', [], 500);
        }
    }
}