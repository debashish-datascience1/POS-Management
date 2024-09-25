<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Log;
use App\ProductionUnit;
use App\Product;
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use App\VariationLocationDetails;


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
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        
        // Fetch products for the dropdown
        $products = Product::where('business_id', $business_id)
                           ->select('id', 'name')
                           ->get();

        return view('production.create', compact('business_id', 'products', 'business_locations', 'bl_attributes'));
    }

    // public function store(Request $request)
    // {
    //     try {
    //         if (!auth()->user()->can('production.create')) {
    //             abort(403, 'Unauthorized action.');
    //         }

    //         $validated = $request->validate([
    //             'date' => 'required|date',
    //             'raw_material' => 'required|integer',
    //             'product_id' => 'required|exists:products,id', // Add this line for product validation
    //         ]);

    //         $business_id = $request->session()->get('user.business_id');

    //         $production_unit = new ProductionUnit();
    //         $production_unit->business_id = $business_id;
    //         $production_unit->date = $validated['date'];
    //         $production_unit->raw_material = $validated['raw_material'];
    //         $production_unit->product_id = $validated['product_id']; // Add this line to save product_id
    //         $production_unit->save();

    //         $output = [
    //             'success' => true,
    //             'msg' => __("production.production_add_success")
    //         ];
    //     } catch (Exception $e) {
    //         Log::error('ProductionController@store: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg' => __("messages.something_went_wrong")
    //         ];
    //     }

    //     return redirect('production/unit')->with('status', $output);
    // }
    public function store(Request $request)
    {
        try {
            if (!auth()->user()->can('production.create')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|numeric',
                'product_id' => 'required|exists:products,id',
                'updated_stock' => 'required|numeric',
                'location_id' => 'required|exists:business_locations,id',
            ]);

            $business_id = $request->session()->get('user.business_id');

            // Check if the location exists in variation_location_details
            $locationExists = VariationLocationDetails::where('product_id', $validated['product_id'])
                ->where('location_id', $validated['location_id'])
                ->exists();

            if (!$locationExists) {
                throw new \Exception("Please purchase raw materials for this Business Location first.");
            }

            DB::beginTransaction();

            $production_unit = new ProductionUnit();
            $production_unit->business_id = $business_id;
            $production_unit->date = $validated['date'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->product_id = $validated['product_id'];
            $production_unit->location_id = $validated['location_id'];
            $production_unit->save();

            // Update stock in variation_location_details
            $variation = Product::where('id', $validated['product_id'])
                ->where('business_id', $business_id)
                ->first()
                ->variations()
                ->first();

            if ($variation) {
                VariationLocationDetails::updateOrCreate(
                    [
                        'product_id' => $validated['product_id'],
                        'product_variation_id' => $variation->product_variation_id,
                        'variation_id' => $variation->id,
                        'location_id' => $validated['location_id'],
                    ],
                    ['qty_available' => $validated['updated_stock']]
                );
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("production.production_add_success")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductionController@store: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        } else {
            if ($output['success']) {
                return redirect('production/unit')->with('status', $output);
            } else {
                return redirect()->back()->withInput()->with('status', $output);
            }
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->can('production.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $products = Product::where('business_id', $business_id)
                           ->pluck('name', 'id');

        return view('production.edit', compact('production_unit', 'products','business_locations','bl_attributes'));
    }

    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->can('production.update')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|numeric',
                'product_id' => 'required|exists:products,id',
                'updated_stock' => 'required|numeric',
                'location_id' => 'required|exists:business_locations,id',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

            // Check if the location exists in variation_location_details
            $locationExists = VariationLocationDetails::where('product_id', $validated['product_id'])
                ->where('location_id', $validated['location_id'])
                ->exists();

            if (!$locationExists) {
                throw new \Exception("Please purchase raw materials for this Business Location first.");
            }

            DB::beginTransaction();

            // Restore the original stock
            $this->updateStock($production_unit->product_id, $production_unit->raw_material, '+');

            // Update the production unit
            $production_unit->date = $validated['date'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->product_id = $validated['product_id'];
            $production_unit->location_id = $validated['location_id'];
            $production_unit->save();

            // Update the stock with the new quantity
            $this->updateStock($validated['product_id'], $validated['raw_material'], '-');

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("production.production_update_success")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProductionController@update: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        } else {
            if ($output['success']) {
                return redirect('production/unit')->with('status', $output);
            } else {
                return redirect()->back()->withInput()->with('status', $output);
            }
        }
    }
    
    public function destroy($id)
    {
        if (!auth()->user()->can('production.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

            DB::beginTransaction();

            // Restore the stock
            $this->updateStock($production_unit->product_id, $production_unit->raw_material, '+');

            $production_unit->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("lang_v1.production_delete_success")
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ProductionController@destroy: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    private function updateStock($product_id, $quantity, $operation)
    {
        $variation = Product::where('id', $product_id)
            ->first()
            ->variations()
            ->first();

        if ($variation) {
            $vld = VariationLocationDetails::where('product_id', $product_id)
                ->where('product_variation_id', $variation->product_variation_id)
                ->where('variation_id', $variation->id)
                ->first();

            if ($vld) {
                if ($operation === '+') {
                    $vld->qty_available += $quantity;
                } else {
                    $vld->qty_available -= $quantity;
                }
                $vld->save();
            }
        }
    }
}