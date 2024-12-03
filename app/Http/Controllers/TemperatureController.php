<?php

namespace App\Http\Controllers;

use App\Temperature;
use App\ProductTemp;
use App\Product;
use Illuminate\Http\Request;
use App\BusinessLocation;
use Illuminate\Support\Facades\Validator;  // Add this import
use Yajra\DataTables\Facades\DataTables;
use DB;
use Log;

class TemperatureController extends Controller
{
    // TemperatureController.php index method
    public function index()
    {
        try {
            if (!auth()->user()->can('temperature.view')) {
                abort(403, 'Unauthorized action.');
            }
    
            $business_id = request()->session()->get('user.business_id');
    
            if (request()->ajax()) {
                $temperature_records = ProductTemp::where('business_id', $business_id)
                    ->select([
                        'date',
                        'temperature',
                        'quantity',
                        'product_output',
                        'id'
                    ]);
    
                return DataTables::of($temperature_records)
                    ->addColumn('action', function ($row) {
                        $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' . 
                                __("messages.actions") . 
                                '<span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="#" class="edit-temperature" data-href="' . action([\App\Http\Controllers\TemperatureController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                                <li><a href="#" class="delete-temperature" data-href="' . action([\App\Http\Controllers\TemperatureController::class, 'destroy'], [$row->id]) . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                            </ul>
                        </div>';
                        return $html;
                    })
                    ->editColumn('date', '{{@format_date($date)}}')
                    ->editColumn('temperature', function ($row) {
                        $temperatures = json_decode($row->temperature);
                        return is_array($temperatures) ? implode(', ', $temperatures) : $row->temperature;
                    })
                    ->editColumn('quantity', function ($row) {
                        $quantities = json_decode($row->quantity);
                        if (is_array($quantities)) {
                            return implode(', ', array_map(function($qty) {
                                return number_format((float)$qty, 2, '.', '');
                            }, $quantities));
                        }
                        return number_format((float)$row->quantity, 2, '.', '');
                    })
                    ->editColumn('product_output', function ($row) {
                        return number_format((float)$row->product_output, 2, '.', '');
                    })
                    ->rawColumns(['action', 'temperature'])
                    ->make(true);
            }
    
            return view('temperature.index');
        } catch (Exception $e) {
            Log::error('TemperatureController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        if (!auth()->user()->can('temperature.create')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];
    
        // Calculate the date 7 days ago
        $sevenDaysAgo = now()->subDays(7)->format('Y-m-d');
    
        // Fetch product outputs from packing_stock table
        $productOutputs = $this->getProductOutputsForDate($sevenDaysAgo);
    
        // Fetch temperatures for the dropdown
        $temperatures = Temperature::select('temperature')
                                   ->distinct()
                                   ->pluck('temperature', 'temperature');
    
        return view('temperature.create', compact(
            'business_id',
            'temperatures',
            'business_locations',
            'bl_attributes',
            'productOutputs',
            'sevenDaysAgo'
        ));
    }
    
    public function getProductOutputs(Request $request)
    {
        try {
            if (!auth()->user()->can('temperature.create')) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Unauthorized'
                ], 403);
            }
    
            $date = $request->input('date');
            if (!$date) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Date is required'
                ], 400);
            }
    
            // Parse and validate the date
            $parsedDate = \Carbon::parse($date);
            $sevenDaysBefore = $parsedDate->copy()->subDays(7)->format('Y-m-d');
    
            $productOutputs = $this->getProductOutputsForDate($sevenDaysBefore);
    
