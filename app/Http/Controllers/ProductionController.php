<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Log;
use App\ProductionUnit;
use App\Product;
use App\Packing;
use App\BusinessLocation;
use App\ProductionStock;
use App\PackingStock;
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
                $production_units = ProductionUnit::with('location')
                    ->where('business_id', $business_id)
                    ->select(['id', 'name', 'date', 'raw_material', 'product_id', 'total_quantity', 'location_id', 'created_at']);
    
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
                    ->editColumn('total_quantity', function ($row) {
                        return number_format((float)$row->total_quantity, 2, '.', '');
                    })
                    ->addColumn('products', function ($row) {
                        if (!is_array($row->product_id)) {
                            return 'N/A';
                        }
                        $productNames = Product::whereIn('id', $row->product_id)->pluck('name')->toArray();
                        return implode(', ', $productNames);
                    })
                    ->addColumn('raw_materials', function ($row) {
                        Log::info('ProductionController: raw_material for production unit ' . $row->id . ':', $row->raw_material);
                        Log::info('ProductionController: product_id for production unit ' . $row->id . ':', $row->product_id);
                        
                        if (!is_array($row->product_id)) {
                            Log::warning('ProductionController: product_id is not an array for production unit ' . $row->id);
                            return 'N/A';
                        }
                        
                        $productIds = $row->product_id;
                        Log::info('ProductionController: Product IDs extracted for production unit ' . $row->id . ':', $productIds);
                        
                        $products = Product::whereIn('id', $productIds)->pluck('name', 'id')->toArray();
                        Log::info('ProductionController: Products fetched for production unit ' . $row->id . ':', $products);
                        
                        $formattedMaterials = [];
                        foreach ($row->raw_material as $index => $quantity) {
                            $productId = $productIds[$index] ?? null;
                            $productName = $products[$productId] ?? 'Unknown Product';
                            $formattedMaterials[] = $productName . ':' . $quantity;
                            
                            if ($productName === 'Unknown Product') {
                                Log::warning('ProductionController: Unknown product ID ' . $productId . ' for production unit ' . $row->id);
                            }
                        }
                        return implode(', ', $formattedMaterials);
                    })
                    ->addColumn('location_name', function ($row) {
                        return $row->location ? $row->location->name : '';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }
    
            return view('production.index');
        } catch (Exception $e) {
            Log::error('ProductionController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
    //             'raw_material' => 'required|array',
    //             'raw_material.*' => 'required|numeric',
    //             'product_id' => 'required|array',
    //             'product_id.*' => 'required|exists:products,id',
    //             'location_id' => 'required|exists:business_locations,id',
    //             'liquor' => 'required|string',
    //             'total_quantity' => 'required|numeric',
    //         ]);

    //         $business_id = $request->session()->get('user.business_id');

    //         DB::beginTransaction();

    //         // Create the ProductionUnit record
    //         $production_unit = new ProductionUnit();
    //         $production_unit->business_id = $business_id;
    //         $production_unit->date = $validated['date'];
    //         $production_unit->location_id = $validated['location_id'];
    //         $production_unit->name = $validated['liquor'];
    //         $production_unit->product_id = $validated['product_id'];
    //         $production_unit->raw_material = $validated['raw_material'];
    //         $production_unit->total_quantity = $validated['total_quantity'];
    //         $production_unit->save();

    //         $total_production_stock = 0;

    //         // Process each product
    //         foreach ($validated['product_id'] as $key => $product_id) {
    //             $raw_material = $validated['raw_material'][$key];

    //             // Check if the location exists in variation_location_details
    //             $locationExists = VariationLocationDetails::where('product_id', $product_id)
    //                 ->where('location_id', $validated['location_id'])
    //                 ->exists();

    //             if (!$locationExists) {
    //                 throw new \Exception("Please purchase raw materials for this Business Location first.");
    //             }

    //             // Update or create production_stock
    //             $production_stock = ProductionStock::updateOrCreate(
    //                 [
    //                     'product_id' => $product_id,
    //                     'location_id' => $validated['location_id'],
    //                 ],
    //                 [
    //                     'total_raw_material' => DB::raw('total_raw_material + ' . $raw_material),
    //                 ]
    //             );

    //             // Fetch the updated total_raw_material value
    //             $updated_production_stock = ProductionStock::where('product_id', $product_id)
    //                 ->where('location_id', $validated['location_id'])
    //                 ->first();

    //             $total_production_stock += $updated_production_stock->total_raw_material;

    //             // Update stock in variation_location_details
    //             $variation = Product::where('id', $product_id)
    //                 ->where('business_id', $business_id)
    //                 ->first()
    //                 ->variations()
    //                 ->first();

    //             if ($variation) {
    //                 $vld = VariationLocationDetails::where('product_id', $product_id)
    //                     ->where('product_variation_id', $variation->product_variation_id)
    //                     ->where('variation_id', $variation->id)
    //                     ->where('location_id', $validated['location_id'])
    //                     ->first();

    //                 if ($vld) {
    //                     $vld->qty_available -= $raw_material;
    //                     $vld->save();
    //                 }
    //             }
    //         }

    //         // Update or create packing_stock
    //         PackingStock::updateOrCreate(
    //             ['location_id' => $validated['location_id']],
    //             ['total' => $total_production_stock]
    //         );

    //         DB::commit();

    //         $output = [
    //             'success' => true,
    //             'msg' => __("production.production_add_success")
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProductionController@store: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg' => $e->getMessage()
    //         ];
    //     }

    //     if ($request->ajax()) {
    //         return response()->json($output);
    //     } else {
    //         if ($output['success']) {
    //             return redirect('production/unit')->with('status', $output);
    //         } else {
    //             return redirect()->back()->withInput()->with('status', $output);
    //         }
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     try {
    //         if (!auth()->user()->can('production.update')) {
    //             abort(403, 'Unauthorized action.');
    //         }

    //         $validated = $request->validate([
    //             'date' => 'required|date',
    //             'raw_material' => 'required|array',
    //             'raw_material.*' => 'required|numeric',
    //             'product_id' => 'required|array',
    //             'product_id.*' => 'required|exists:products,id',
    //             'location_id' => 'required|exists:business_locations,id',
    //             'liquor' => 'required|string',
    //             'total_quantity' => 'required|numeric',
    //         ]);

    //         $business_id = $request->session()->get('user.business_id');
    //         $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

    //         DB::beginTransaction();

    //         $total_production_stock = 0;

    //         // Process each product
    //         foreach ($validated['product_id'] as $key => $product_id) {
    //             $old_raw_material = $production_unit->raw_material[$key] ?? 0;
    //             $new_raw_material = $validated['raw_material'][$key];

    //             // Check if the location exists in variation_location_details
    //             $locationExists = VariationLocationDetails::where('product_id', $product_id)
    //                 ->where('location_id', $validated['location_id'])
    //                 ->exists();

    //             if (!$locationExists) {
    //                 throw new \Exception("Please purchase raw materials for this Business Location first.");
    //             }

    //             // Update production_stock table
    //             $this->updateProductionStock([
    //                 'product_id' => $product_id,
    //                 'old_raw_material' => $old_raw_material,
    //                 'new_raw_material' => $new_raw_material,
    //                 'location_id' => $validated['location_id'],
    //             ]);

    //             // Fetch the updated total_raw_material value
    //             $updated_production_stock = ProductionStock::where('product_id', $product_id)
    //                 ->where('location_id', $validated['location_id'])
    //                 ->first();

    //             $total_production_stock += $updated_production_stock->total_raw_material;

    //             // Update the stock
    //             $this->updateStock($product_id, $old_raw_material, '+', $validated['location_id']); // Add back old quantity
    //             $this->updateStock($product_id, $new_raw_material, '-', $validated['location_id']); // Subtract new quantity
    //         }

    //         // Update the production unit
    //         $production_unit->date = $validated['date'];
    //         $production_unit->location_id = $validated['location_id'];
    //         $production_unit->name = $validated['liquor'];
    //         $production_unit->product_id = $validated['product_id'];
    //         $production_unit->raw_material = $validated['raw_material'];
    //         $production_unit->total_quantity = $validated['total_quantity'];
    //         $production_unit->save();

    //         // Update packing_stock
    //         PackingStock::updateOrCreate(
    //             ['location_id' => $validated['location_id']],
    //             ['total' => $total_production_stock]
    //         );

    //         DB::commit();

    //         $output = [
    //             'success' => true,
    //             'msg' => __("production.production_update_success")
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('ProductionController@update: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg' => $e->getMessage()
    //         ];
    //     }

    //     if ($request->ajax()) {
    //         return response()->json($output);
    //     } else {
    //         if ($output['success']) {
    //             return redirect('production/unit')->with('status', $output);
    //         } else {
    //             return redirect()->back()->withInput()->with('status', $output);
    //         }
    //     }
    // }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->can('production.create')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|array',
                'raw_material.*' => 'required|numeric',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:products,id',
                'location_id' => 'required|exists:business_locations,id',
                'liquor' => 'required|string',
                'total_quantity' => 'required|numeric',
            ]);

            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();

            // Create the ProductionUnit record
            $production_unit = new ProductionUnit();
            $production_unit->business_id = $business_id;
            $production_unit->date = $validated['date'];
            $production_unit->location_id = $validated['location_id'];
            $production_unit->name = $validated['liquor'];
            $production_unit->product_id = $validated['product_id'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->total_quantity = $validated['total_quantity'];
            $production_unit->save();

            $total_production_stock = 0;

            // Process each product
            foreach ($validated['product_id'] as $key => $product_id) {
                $raw_material = $validated['raw_material'][$key];

                // Check if the location exists
                $locationExists = VariationLocationDetails::where('product_id', $product_id)
                    ->where('location_id', $validated['location_id'])
                    ->exists();

                if (!$locationExists) {
                    throw new \Exception("Please purchase raw materials for this Business Location first.");
                }

                // Update production_stock
                $production_stock = ProductionStock::updateOrCreate(
                    [
                        'product_id' => $product_id,
                        'location_id' => $validated['location_id'],
                    ],
                    [
                        'total_raw_material' => DB::raw('total_raw_material + ' . $raw_material),
                    ]
                );

                $updated_production_stock = ProductionStock::where('product_id', $product_id)
                    ->where('location_id', $validated['location_id'])
                    ->first();

                $total_production_stock += $updated_production_stock->total_raw_material;

                // Update variation_location_details stock
                $variation = Product::where('id', $product_id)
                    ->where('business_id', $business_id)
                    ->first()
                    ->variations()
                    ->first();

                if ($variation) {
                    $vld = VariationLocationDetails::where('product_id', $product_id)
                        ->where('product_variation_id', $variation->product_variation_id)
                        ->where('variation_id', $variation->id)
                        ->where('location_id', $validated['location_id'])
                        ->first();

                    if ($vld) {
                        $vld->qty_available -= $raw_material;
                        $vld->save();
                    }
                }
            }

            // Create new packing_stock record
            PackingStock::create([
                'location_id' => $validated['location_id'],
                'total' => $production_unit->total_quantity,
                'production_unit_id' => $production_unit->id,
                'date' => $validated['date']
            ]);

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
        }
        
        return $output['success'] 
            ? redirect('production/unit')->with('status', $output)
            : redirect()->back()->withInput()->with('status', $output);
    }

    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->can('production.update')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|array',
                'raw_material.*' => 'required|numeric',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:products,id',
                'location_id' => 'required|exists:business_locations,id',
                'liquor' => 'required|string',
                'total_quantity' => 'required|numeric',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

            DB::beginTransaction();

            $total_production_stock = 0;

            foreach ($validated['product_id'] as $key => $product_id) {
                $old_raw_material = $production_unit->raw_material[$key] ?? 0;
                $new_raw_material = $validated['raw_material'][$key];

                $locationExists = VariationLocationDetails::where('product_id', $product_id)
                    ->where('location_id', $validated['location_id'])
                    ->exists();

                if (!$locationExists) {
                    throw new \Exception("Please purchase raw materials for this Business Location first.");
                }

                // Update production_stock
                $this->updateProductionStock([
                    'product_id' => $product_id,
                    'old_raw_material' => $old_raw_material,
                    'new_raw_material' => $new_raw_material,
                    'location_id' => $validated['location_id'],
                ]);

                $updated_production_stock = ProductionStock::where('product_id', $product_id)
                    ->where('location_id', $validated['location_id'])
                    ->first();

                $total_production_stock += $updated_production_stock->total_raw_material;

                // Update stock
                $this->updateStock($product_id, $old_raw_material, '+', $validated['location_id']);
                $this->updateStock($product_id, $new_raw_material, '-', $validated['location_id']);
            }

            // Update production unit
            $production_unit->update([
                'date' => $validated['date'],
                'location_id' => $validated['location_id'],
                'name' => $validated['liquor'],
                'product_id' => $validated['product_id'],
                'raw_material' => $validated['raw_material'],
                'total_quantity' => $validated['total_quantity']
            ]);

            // Update existing packing_stock record
            PackingStock::where('production_unit_id', $id)->update([
                'total' => $validated['total_quantity'],
                'date' => $validated['date']
            ]);

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
        }
        
        return $output['success'] 
            ? redirect('production/unit')->with('status', $output)
            : redirect()->back()->withInput()->with('status', $output);
    }

    private function updateProductionStock($data)
    {
        $productionStock = ProductionStock::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->first();

        if ($productionStock) {
            // If record exists, update it by replacing the old value with the new one
            $productionStock->total_raw_material = $productionStock->total_raw_material - floatval($data['old_raw_material']) + floatval($data['new_raw_material']);
            $productionStock->save();
        } else {
            // If record doesn't exist, create a new one with the new value
            $productionStock = ProductionStock::create([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'total_raw_material' => floatval($data['new_raw_material']),
            ]);
        }

        return $productionStock;
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

        $products = Product::where('business_id', $business_id)->pluck('name', 'id');
    
        // Fetch current stock for each product, adjusting for the quantity used in this production unit
        $product_stocks = [];
        foreach ($production_unit->product_id as $key => $product_id) {
            $current_stock = $this->getCurrentStock($product_id, $production_unit->location_id);
            $used_quantity = $production_unit->raw_material[$key];
            $product_stocks[$product_id] = $current_stock + $used_quantity;
        }

        return view('production.edit', compact('production_unit', 'products','business_locations','bl_attributes', 'product_stocks'));
    }

    private function getCurrentStock($product_id, $location_id)
    {
        $variation = Product::find($product_id)->variations()->first();
        
        if ($variation) {
            $vld = VariationLocationDetails::where('product_id', $product_id)
                ->where('product_variation_id', $variation->product_variation_id)
                ->where('variation_id', $variation->id)
                ->where('location_id', $location_id)
                ->first();
            
            return $vld ? $vld->qty_available : 0;
        }
        
        return 0;
    }

    private function updateStock($product_id, $quantity, $operation, $location_id)
    {
        $variation = Product::where('id', $product_id)
            ->first()
            ->variations()
            ->first();

        if ($variation) {
            $vld = VariationLocationDetails::where('product_id', $product_id)
                ->where('product_variation_id', $variation->product_variation_id)
                ->where('variation_id', $variation->id)
                ->where('location_id', $location_id)
                ->first();

            if ($vld) {
                $quantity = floatval($quantity);
                if ($operation === '+') {
                    $vld->qty_available += $quantity;
                } else {
                    $vld->qty_available -= $quantity;
                }
                $vld->save();
            }
        }
    }
    
    // public function destroy($id)
    // {
    //     if (!auth()->user()->can('production.delete')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     try {
    //         $business_id = request()->session()->get('user.business_id');
    //         $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

    //         DB::beginTransaction();

    //         $raw_materials = $production_unit->raw_material;
    //         $location_id = $production_unit->location_id;

    //         foreach ($raw_materials as $key => $quantity) {
    //             $product_id = $production_unit->product_id[$key];

    //             // Update production_stock
    //             ProductionStock::where('product_id', $product_id)
    //                 ->where('location_id', $location_id)
    //                 ->decrement('total_raw_material', $quantity);

    //             // Restore stock in variation_location_details
    //             $this->updateStock($product_id, $quantity, '+', $location_id);
    //         }

    //         // Update packing_stock
    //         $total_production_stock = ProductionStock::where('location_id', $location_id)->sum('total_raw_material');
    //         PackingStock::updateOrCreate(
    //             ['location_id' => $location_id],
    //             ['total' => $total_production_stock]
    //         );

    //         $production_unit->delete();

    //         DB::commit();

    //         $output = [
    //             'success' => true,
    //             'msg' => __("lang_v1.production_delete_success")
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::error('ProductionController@destroy: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg' => __("messages.something_went_wrong")
    //         ];
    //     }

    //     return $output;
    // }
    public function destroy($id)
    {
        if (!auth()->user()->can('production.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $production_unit = ProductionUnit::where('business_id', $business_id)->findOrFail($id);

            DB::beginTransaction();

            $raw_materials = $production_unit->raw_material;
            $location_id = $production_unit->location_id;

            foreach ($raw_materials as $key => $quantity) {
                $product_id = $production_unit->product_id[$key];

                // Update production_stock
                ProductionStock::where('product_id', $product_id)
                    ->where('location_id', $location_id)
                    ->decrement('total_raw_material', $quantity);

                // Restore stock
                $this->updateStock($product_id, $quantity, '+', $location_id);
            }

            // Delete associated packing_stock record
            PackingStock::where('production_unit_id', $id)->delete();

            $production_unit->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("lang_v1.production_delete_success")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ProductionController@destroy: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}