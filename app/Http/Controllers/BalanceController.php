<?php

namespace App\Http\Controllers;
use App\ProductTemp;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use App\Balance;
use App\Product;
use Illuminate\Support\Facades\Log;
use App\ProductionUnit;
use App\Packing;
use App\BusinessLocation;
use App\ProductionStock;
use App\PackingStock;
use App\VariationLocationDetails;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('balance.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $balances = Balance::with('location')
                ->where('business_id', $business_id)
                ->select(['id', 'name', 'date', 'raw_material', 'product_id', 'total_quantity', 'location_id', 'created_at']);

            return datatables()->of($balances)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                    data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action([\App\Http\Controllers\BalanceController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                                <li><a href="#" data-href="' . action([\App\Http\Controllers\BalanceController::class, 'destroy'], [$row->id]) . '" class="delete_balance"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
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
                    if (!is_array($row->product_id)) {
                        return 'N/A';
                    }

                    $productIds = $row->product_id;
                    $products = Product::whereIn('id', $productIds)->pluck('name', 'id')->toArray();

                    $formattedMaterials = [];
                    foreach ($row->raw_material as $index => $quantity) {
                        $productId = $productIds[$index] ?? null;
                        $productName = $products[$productId] ?? 'Unknown Product';
                        $formattedMaterials[] = $productName . ':' . $quantity;
                    }
                    return implode(', ', $formattedMaterials);
                })
                ->addColumn('location_name', function ($row) {
                    return $row->location ? $row->location->name : '';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('balance.index');
    }

    public function create()
    {
        if (!auth()->user()->can('balance.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        // Modified query to get products with their variations and stock
        $products = Product::where('business_id', $business_id)
                          ->select('id', 'name', 'unit_id')
                          ->with(['unit', 'variations' => function($query) {
                              $query->with('variation_location_details');
                          }])
                          ->get();

        return view('balance.create', compact('business_id', 'products', 'business_locations', 'bl_attributes'));
    }

    public function getProductStock($id)
    {
        if (!auth()->user()->can('balance.create')) {
            abort(403, 'Unauthorized action.');
        }

        $product = Product::with(['unit', 'variations' => function($query) {
                        $query->with('variation_location_details');
                    }])
                    ->findOrFail($id);

        // Calculate total stock and use abs() to ensure it's always positive
        $current_stock = abs($product->variations->sum(function($variation) {
            return $variation->variation_location_details->sum('qty_available');
        }));

        return response()->json([
            'current_stock' => $current_stock,
            'unit' => $product->unit->short_name ?? ''
        ]);
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->can('balance.create')) {
                abort(403, 'Unauthorized action.');
            }
            //  dd( $request->all());
    
            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|array',
                'raw_material.*' => 'required|numeric|min:0',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:products,id',
                'location_id' => 'required|exists:business_locations,id',
                'total_quantity' => 'required|numeric|min:0',
                'total_quantity' => 'required|numeric|min:0',
                  // Simplified validation for jars
                'jars' => 'nullable|array',
                'jars.*' => 'array',
                'jars.*.size' => 'required|in:5L,5L(sp),10L,10L(sp),20L,20L(sp)',
                'jars.*.quantity' => 'required|integer|min:1',
                // Simplified validation for packets
                'packets' => 'nullable|array',
                'packets.*.size' => 'required|in:100ML,100ML(sp),200ML,200ML(sp),500ML,500ML(sp)',
                'packets.*.quantity' => 'required|integer|min:1',
        
            ]);
           
    
            $business_id = $request->session()->get('user.business_id');
    
            // Initialize total quantity
            $current_total_quantity = $validated['total_quantity'];
    
            // DB::beginTransaction();
    
            // Create balance record
            $balance = new Balance();
            $balance->business_id = $business_id;
            $balance->date = $validated['date'];
            $balance->location_id = $validated['location_id'];
            $balance->product_id = $validated['product_id'];
            $balance->raw_material = $validated['raw_material'];
            $balance->total_quantity = $current_total_quantity;
            $balance->save();
    
            // Create production unit record
            $production_unit = new ProductionUnit();
            $production_unit->business_id = $business_id;
            $production_unit->date = $validated['date'];
            $production_unit->location_id = $validated['location_id'];
            $production_unit->product_id = $validated['product_id'];
            $production_unit->raw_material = $validated['raw_material'];
            $production_unit->total_quantity = $current_total_quantity;
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
    
                // Get the product variation
                $variation = Product::where('id', $product_id)
                    ->where('business_id', $business_id)
                    ->first()
                    ->variations()
                    ->first();
    
                if ($variation) {
                    // Update stock in variation_location_details
                    $vld = VariationLocationDetails::where('product_id', $product_id)
                        ->where('product_variation_id', $variation->product_variation_id)
                        ->where('variation_id', $variation->id)
                        ->where('location_id', $validated['location_id'])
                        ->first();
    
                    if ($vld) {
                        // Instead of subtracting, we add the raw material quantity
                        $vld->qty_available += $raw_material;
                        $vld->save();
                    }
                }
            }

            // if (!empty($request->jar_quantities)) {
            //     $jar = new Packing();
            //     $jar->business_id = $business_id; // Fixed: Use business_id instead of product_id
            //     $jar->jar = $request->jar_quantities;
            //     // $jar->packet = "10";
            //     $jar->date = $validated['date'];
            //     $jar->location_id = $validated['location_id'];
            //     $jar->save();
            // }


    
            // // Create packing_stock record
            // PackingStock::create([
            //     'location_id' => $validated['location_id'],
            //     'total' => $production_unit->total_quantity,
            //     'production_unit_id' => $production_unit->id,
            //     'date' => $validated['date']
            // ]);



            // In BalanceController.php, add this code after the jar handling section in the store method:
            //  dd($request->all());
             Log::info($request->all());

            



           // Handle packing data
            if ($request->has('jar_quantities') || $request->has('packet_quantities')) {
                // Initialize the Packing instance
                $packing = new Packing();
                $packing->business_id = auth()->user()->business_id;
                $packing->location_id = $validated['location_id'];
                $packing->date = $validated['date'];

                // Handle jar data
                $jar_sizes = (array) $request->input('jar_sizes', []);
                $jar_quantities = (array) $request->input('jar_quantities', []);
                
                $jar_data = [];
                for ($i = 0; $i < count($jar_sizes); $i++) {
                    // Only add if we have both size and quantity
                    if (isset($jar_sizes[$i]) && isset($jar_quantities[$i]) && 
                        $jar_quantities[$i] !== null && $jar_quantities[$i] !== '') {
                        $jar_data[] = [
                            'size' => $jar_sizes[$i],
                            'quantity' => (float) $jar_quantities[$i]
                        ];
                    }
                }
                
                // Store jar data as JSON
                $packing->jar = !empty($jar_data) ? json_encode($jar_data) : null;

                // Handle packet data
                $packet_sizes = (array) $request->input('packet_sizes', []);
                $packet_quantities = (array) $request->input('packet_quantities', []);
                
                $packet_data = [];
                for ($i = 0; $i < count($packet_sizes); $i++) {
                    // Only add if we have both size and quantity
                    if (isset($packet_sizes[$i]) && isset($packet_quantities[$i]) && 
                        $packet_quantities[$i] !== null && $packet_quantities[$i] !== '') {
                        $packet_data[] = [
                            'size' => $packet_sizes[$i],
                            'quantity' => (float) $packet_quantities[$i]
                        ];
                    }
                }
                
                // Store packet data as JSON
                $packing->packet = !empty($packet_data) ? json_encode($packet_data) : null;

                // Save the packing data
                $packing->save();

                // Calculate totals using array_reduce for better accuracy
                $total_jar_quantity = array_reduce($jar_data, function($carry, $item) {
                    return $carry + (float)$item['quantity'];
                }, 0.0);
                
                $total_packet_quantity = array_reduce($packet_data, function($carry, $item) {
                    return $carry + (float)$item['quantity'];
                }, 0.0);
                
                $total_quantity = $total_jar_quantity + $total_packet_quantity;

                // Create packing stock record
                PackingStock::create([
                    'location_id' => $validated['location_id'],
                    'total' => $total_quantity,
                    'production_unit_id' => $production_unit->id,
                    'date' => $validated['date']
                ]);
            }
            
            if(true)
            {
                $productTemp = ProductTemp::create([
                    'business_id' => $business_id,
                    'date' => $request->date,
                    'location_id' => $request->location_id,
                    'temperature' => json_encode($request->temperatures),
                    'quantity' => json_encode($request->quantities),
                    'product_output' => $request->product_output,
                ]);
                

                // 2. Update temperatures and create history records
                foreach ($request->temperatures as $index => $temperature_id) {
                    $quantity = $request->quantities[$index];
                    
                    // Update temperature table
                    DB::table('temperature')
                        ->where('temperature', $temperature_id)
                        ->update([
                            'temp_quantity' => DB::raw('COALESCE(temp_quantity, 0) + ' . $quantity)
                        ]);

                    // Create history record
                    DB::table('temperature_history')->insert([
                        'business_id' => $business_id,
                        'temperature' => $temperature_id,
                        'product_temp_id' => $productTemp->id,
                        'location_id' => $request->location_id,
                        'quantity' => $quantity,
                        'date' => $request->date,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            //end temprature code======================================================
            // DB::commit();
    
            $output = [
                'success' => true,
                'msg' => __("Balance added successfully")
            ];
        } catch (\Exception $e) {
            // DB::rollBack();
            Log::error('BalanceController@store: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }
    
        if ($request->ajax()) {
            return response()->json($output);
        }
    
        return redirect()->action([BalanceController::class, 'index'])->with('status', $output);
    }
    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->can('balance.update')) {
                abort(403, 'Unauthorized action.');
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'raw_material' => 'required|array',
                'raw_material.*' => 'required|numeric',
                'product_id' => 'required|array',
                'product_id.*' => 'required|exists:products,id',
                'location_id' => 'required|exists:business_locations,id',
                'total_quantity' => 'required|numeric',
            ]);

            $business_id = $request->session()->get('user.business_id');
            $balance = Balance::where('business_id', $business_id)->findOrFail($id);

            // Validate stock availability and calculate the current total quantity
            $current_total_quantity = 0;
            foreach ($validated['product_id'] as $key => $product_id) {
                $product = Product::with(['variations' => function($query) {
                                $query->with('variation_location_details');
                            }])
                            ->findOrFail($product_id);

                // Calculate current stock (current amount)
                $current_stock = $product->variations->sum(function($variation) {
                    return $variation->variation_location_details->sum('qty_available');
                });

                // Add the current stock (current_amount) to the total quantity
                $current_total_quantity += $current_stock;
            }

            // Add the user-entered total quantity
            $current_total_quantity += $validated['total_quantity'];

            DB::beginTransaction();

            $balance->update([
                'date' => $validated['date'],
                'location_id' => $validated['location_id'],
                'product_id' => $validated['product_id'],
                'raw_material' => $validated['raw_material'],
                'total_quantity' => $current_total_quantity // Save the calculated total quantity
            ]);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __("Balance updated successfully")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BalanceController@update: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->action([BalanceController::class, 'index'])->with('status', $output);
    }

    public function edit($id)
    {
        if (!auth()->user()->can('balance.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $balance = Balance::where('business_id', $business_id)->findOrFail($id);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        return view('balance.edit', compact('balance', 'products', 'business_locations', 'bl_attributes'));
    }




    public function destroy($id)
{
    try {
        if (!auth()->user()->can('balance.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $balance = Balance::where('business_id', $business_id)->findOrFail($id);

        DB::beginTransaction();

        // Perform any necessary operations before deleting the balance, such as updating stock levels or related records

        // Delete the balance record
        $balance->delete();

        DB::commit();

        $output = [
            'success' => true,
            'msg' => __("Balance deleted successfully")
        ];
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('BalanceController@destroy: ' . $e->getMessage());
        $output = [
            'success' => false,
            'msg' => $e->getMessage()
        ];
    }

    if (request()->ajax()) {
        return response()->json($output);
    }

    return redirect()->action([BalanceController::class, 'index'])->with('status', $output);
}

}