            return response()->json([
                'success' => true,
                'data' => $productOutputs,
                'debug' => [
                    'selected_date' => $date,
                    'seven_days_before' => $sevenDaysBefore
                ]
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error in getProductOutputs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'An error occurred while fetching product outputs',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getProductOutputsForDate($date)
    {
        try {
            $result = DB::table('packing_stock')
                ->select(
                    'location_id',
                    DB::raw('MIN(id) as id'),
                    DB::raw('COALESCE(SUM(total), 0) as total_stock')
                )
                ->whereDate('date', $date)
                ->groupBy('location_id')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->location_id => [
                        'id' => $item->id,
                        'stock' => (float)$item->total_stock
                    ]];
                });
                
            \Log::info('Product outputs for date ' . $date, ['result' => $result]);
            return $result;
                
        } catch (\Exception $e) {
            \Log::error('Error in getProductOutputsForDate: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('temperature.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        try {
            // Validate the inputs
            $baseValidation = Validator::make($request->all(), [
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
                'product_output' => 'required|numeric',
                'temperatures' => 'required|array|min:1',
                'quantities' => 'required|array|min:1',
                'quantities.*' => 'required|numeric|min:0',
            ]);

            if ($baseValidation->fails()) {
                return [
                    'success' => false,
                    'msg' => $baseValidation->errors()->first()
                ];
            }

            // Validate that total quantity doesn't exceed product output
            $totalQuantity = array_sum($request->quantities);
            if ($totalQuantity > $request->product_output) {
                return [
                    'success' => false,
                    'msg' => __('temperature.quantity_exceeds_output')
                ];
            }

            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();

            try {
                // 1. First create the product_temp record
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

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __("temperature.added_success")
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = [
                    'success' => false,
                    'msg' => $e->getMessage()
                ];
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('temperature.edit')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];
    
        $productTemp = ProductTemp::findOrFail($id);
    
        if ($productTemp->business_id != $business_id) {
            abort(403, 'Unauthorized action.');
        }
    
        // Calculate the date 7 days before the saved date
        $sevenDaysAgo = \Carbon::parse($productTemp->date)->subDays(7)->format('Y-m-d');
        
        // Fetch product outputs for the saved date
        $productOutputs = $this->getProductOutputsForDate($sevenDaysAgo);
    
        // Fetch temperatures for the dropdown
        $temperatures = Temperature::select('temperature')
                                   ->distinct()
                                   ->pluck('temperature', 'temperature');
    
        return view('temperature.edit', compact(
            'productTemp',
            'business_id',
            'temperatures',
            'business_locations',
            'bl_attributes',
            'productOutputs',
            'sevenDaysAgo'
        ));
    }

    // public function update(Request $request, $id)
    // {
    //     if (!auth()->user()->can('temperature.edit')) {
    //         abort(403, 'Unauthorized action.');
    //     }
    
    //     try {
    //         // Validate the inputs
    //         $baseValidation = Validator::make($request->all(), [
    //             'date' => 'required|date',
    //             'location_id' => 'required|exists:business_locations,id',
    //             'product_output' => 'required|numeric',
    //             'temperatures' => 'required|array|min:1',
    //             'quantities' => 'required|array|min:1',
    //             'quantities.*' => 'required|numeric|min:0',
    //         ]);
    
    //         if ($baseValidation->fails()) {
    //             return [
    //                 'success' => false,
    //                 'msg' => $baseValidation->errors()->first()
    //             ];
    //         }
    
    //         // Validate that total quantity doesn't exceed product output
    //         $totalQuantity = array_sum($request->quantities);
    //         if ($totalQuantity > $request->product_output) {
    //             return [
    //                 'success' => false,
    //                 'msg' => __('temperature.quantity_exceeds_output')
    //             ];
    //         }
    
    //         $business_id = $request->session()->get('user.business_id');
    //         $productTemp = ProductTemp::findOrFail($id);
    
    //         if ($productTemp->business_id != $business_id) {
    //             abort(403, 'Unauthorized action.');
    //         }
    
    //         DB::beginTransaction();
    
    //         $productTemp->update([
    //             'date' => $request->date,
    //             'location_id' => $request->location_id,
    //             'temperature' => json_encode($request->temperatures),
    //             'quantity' => json_encode($request->quantities),
    //             'product_output' => $request->product_output,
    //         ]);
    
    //         DB::commit();
    
    //         $output = [
    //             'success' => true,
    //             'msg' => __("temperature.updated_success")
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
    
    //         $output = [
    //             'success' => false,
    //             'msg' => __("messages.something_went_wrong")
    //         ];
    //     }
    
    //     return $output;
    // }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('temperature.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Validate the inputs
            $baseValidation = Validator::make($request->all(), [
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
                'product_output' => 'required|numeric',
                'temperatures' => 'required|array|min:1',
                'quantities' => 'required|array|min:1',
                'quantities.*' => 'required|numeric|min:0',
            ]);

            if ($baseValidation->fails()) {
                return [
                    'success' => false,
                    'msg' => $baseValidation->errors()->first()
                ];
            }

            // Validate that total quantity doesn't exceed product output
            $totalQuantity = array_sum($request->quantities);
            if ($totalQuantity > $request->product_output) {
                return [
                    'success' => false,
                    'msg' => __('temperature.quantity_exceeds_output')
                ];
            }

            $business_id = $request->session()->get('user.business_id');
            $productTemp = ProductTemp::findOrFail($id);

            if ($productTemp->business_id != $business_id) {
                abort(403, 'Unauthorized action.');
            }

            DB::beginTransaction();

            try {
                // Get old temperature and quantity data
                $oldTemperatures = json_decode($productTemp->temperature, true);
                $oldQuantities = json_decode($productTemp->quantity, true);

                // Create associative arrays for easier comparison
                $oldData = array_combine($oldTemperatures, $oldQuantities);
                $newData = array_combine($request->temperatures, $request->quantities);

                // Process each temperature in the new data
                foreach ($newData as $temperatureId => $newQuantity) {
                    $oldQuantity = isset($oldData[$temperatureId]) ? $oldData[$temperatureId] : 0;
                    $quantityDifference = $newQuantity - $oldQuantity;

                    // Update temperature table
                    if ($quantityDifference != 0) {
                        DB::table('temperature')
                            ->where('temperature', $temperatureId)
                            ->update([
                                'temp_quantity' => DB::raw('COALESCE(temp_quantity, 0) + ' . $quantityDifference)
                            ]);

                        // Create history record for the change
                        DB::table('temperature_history')->insert([
                            'business_id' => $business_id,
                            'temperature' => $temperatureId,
                            'product_temp_id' => $productTemp->id,
                            'location_id' => $request->location_id,
                            'quantity' => $quantityDifference,
                            'date' => $request->date,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                // Process removed temperatures (temperatures that were in old data but not in new data)
                foreach ($oldData as $temperatureId => $oldQuantity) {
                    if (!isset($newData[$temperatureId])) {
                        // Subtract the old quantity from temperature table
                        DB::table('temperature')
                            ->where('temperature', $temperatureId)
                            ->update([
                                'temp_quantity' => DB::raw('COALESCE(temp_quantity, 0) - ' . $oldQuantity)
                            ]);

                        // Create history record for the removal
                        DB::table('temperature_history')->insert([
                            'business_id' => $business_id,
                            'temperature' => $temperatureId,
                            'product_temp_id' => $productTemp->id,
                            'location_id' => $request->location_id,
                            'quantity' => -$oldQuantity, // Negative quantity to indicate removal
                            'date' => $request->date,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

                // Update the product_temp record
                $productTemp->update([
                    'date' => $request->date,
                    'location_id' => $request->location_id,
                    'temperature' => json_encode($request->temperatures),
                    'quantity' => json_encode($request->quantities),
                    'product_output' => $request->product_output,
                ]);

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __("temperature.updated_success")
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = [
                    'success' => false,
                    'msg' => $e->getMessage()
                ];
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('temperature.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $productTemp = ProductTemp::findOrFail($id);
            $business_id = auth()->user()->business_id;

            // Check if the record belongs to the business
            if ($productTemp->business_id != $business_id) {
                abort(403, 'Unauthorized action.');
            }

            DB::beginTransaction();
            
            try {
                // Get the temperatures and quantities before deleting
                $temperatures = json_decode($productTemp->temperature, true);
                $quantities = json_decode($productTemp->quantity, true);

                // Combine temperatures and quantities for processing
                $temperatureData = array_combine($temperatures, $quantities);

                // Update each temperature's quantity
                foreach ($temperatureData as $temperatureId => $quantity) {
                    // Subtract the quantity from temperature table
                    DB::table('temperature')
                        ->where('temperature', $temperatureId)
                        ->update([
                            'temp_quantity' => DB::raw('COALESCE(temp_quantity, 0) - ' . $quantity)
                        ]);

                    // Add a final history record to track the deletion
                    DB::table('temperature_history')->insert([
                        'business_id' => $business_id,
                        'temperature' => $temperatureId,
                        'product_temp_id' => $productTemp->id,
                        'location_id' => $productTemp->location_id,
                        'quantity' => -$quantity, // Negative quantity to indicate removal
                        'date' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Log the deletion details for debugging
                \Log::info('Temperature Delete Operation:', [
                    'product_temp_id' => $id,
                    'temperatures_affected' => $temperatureData,
                    'deleted_by' => auth()->user()->id
                ]);

                // Delete the product_temp record
                // This will also delete related temperature_history records if you have cascade set up
                $productTemp->delete();

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __("temperature.deleted_success")
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = [
                    'success' => false,
                    'msg' => $e->getMessage()
                ];
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}