<?php

namespace App\Http\Controllers;

use App\Packing;
use App\Product;
use App\Temperature;
use App\ProductionUnit;
use App\BusinessLocation;
use App\ProductionStock;
use App\PackingStock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class PackingController extends Controller
{


    public function formatPackingData($data)
    {
        if (empty($data)) {
            return '';
        }

        try {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            if (!is_array($data)) {
                return '';
            }

            $formatted = [];

            foreach ($data as $item) {
                if (empty($item)) {
                    continue;
                }

                $entries = [];
                $itemData = is_string($item) ? explode(',', $item) : (array)$item;

                foreach ($itemData as $entry) {
                    // Validate entry format
                    $parts = is_string($entry) ? explode(':', $entry) : (array)$entry;
                    if (count($parts) !== 3) {
                        continue;
                    }

                    [$size, $quantity, $price] = $parts;

                    // Clean and validate numeric values
                    $quantity = trim($quantity);
                    $price = trim($price);

                    // Convert to float and validate numeric values
                    $cleanQuantity = filter_var($quantity, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $cleanPrice = filter_var(str_replace(['₹', ','], '', $price), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                    if ($cleanQuantity !== false && $cleanPrice !== false) {
                        $formattedQuantity = number_format((float)$cleanQuantity, 2, '.', '');
                        $formattedPrice = number_format((float)$cleanPrice, 2, '.', '');

                        $entries[] = sprintf(
                            "%s: %s @ ₹%s",
                            trim($size),
                            $formattedQuantity,
                            $formattedPrice
                        );
                    }
                }

                if (!empty($entries)) {
                    $formatted[] = implode('<br>', $entries);
                }
            }

            return !empty($formatted)
                ? implode('<hr style="margin: 5px 0">', $formatted)
                : '';
        } catch (\Exception $e) {
            \Log::error('Error formatting packing data: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    public function index()
    {
        if (!auth()->user()->can('packing.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $packings = Packing::where('business_id', $business_id)
                ->select([
                    'id', 
                    'date', 
<<<<<<< HEAD
                    'temperature', 
                    'product_temperature', 
                    'quantity', 
                    'mix', 
                    'total', 
=======
                    'product_temperature', 
                    'quantity', 
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                    'jar', 
                    'packet', 
                    'grand_total', 
                    'created_at'
                ]);

            return DataTables::of($packings)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                    data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                <li><a href="' . action([self::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                                <li><a href="#" data-href="' . action([self::class, 'destroy'], [$row->id]) . '" class="delete_packing_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
                                </ul>
                            </div>';
                    return $html;
                })
                ->editColumn('product_temperature', function ($row) {
                    try {
                        $productTemps = is_array($row->product_temperature) ? $row->product_temperature : (is_string($row->product_temperature) ? json_decode($row->product_temperature, true) : []);
                        return implode('<br>', array_map(function ($temp) {
                            return is_numeric($temp) ? number_format((float)$temp, 1) . '°C' : '0.0°C';
                        }, $productTemps ?: []));
                    } catch (\Exception $e) {
                        return '0.0°C';
                    }
                })
                ->editColumn('product_temperature', function ($row) {
                    try {
                        $productTemps = is_array($row->product_temperature) ? $row->product_temperature : (is_string($row->product_temperature) ? json_decode($row->product_temperature, true) : []);
                        return implode('<br>', array_map(function ($temp) {
                            return is_numeric($temp) ? number_format((float)$temp, 1) . '°C' : '0.0°C';
                        }, $productTemps ?: []));
                    } catch (\Exception $e) {
                        return '0.0°C';
                    }
                })
                ->editColumn('quantity', function ($row) {
                    try {
                        $quantities = is_array($row->quantity) ? $row->quantity : (is_string($row->quantity) ? json_decode($row->quantity, true) : []);
                        return implode('<br>', array_map(function ($qty) {
                            return is_numeric($qty) ? number_format((float)$qty, 2) : '0.00';
                        }, $quantities ?: []));
                    } catch (\Exception $e) {
                        return '0.00';
                    }
                })
                ->editColumn('jar', function ($row) {
                    return $this->formatPackingData($row->jar);
                })
                ->editColumn('packet', function ($row) {
                    return $this->formatPackingData($row->packet);
                })
                ->editColumn('grand_total', function ($row) {
                    return is_numeric($row->grand_total) ?
                        number_format((float)$row->grand_total, 2) : '0.00';
                })
                ->editColumn('date', function ($row) {
                    return \Carbon\Carbon::parse($row->date)->format('d/m/Y');
                })
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns([
                    'action', 
<<<<<<< HEAD
                    'temperature', 
                    'product_temperature', 
                    'quantity', 
                    'mix', 
                    'total', 
=======
                    'product_temperature',
                    'quantity', 
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                    'jar', 
                    'packet'
                ])
                ->make(true);
        }

        return view('packing.index');
    }

    // public function create()
    // {
    //     if (!auth()->user()->can('packing.create')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $business_id = request()->session()->get('user.business_id');
    //     $business_locations = BusinessLocation::forDropdown($business_id, false, true);
    //     $bl_attributes = $business_locations['attributes'];
    //     $business_locations = $business_locations['locations'];

    //     $temperatures = Temperature::select('temperature')
    //         ->distinct()
    //         ->pluck('temperature', 'temperature');

    //     return view('packing.create', compact(
    //         'business_locations',
    //         'bl_attributes',
    //         'temperatures'
    //     ));
    // }

    public function create()
    {     
        if (!auth()->user()->can('packing.create')) {         
            abort(403, 'Unauthorized action.');     
        }     
        $business_id = request()->session()->get('user.business_id');     
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);     
        $bl_attributes = $business_locations['attributes'];     
        $business_locations = $business_locations['locations'];     
<<<<<<< HEAD
        $temperatures = Temperature::select('temperature')         
            ->distinct()         
            ->pluck('temperature', 'temperature');     
=======
    
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');     
        return view('packing.create', compact(         
            'business_locations',         
            'bl_attributes',         
<<<<<<< HEAD
            'temperatures',         
=======
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
            'product_temperatures'     
        )); 
    }

    public function getTemperatureQuantity1(Request $request)
    {
        try {
            $temperature = $request->input('temperature');

            $tempQuantity = Temperature::where('temperature', $temperature)
                ->value('temp_quantity');

            if ($tempQuantity !== null) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'temp_quantity' => $tempQuantity
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Temperature quantity not found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching temperature quantity'
            ]);
        }
    }

    public function getTemperatureQuantity(Request $request)
    {
        try {
            $temperature = $request->input('temperature');

            $tempQuantity = DB::table('temperature_fixed')
                ->where('temperature', $temperature)
                ->value('quantity');

            if ($tempQuantity !== null) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'temp_quantity' => $tempQuantity
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Temperature quantity not found'
            ]);
            } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching temperature quantity'
                ]);
            }
    }

    public function getPackingStock($location_id)
    {
        try {
            $packingStock = PackingStock::where('location_id', $location_id)
                ->select('total')
                ->first();

            if (!$packingStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'No packing stock found for this location'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => number_format($packingStock->total, 2, '.', ''), // Format to 2 decimal places
                    'original_total' => $packingStock->total // Keep original value for calculations
                ]
            ]);
        } catch (Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching packing stock'
            ]);
        }
    }

    public function validateStock(Request $request)
    {
        try {
            $location_id = $request->location_id;
            $requested_amount = $request->amount;

            $packingStock = PackingStock::where('location_id', $location_id)
                ->select('total')
                ->first();

            if (!$packingStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'No packing stock found'
                ]);
            }

            $isValid = $requested_amount <= $packingStock->total;
            $remaining = $packingStock->total - $requested_amount;

            return response()->json([
                'success' => true,
                'data' => [
                    'isValid' => $isValid,
                    'remaining' => number_format($remaining, 2, '.', ''),
                    'message' => $isValid ? 'Stock available' : 'Insufficient stock'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating stock'
            ]);
        }
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('packing.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->validate([
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
<<<<<<< HEAD
                'temperatures' => 'required|array',
                'temperatures.*' => 'required|string',
=======
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                'product_temperature' => 'required|array', 
                'product_temperature.*' => 'required|string', 
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0',
                'jars' => 'nullable|array',
                'jars.*' => 'array',
                'jars.*.*' => 'array',
                'jars.*.*.size' => 'required|in:5L,5L(sp),10L,10L(sp),20L,20L(sp)',
                'jars.*.*.quantity' => 'required|integer|min:1',
                'jars.*.*.price' => 'required|numeric|min:0',
                'packets' => 'nullable|array',
                'packets.*' => 'array',
                'packets.*.*' => 'array',
                'packets.*.*.size' => 'required|in:100ML,100ML(sp),200ML,200ML(sp),500ML,500ML(sp)',
                'packets.*.*.quantity' => 'required|integer|min:1',
                'packets.*.*.price' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            $business_id = $request->session()->get('user.business_id');

            // Prepare arrays to store section data
<<<<<<< HEAD
            $temperatures_array = [];
            $product_temperatures_array = []; // New array for product temperatures
=======
            $product_temperatures_array = [];
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
            $quantities_array = [];
            $jars_array = [];
            $packets_array = [];

            // Prepare a summary of jar and packet quantities by size
            $stock_summary = [];

            // Process each section
            for ($i = 0; $i < count($input['product_temperature']); $i++) {
                // Verify product temperature quantity availability using DB query
                $temperature = DB::table('temperature_fixed')
                    ->where('temperature', $input['product_temperature'][$i])
                    ->where('quantity', '>=', $input['quantity'][$i])
                    ->first();

                if (!$temperature) {
                    throw new Exception("Insufficient quantity available for product temperature {$input['product_temperature'][$i]}");
                }

                // Process Jars
                $jarData = [];
                if (!empty($input['jars'][$i])) {
                    foreach ($input['jars'][$i] as $jar) {
                        $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                        
                        // Track jar quantities
                        $stock_summary[$jar['size']] = 
                            ($stock_summary[$jar['size']] ?? 0) + $jar['quantity'];
                    }
                }

                // Process Packets
                $packetData = [];
                if (!empty($input['packets'][$i])) {
                    foreach ($input['packets'][$i] as $packet) {
                        $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
                        
                        // Track packet quantities
                        $stock_summary[$packet['size']] = 
                            ($stock_summary[$packet['size']] ?? 0) + $packet['quantity'];
                    }
                }

                // Ensure at least one of jar or packet is provided for each section
                if (empty($jarData) && empty($packetData)) {
                    throw new ValidationException(Validator::make([], []), [
                        'jar_or_packet' => ["Either jar or packet must be provided for product temperature {$input['product_temperature'][$i]}"]
                    ]);
                }

                // Store section data in arrays
<<<<<<< HEAD
                $temperatures_array[] = $input['temperatures'][$i];
                $product_temperatures_array[] = $input['product_temperature'][$i]; // Add product temperature
=======
                $product_temperatures_array[] = $input['product_temperature'][$i];
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                $quantities_array[] = $input['quantity'][$i];
                $jars_array[] = !empty($jarData) ? implode(',', $jarData) : null;
                $packets_array[] = !empty($packetData) ? implode(',', $packetData) : null;

                // Update temperature quantity using DB query
                DB::table('temperature_fixed')
                    ->where('temperature', $input['product_temperature'][$i])
                    ->decrement('quantity', $input['quantity'][$i]);
            }

            // Create single packing record with all sections
            $packing = Packing::create([
                'business_id' => $business_id,
                'date' => $input['date'],
                'location_id' => $input['location_id'],
<<<<<<< HEAD
                'temperature' => json_encode($temperatures_array),
                'product_temperature' => json_encode($product_temperatures_array), // Add product_temperature to the creation
=======
                'product_temperature' => json_encode($product_temperatures_array),
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                'quantity' => json_encode($quantities_array),
                'jar' => json_encode($jars_array),
                'packet' => json_encode($packets_array),
                'grand_total' => $input['grand_total']
            ]);

            // Explicitly define valid columns
            $valid_columns = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)', 
                            '100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

            // Ensure only valid columns are used
            $filtered_stock_summary = array_intersect_key(
                $stock_summary, 
                array_flip($valid_columns)
            );

            // Debug logging
            \Log::info('Filtered Stock Summary', [
                'original' => $stock_summary,
                'filtered' => $filtered_stock_summary
            ]);

            // Update or Insert into product_stock_summary
            $existing_summary = DB::table('product_stock_summary')->first();

            if ($existing_summary) {
                // Prepare update query
                $updates = [];
                $bindings = [];

                foreach ($valid_columns as $size) {
                    if (isset($filtered_stock_summary[$size]) && $filtered_stock_summary[$size] > 0) {
                        $updates[] = "`{$size}` = COALESCE(`{$size}`, 0) + ?";
                        $bindings[] = $filtered_stock_summary[$size];
                    }
                }

                if (!empty($updates)) {
                    $updateQuery = "UPDATE product_stock_summary SET " . implode(', ', $updates);
                    
                    \Log::info('Update Query Details', [
                        'query' => $updateQuery,
                        'bindings' => $bindings
                    ]);

                    DB::update($updateQuery, $bindings);
                }
            } else {
                // Prepare insert data
                $insertData = [];
                foreach ($valid_columns as $size) {
                    if (isset($filtered_stock_summary[$size]) && $filtered_stock_summary[$size] > 0) {
                        $insertData[$size] = $filtered_stock_summary[$size];
                    }
                }

                // Add timestamps
                $insertData['created_at'] = now();
                $insertData['updated_at'] = now();

                // Insert only if we have data
                if (!empty($insertData)) {
                    \Log::info('Insert Data', ['data' => $insertData]);
                    DB::table('product_stock_summary')->insert($insertData);
                }
            }

            // Optional: Log the stock summary for debugging
            \Log::info('Stock Summary', $stock_summary);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_added_successfully')
            ];
        } catch (ValidationException $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong')
            ];
        }

        return redirect()->action([\App\Http\Controllers\PackingController::class, 'index'])->with('status', $output);
    }

    // public function edit($id)
    // {
    //     if (!auth()->user()->can('packing.edit')) {
    //         abort(403, 'Unauthorized action.');
    //     }
    
    //     $business_id = request()->session()->get('user.business_id');
    //     $packing = Packing::where('business_id', $business_id)->findOrFail($id);
        
    //     // Format the date to yyyy-MM-dd
    //     $packing->date = date('Y-m-d', strtotime($packing->date));
        
    //     $business_locations = BusinessLocation::forDropdown($business_id, false, true);
    //     $bl_attributes = $business_locations['attributes'];
    //     $business_locations = $business_locations['locations'];
    
    //     // Get temperatures for dropdown and create an associative array
    //     $temperatures_list = Temperature::select('temperature')
    //         ->distinct()
    //         ->pluck('temperature', 'temperature')
    //         ->toArray();
    
    //     return view('packing.edit', compact(
    //         'packing',
    //         'business_locations',
    //         'bl_attributes',
    //         'temperatures_list'
    //     ));
    // }

    public function edit($id)
    {
        if (!auth()->user()->can('packing.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $packing = Packing::where('business_id', $business_id)->findOrFail($id);
        
        // Format the date to yyyy-MM-dd
        $packing->date = date('Y-m-d', strtotime($packing->date));
        
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

<<<<<<< HEAD
        // Get temperatures for dropdown and create an associative array
        $temperatures_list = Temperature::select('temperature')
            ->distinct()
            ->pluck('temperature', 'temperature')
            ->toArray();

=======
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
        // Get product temperatures
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');

        return view('packing.edit', compact(
            'packing',
            'business_locations',
            'bl_attributes',
<<<<<<< HEAD
            'temperatures_list',
=======
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
            'product_temperatures'
        ));
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('packing.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->validate([
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
<<<<<<< HEAD
                'temperatures' => 'required|array',
                'temperatures.*' => 'required|string',
                'quantities' => 'required|array',
                'quantities.*' => 'required|numeric|min:0',
                'mix' => 'required|array',
                'mix.*' => 'required|numeric|min:0',
                'product_temperature' => 'required|array',
                'product_temperature.*' => 'required|string',
                'total' => 'required|array',
                'total.*' => 'required|numeric|min:0',
=======
                'product_temperature' => 'required|array', 
                'product_temperature.*' => 'required|string', 
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0',
>>>>>>> a272fb2e0d36f2f4d21b605d8f5982cb2b6f11b1
                'jars' => 'nullable|array',
                'jars.*' => 'array',
                'jars.*.*' => 'array',
                'jars.*.*.size' => 'required|in:5L,5L(sp),10L,10L(sp),20L,20L(sp)',
                'jars.*.*.quantity' => 'required|integer|min:1',
                'jars.*.*.price' => 'required|numeric|min:0',
                'packets' => 'nullable|array',
                'packets.*' => 'array',
                'packets.*.*' => 'array',
                'packets.*.*.size' => 'required|in:100ML,100ML(sp),200ML,200ML(sp),500ML,500ML(sp)',
                'packets.*.*.quantity' => 'required|integer|min:1',
                'packets.*.*.price' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            $packing = Packing::findOrFail($id);
            $business_id = $request->session()->get('user.business_id');

            // Get old data to compare and adjust stock summary
            $old_jars = $this->safeJsonDecode($packing->jar);
            $old_packets = $this->safeJsonDecode($packing->packet);

            // Prepare arrays to store section data
            $product_temperatures_array = [];
            $quantities_array = [];
            $jars_array = [];
            $packets_array = [];
            $product_temperatures_array = []; // New array for product temperatures

            // Prepare stock summary to track changes
            $stock_summary_changes = [];

            // Process each section
            for ($i = 0; $i < count($input['product_temperature']); $i++) {
                // Process Jars
                $jarData = [];
                if (!empty($input['jars'][$i])) {
                    foreach ($input['jars'][$i] as $jar) {
                        $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                        
                        // Track jar quantity changes
                        $old_jar_quantity = $this->getOldItemQuantity($old_jars, $i, $jar['size']);
                        $quantity_diff = $jar['quantity'] - $old_jar_quantity;
                        
                        $stock_summary_changes[$jar['size']] = 
                            ($stock_summary_changes[$jar['size']] ?? 0) + $quantity_diff;
                    }
                }

                // Process Packets
                $packetData = [];
                if (!empty($input['packets'][$i])) {
                    foreach ($input['packets'][$i] as $packet) {
                        $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
                        
                        // Track packet quantity changes
                        $old_packet_quantity = $this->getOldItemQuantity($old_packets, $i, $packet['size']);
                        $quantity_diff = $packet['quantity'] - $old_packet_quantity;
                        
                        $stock_summary_changes[$packet['size']] = 
                            ($stock_summary_changes[$packet['size']] ?? 0) + $quantity_diff;
                    }
                }

                // Ensure at least one of jar or packet is provided for each section
                if (empty($jarData) && empty($packetData)) {
                    throw new ValidationException(Validator::make([], []), [
                        'jar_or_packet' => ["Either jar or packet must be provided for product temperature {$input['product_temperature'][$i]}"]
                    ]);
                }

                // Store section data in arrays
                $product_temperatures_array[] = $input['product_temperature'][$i];
                $quantities_array[] = $input['quantity'][$i];
                $jars_array[] = !empty($jarData) ? implode(',', $jarData) : null;
                $packets_array[] = !empty($packetData) ? implode(',', $packetData) : null;
                $product_temperatures_array[] = $input['product_temperature'][$i]; // Add product temperature
            }

            // Update the packing record
            $packing->update([
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'product_temperature' => json_encode($product_temperatures_array),
                'quantity' => json_encode($quantities_array),
                'jar' => json_encode($jars_array),
                'packet' => json_encode($packets_array),
                'product_temperature' => json_encode($product_temperatures_array), // Add product temperature
                'grand_total' => $input['grand_total']
            ]);

            // Update product stock summary
            if (!empty($stock_summary_changes)) {
                // Explicitly define valid columns
                $valid_columns = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)', 
                                '100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

                // Ensure only valid columns are used
                $filtered_stock_summary_changes = array_intersect_key(
                    $stock_summary_changes, 
                    array_flip($valid_columns)
                );

                $existing_summary = DB::table('product_stock_summary')->first();

                if ($existing_summary) {
                    // Prepare update query
                    $updates = [];
                    $bindings = [];

                    foreach ($valid_columns as $size) {
                        if (isset($filtered_stock_summary_changes[$size]) && $filtered_stock_summary_changes[$size] != 0) {
                            $updates[] = "`{$size}` = COALESCE(`{$size}`, 0) + ?";
                            $bindings[] = $filtered_stock_summary_changes[$size];
                        }
                    }

                    if (!empty($updates)) {
                        $updateQuery = "UPDATE product_stock_summary SET " . implode(', ', $updates);
                        DB::update($updateQuery, $bindings);
                    }
                } else {
                    // Prepare insert data
                    $insertData = [];
                    foreach ($valid_columns as $size) {
                        if (isset($filtered_stock_summary_changes[$size]) && $filtered_stock_summary_changes[$size] != 0) {
                            $insertData[$size] = $filtered_stock_summary_changes[$size];
                        }
                    }

                    // Add timestamps
                    $insertData['created_at'] = now();
                    $insertData['updated_at'] = now();

                    // Insert only if we have data
                    if (!empty($insertData)) {
                        DB::table('product_stock_summary')->insert($insertData);
                    }
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_updated_successfully')
            ];
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation failed: " . json_encode($e->errors()));
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong')
            ];
        }

        return redirect()->action([\App\Http\Controllers\PackingController::class, 'index'])->with('status', $output);
    }

    private function getOldItemQuantity($old_items, $section_index, $size)
    {
        if (empty($old_items) || !isset($old_items[$section_index])) {
            return 0;
        }

        $old_section_items = explode(',', $old_items[$section_index]);
        foreach ($old_section_items as $item) {
            $item_parts = explode(':', $item);
            if ($item_parts[0] === $size) {
                return (int)$item_parts[1];
            }
        }

        return 0;
    }

    private function safeJsonDecode($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('packing.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $packing = Packing::where('business_id', $business_id)->findOrFail($id);

            DB::beginTransaction();

            // Get the temperatures and quantities from the JSON stored in the packing record
            $temperatures = json_decode($packing->product_temperature, true);
            $quantities = json_decode($packing->quantity, true);
            $jars = $this->safeJsonDecode($packing->jar);
            $packets = $this->safeJsonDecode($packing->packet);

            // Restore the temperature quantities using DB query
            for ($i = 0; $i < count($temperatures); $i++) {
                // Update quantity in temperature_fixed table
                $updated = DB::table('temperature_fixed')
                    ->where('temperature', $temperatures[$i])
                    ->increment('quantity', $quantities[$i]);

                if (!$updated) {
                    throw new Exception("Temperature {$temperatures[$i]} not found");
                }
            }

            // Prepare stock summary reduction
            $stock_summary_reduction = [];

            // Process jar quantities
            if (!empty($jars)) {
                foreach ($jars as $i => $jar_section) {
                    if (!empty($jar_section)) {
                        foreach (explode(',', $jar_section) as $jar_item) {
                            list($size, $quantity, $price) = explode(':', $jar_item);
                            $stock_summary_reduction[$size] = 
                                ($stock_summary_reduction[$size] ?? 0) + $quantity;
                        }
                    }
                }
            }

            // Process packet quantities
            if (!empty($packets)) {
                foreach ($packets as $i => $packet_section) {
                    if (!empty($packet_section)) {
                        foreach (explode(',', $packet_section) as $packet_item) {
                            list($size, $quantity, $price) = explode(':', $packet_item);
                            $stock_summary_reduction[$size] = 
                                ($stock_summary_reduction[$size] ?? 0) + $quantity;
                        }
                    }
                }
            }

            // Update product stock summary
            if (!empty($stock_summary_reduction)) {
                $existing_summary = DB::table('product_stock_summary')->first();

                if ($existing_summary) {
                    // Prepare update query
                    $updates = [];
                    $bindings = [];

                    foreach ($stock_summary_reduction as $size => $quantity) {
                        $updates[] = "`{$size}` = GREATEST(COALESCE(`{$size}`, 0) - ?, 0)";
                        $bindings[] = $quantity;
                    }

                    if (!empty($updates)) {
                        $updateQuery = "UPDATE product_stock_summary SET " . implode(', ', $updates);
                        
                        \Log::info('Stock Summary Reduction', [
                            'query' => $updateQuery,
                            'bindings' => $bindings
                        ]);

                        DB::update($updateQuery, $bindings);
                    }
                }
            }

            // Delete the packing record
            $packing->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_deleted_successfully')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

}
