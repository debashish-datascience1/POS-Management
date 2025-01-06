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
                    'product_temperature', 
                    'quantity', 
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
                    'product_temperature',
                    'quantity', 
                    'jar', 
                    'packet'
                ])
                ->make(true);
        }

        return view('packing.index');
    }

    public function create()
    {     
        if (!auth()->user()->can('packing.create')) {         
            abort(403, 'Unauthorized action.');     
        }     
        $business_id = request()->session()->get('user.business_id');     
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);     
        $bl_attributes = $business_locations['attributes'];     
        $business_locations = $business_locations['locations'];     
    
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');     
        return view('packing.create', compact(         
            'business_locations',         
            'bl_attributes',         
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

            // Explicitly define valid columns
            $valid_columns = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)', 
                            '100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

            // Prepare arrays to store section data
            $product_temperatures_array = [];
            $quantities_array = [];
            $jars_array = [];
            $packets_array = [];

            // Process each section
            for ($i = 0; $i < count($input['product_temperature']); $i++) {
                $current_temperature = $input['product_temperature'][$i];
                
                // Verify product temperature quantity availability
                $temperature = DB::table('temperature_fixed')
                    ->where('temperature', $current_temperature)
                    ->where('quantity', '>=', $input['quantity'][$i])
                    ->first();

                if (!$temperature) {
                    throw new Exception("Insufficient quantity available for product temperature {$current_temperature}");
                }

                // Prepare stock summary data for this section
                $stock_summary = [];
                
                // Process Jars
                $jarData = [];
                if (!empty($input['jars'][$i])) {
                    foreach ($input['jars'][$i] as $jar) {
                        $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                        
                        $stock_summary[$jar['size']] = 
                            ($stock_summary[$jar['size']] ?? 0) + $jar['quantity'];
                    }
                }

                // Process Packets
                $packetData = [];
                if (!empty($input['packets'][$i])) {
                    foreach ($input['packets'][$i] as $packet) {
                        $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
                        
                        $stock_summary[$packet['size']] = 
                            ($stock_summary[$packet['size']] ?? 0) + $packet['quantity'];
                    }
                }

                // Ensure at least one of jar or packet is provided
                if (empty($jarData) && empty($packetData)) {
                    throw new ValidationException(Validator::make([], []), [
                        'jar_or_packet' => ["Either jar or packet must be provided for product temperature {$current_temperature}"]
                    ]);
                }

                // Store section data in arrays
                $product_temperatures_array[] = $current_temperature;
                $quantities_array[] = $input['quantity'][$i];
                $jars_array[] = !empty($jarData) ? implode(',', $jarData) : null;
                $packets_array[] = !empty($packetData) ? implode(',', $packetData) : null;

                // Check if this temperature already exists in product_stock_summary
                $existing_record = DB::table('product_stock_summary')
                    ->where('temperature', $current_temperature)
                    ->first();

                if ($existing_record) {
                    // Prepare update data that handles null columns
                    $updateData = [];
                    foreach ($valid_columns as $size) {
                        if (isset($stock_summary[$size]) && $stock_summary[$size] > 0) {
                            // Use raw database expression to handle null columns
                            $updateData[$size] = DB::raw("COALESCE(`{$size}`, 0) + {$stock_summary[$size]}");
                        }
                    }

                    // Perform update if there are changes
                    if (!empty($updateData)) {
                        DB::table('product_stock_summary')
                            ->where('temperature', $current_temperature)
                            ->update($updateData);
                    }
                } else {
                    // Insert new record
                    $insertData = ['temperature' => $current_temperature];
                    foreach ($valid_columns as $size) {
                        if (isset($stock_summary[$size]) && $stock_summary[$size] > 0) {
                            $insertData[$size] = $stock_summary[$size];
                        }
                    }
                    $insertData['created_at'] = now();
                    $insertData['updated_at'] = now();
                    DB::table('product_stock_summary')->insert($insertData);
                }

                // Update temperature quantity
                DB::table('temperature_fixed')
                    ->where('temperature', $current_temperature)
                    ->decrement('quantity', $input['quantity'][$i]);
            }

            // Create packing record
            $packing = Packing::create([
                'business_id' => $business_id,
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'product_temperature' => json_encode($product_temperatures_array),
                'quantity' => json_encode($quantities_array),
                'jar' => json_encode($jars_array),
                'packet' => json_encode($packets_array),
                'grand_total' => $input['grand_total']
            ]);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_added_successfully')
            ];

            return redirect()->action([\App\Http\Controllers\PackingController::class, 'index'])->with('status', $output);

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

            return redirect()->back()->with('status', $output);
        }
    }

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

        // Get product temperatures
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');

        return view('packing.edit', compact(
            'packing',
            'business_locations',
            'bl_attributes',
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

            $packing = Packing::findOrFail($id);
            $business_id = $request->session()->get('user.business_id');

            // Get old data to compare and adjust stock summary
            $old_jars = $this->safeJsonDecode($packing->jar);
            $old_packets = $this->safeJsonDecode($packing->packet);
            $old_temperatures = $this->safeJsonDecode($packing->product_temperature);
            $old_quantities = $this->safeJsonDecode($packing->quantity);

            // Prepare arrays to store section data
            $product_temperatures_array = [];
            $quantities_array = [];
            $jars_array = [];
            $packets_array = [];

            // Explicitly define valid columns
            $valid_columns = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)', 
                            '100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

            // Process each section
            for ($i = 0; $i < count($input['product_temperature']); $i++) {
                $current_temperature = $input['product_temperature'][$i];

                // Find the index of this temperature in the old temperatures
                $old_temperature_index = array_search($current_temperature, $old_temperatures);

                // Prepare stock summary to track changes for this specific temperature
                $stock_summary_changes = [];

                // Check if temperature has changed for this section
                $temperature_quantity_changed = $old_temperature_index === false || 
                    $input['quantity'][$i] != $old_quantities[$old_temperature_index];

                // If temperature has changed, verify temperature fixed availability
                if ($temperature_quantity_changed) {
                    $temperature = DB::table('temperature_fixed')
                        ->where('temperature', $current_temperature)
                        ->where('quantity', '>=', $input['quantity'][$i])
                        ->first();

                    if (!$temperature) {
                        throw new Exception("Insufficient quantity available for product temperature {$current_temperature}");
                    }
                }

                // Process Jars
                $jarData = [];
                if (!empty($input['jars'][$i])) {
                    foreach ($input['jars'][$i] as $jar) {
                        $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                        
                        // Track jar quantity changes
                        $old_jar_quantity = $this->getOldItemQuantity($old_jars, $old_temperature_index, $jar['size']);
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
                        $old_packet_quantity = $this->getOldItemQuantity($old_packets, $old_temperature_index, $packet['size']);
                        $quantity_diff = $packet['quantity'] - $old_packet_quantity;
                        
                        $stock_summary_changes[$packet['size']] = 
                            ($stock_summary_changes[$packet['size']] ?? 0) + $quantity_diff;
                    }
                }

                // Ensure at least one of jar or packet is provided for each section
                if (empty($jarData) && empty($packetData)) {
                    throw new ValidationException(Validator::make([], []), [
                        'jar_or_packet' => ["Either jar or packet must be provided for product temperature {$current_temperature}"]
                    ]);
                }

                // Store section data in arrays
                $product_temperatures_array[] = $current_temperature;
                $quantities_array[] = $input['quantity'][$i];
                $jars_array[] = !empty($jarData) ? implode(',', $jarData) : null;
                $packets_array[] = !empty($packetData) ? implode(',', $packetData) : null;

                // Update product stock summary for this specific temperature
                if (!empty($stock_summary_changes)) {
                    // Ensure only valid columns are used
                    $filtered_stock_summary_changes = array_intersect_key(
                        $stock_summary_changes, 
                        array_flip($valid_columns)
                    );

                    // Perform stock summary update
                    if (!empty($filtered_stock_summary_changes)) {
                        // Prepare update data that handles null columns
                        $updateData = [];
                        foreach ($valid_columns as $size) {
                            if (isset($filtered_stock_summary_changes[$size]) && $filtered_stock_summary_changes[$size] != 0) {
                                // Use raw database expression to handle null columns
                                $updateData[$size] = DB::raw("COALESCE(`{$size}`, 0) + {$filtered_stock_summary_changes[$size]}");
                            }
                        }

                        // Perform update if there are changes
                        if (!empty($updateData)) {
                            DB::table('product_stock_summary')
                                ->where('temperature', $current_temperature)
                                ->update($updateData);
                        }
                    }
                }
            }

            // Update the packing record
            $packing->update([
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'product_temperature' => json_encode($product_temperatures_array),
                'quantity' => json_encode($quantities_array),
                'jar' => json_encode($jars_array),
                'packet' => json_encode($packets_array),
                'grand_total' => $input['grand_total']
            ]);

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

            // Explicitly define valid columns
            $valid_columns = ['5L', '5L(sp)', '10L', '10L(sp)', '20L', '20L(sp)', 
                            '100ML', '100ML(sp)', '200ML', '200ML(sp)', '500ML', '500ML(sp)'];

            // Process each temperature section
            for ($i = 0; $i < count($temperatures); $i++) {
                $current_temperature = $temperatures[$i];

                // Restore the temperature quantity
                $updated = DB::table('temperature_fixed')
                    ->where('temperature', $current_temperature)
                    ->increment('quantity', $quantities[$i]);

                if (!$updated) {
                    throw new Exception("Temperature {$current_temperature} not found");
                }

                // Prepare stock summary reduction for this specific temperature
                $stock_summary_reduction = [];

                // Process Jars
                if (!empty($jars[$i])) {
                    foreach (explode(',', $jars[$i]) as $jar_item) {
                        list($size, $quantity, $price) = explode(':', $jar_item);
                        $stock_summary_reduction[$size] = 
                            ($stock_summary_reduction[$size] ?? 0) + $quantity;
                    }
                }

                // Process Packets
                if (!empty($packets[$i])) {
                    foreach (explode(',', $packets[$i]) as $packet_item) {
                        list($size, $quantity, $price) = explode(':', $packet_item);
                        $stock_summary_reduction[$size] = 
                            ($stock_summary_reduction[$size] ?? 0) + $quantity;
                    }
                }

                // Update product stock summary for this specific temperature
                if (!empty($stock_summary_reduction)) {
                    // Ensure only valid columns are used
                    $filtered_stock_summary_reduction = array_intersect_key(
                        $stock_summary_reduction, 
                        array_flip($valid_columns)
                    );

                    if (!empty($filtered_stock_summary_reduction)) {
                        // Prepare update data that handles null columns and ensures non-negative values
                        $updateData = [];
                        foreach ($valid_columns as $size) {
                            if (isset($filtered_stock_summary_reduction[$size]) && $filtered_stock_summary_reduction[$size] > 0) {
                                // Use raw database expression to handle null columns and ensure non-negative values
                                $updateData[$size] = DB::raw("GREATEST(COALESCE(`{$size}`, 0) - {$filtered_stock_summary_reduction[$size]}, 0)");
                            }
                        }

                        // Perform update if there are changes
                        if (!empty($updateData)) {
                            DB::table('product_stock_summary')
                                ->where('temperature', $current_temperature)
                                ->update($updateData);
                        }
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
