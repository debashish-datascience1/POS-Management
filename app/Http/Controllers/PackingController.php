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
                ->select(['id', 'date', 'temperature', 'quantity', 'mix', 'total', 'jar', 'packet', 'grand_total', 'created_at']);

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
                ->editColumn('temperature', function ($row) {
                    try {
                        $temps = is_array($row->temperature) ? $row->temperature : (is_string($row->temperature) ? json_decode($row->temperature, true) : []);
                        return implode('<br>', array_map(function ($temp) {
                            return is_numeric($temp) ? number_format((float)$temp, 1) . '°C' : '0.0°C';
                        }, $temps ?: []));
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
                ->editColumn('mix', function ($row) {
                    try {
                        $mixes = is_array($row->mix) ? $row->mix : (is_string($row->mix) ? json_decode($row->mix, true) : []);
                        return implode('<br>', array_map(function ($mix) {
                            return is_numeric($mix) ? number_format((float)$mix, 2) . '%' : '0.00%';
                        }, $mixes ?: []));
                    } catch (\Exception $e) {
                        return '0.00%';
                    }
                })
                ->editColumn('total', function ($row) {
                    try {
                        $totals = is_array($row->total) ? $row->total : (is_string($row->total) ? json_decode($row->total, true) : []);
                        return implode('<br>', array_map(function ($total) {
                            return is_numeric($total) ? number_format((float)$total, 2) : '0.00';
                        }, $totals ?: []));
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
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action', 'temperature', 'quantity', 'mix', 'total', 'jar', 'packet'])
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

        $temperatures = Temperature::select('temperature')
            ->distinct()
            ->pluck('temperature', 'temperature');

        return view('packing.create', compact(
            'business_locations',
            'bl_attributes',
            'temperatures'
        ));
    }

    public function getTemperatureQuantity(Request $request)
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

    // public function store(Request $request)
    // {
    //     if (!auth()->user()->can('packing.create')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $input = $request->validate([
    //             'product_output' => 'required|numeric',
    //             'mix' => 'required|numeric|min:0',
    //             'jar' => 'nullable|array',
    //             'jar.*.size' => 'required_with:jar|in:5L,10L,20L',
    //             'jar.*.quantity' => 'required_with:jar|integer|min:1',
    //             'jar.*.price' => 'required_with:jar|numeric|min:0',
    //             'packet' => 'nullable|array',
    //             'packet.*.size' => 'required_with:packet|in:100ML,200ML,500ML',
    //             'packet.*.quantity' => 'required_with:packet|integer|min:1',
    //             'packet.*.price' => 'required_with:packet|numeric|min:0',
    //             'total' => 'required|numeric',
    //             'grand_total' => 'required|numeric',
    //             'date' => 'required|date',
    //             'location_id' => 'required|exists:business_locations,id',
    //         ]);

    //         $input['business_id'] = $request->session()->get('user.business_id');

    //         // Convert jar array to a formatted string
    //         $jarData = [];
    //         if (!empty($input['jar'])) {
    //             foreach ($input['jar'] as $jar) {
    //                 $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
    //             }
    //             $input['jar'] = implode(',', $jarData);
    //         } else {
    //             $input['jar'] = null;
    //         }

    //         // Convert packet array to a formatted string
    //         $packetData = [];
    //         if (!empty($input['packet'])) {
    //             foreach ($input['packet'] as $packet) {
    //                 $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
    //             }
    //             $input['packet'] = implode(',', $packetData);
    //         } else {
    //             $input['packet'] = null;
    //         }

    //         // Ensure at least one of jar or packet is provided
    //         if (empty($input['jar']) && empty($input['packet'])) {
    //             throw new ValidationException(Validator::make([], []), [
    //                 'jar_or_packet' => ['Either jar or packet must be provided.']
    //             ]);
    //         }

    //         // Remove product_id from the input array as it's no longer needed
    //         unset($input['product_id']);

    //         $packing = Packing::create($input);

    //         $packingStock = PackingStock::where('location_id', $request->location_id)->first();
    //         if (!$packingStock || $packingStock->total < $request->product_output) {
    //             throw new Exception('Insufficient stock available');
    //         }

    //         // Update the packing stock
    //         $packingStock->total -= $request->product_output;
    //         $packingStock->save();

    //         DB::commit();

    //         $output = [
    //             'success' => true,
    //             'msg' => __('lang_v1.packing_added_successfully')
    //         ];

    //     } catch (ValidationException $e) {
    //         DB::rollBack();
    //         return redirect()->back()->withErrors($e->errors())->withInput();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

    //         $output = [
    //             'success' => false,
    //             'msg' => __('messages.something_went_wrong')
    //         ];
    //     }

    //     return redirect()->action([\App\Http\Controllers\PackingController::class, 'index'])->with('status', $output);
    // }
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
                'temperatures' => 'required|array',
                'temperatures.*' => 'required|string',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0',
                'mix' => 'required|array',
                'mix.*' => 'required|numeric|min:0',
                'total' => 'required|array',
                'total.*' => 'required|numeric|min:0',
                'jars' => 'nullable|array',
                'jars.*' => 'array',
                'jars.*.*' => 'array',
                'jars.*.*.size' => 'required|in:5L,10L,20L',
                'jars.*.*.quantity' => 'required|integer|min:1',
                'jars.*.*.price' => 'required|numeric|min:0',
                'packets' => 'nullable|array',
                'packets.*' => 'array',
                'packets.*.*' => 'array',
                'packets.*.*.size' => 'required|in:100ML,200ML,500ML',
                'packets.*.*.quantity' => 'required|integer|min:1',
                'packets.*.*.price' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            $business_id = $request->session()->get('user.business_id');

            // Prepare arrays to store section data
            $temperatures_array = [];
            $quantities_array = [];
            $mix_array = [];
            $total_array = [];
            $jars_array = [];
            $packets_array = [];

            // Process each section
            for ($i = 0; $i < count($input['temperatures']); $i++) {
                // Verify temperature quantity availability
                $temperature = Temperature::where('temperature', $input['temperatures'][$i])
                    ->where('temp_quantity', '>=', $input['quantity'][$i])
                    ->first();

                if (!$temperature) {
                    throw new Exception("Insufficient quantity available for temperature {$input['temperatures'][$i]}");
                }

                // Format jar data for this section
                $jarData = [];
                if (!empty($input['jars'][$i])) {
                    foreach ($input['jars'][$i] as $jar) {
                        $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                    }
                }

                // Format packet data for this section
                $packetData = [];
                if (!empty($input['packets'][$i])) {
                    foreach ($input['packets'][$i] as $packet) {
                        $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
                    }
                }

                // Ensure at least one of jar or packet is provided for each section
                if (empty($jarData) && empty($packetData)) {
                    throw new ValidationException(Validator::make([], []), [
                        'jar_or_packet' => ["Either jar or packet must be provided for temperature {$input['temperatures'][$i]}"]
                    ]);
                }

                // Store section data in arrays
                $temperatures_array[] = $input['temperatures'][$i];
                $quantities_array[] = $input['quantity'][$i];
                $mix_array[] = $input['mix'][$i];
                $total_array[] = $input['total'][$i];
                $jars_array[] = !empty($jarData) ? implode(',', $jarData) : null;
                $packets_array[] = !empty($packetData) ? implode(',', $packetData) : null;

                // Update temperature quantity
                $temperature->temp_quantity -= $input['quantity'][$i];
                $temperature->save();
            }

            // Create single packing record with all sections
            $packing = Packing::create([
                'business_id' => $business_id,
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'temperature' => json_encode($temperatures_array),
                'quantity' => json_encode($quantities_array),
                'mix' => json_encode($mix_array),
                'total' => json_encode($total_array),
                'jar' => json_encode($jars_array),
                'packet' => json_encode($packets_array),
                'grand_total' => $input['grand_total']
            ]);

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
    
        // Get temperatures for dropdown and create an associative array
        $temperatures_list = Temperature::select('temperature')
            ->distinct()
            ->pluck('temperature', 'temperature')
            ->toArray();
    
        return view('packing.edit', compact(
            'packing',
            'business_locations',
            'bl_attributes',
            'temperatures_list'
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
                'product_output' => 'required|numeric',
                'mix' => 'required|numeric|min:0',
                'jar' => 'nullable|array',
                'jar.*.size' => 'required_with:jar|in:5L,10L,20L',
                'jar.*.quantity' => 'required_with:jar|integer|min:1',
                'jar.*.price' => 'required_with:jar|numeric|min:0',
                'packet' => 'nullable|array',
                'packet.*.size' => 'required_with:packet|in:100ML,200ML,500ML',
                'packet.*.quantity' => 'required_with:packet|integer|min:1',
                'packet.*.price' => 'required_with:packet|numeric|min:0',
                'total' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
            ]);

            $packing = Packing::findOrFail($id);

            // Convert jar array to a formatted string
            $jarData = [];
            if (!empty($input['jar'])) {
                foreach ($input['jar'] as $jar) {
                    $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
                }
                $input['jar'] = implode(',', $jarData);
            } else {
                $input['jar'] = null;
            }

            // Convert packet array to a formatted string
            $packetData = [];
            if (!empty($input['packet'])) {
                foreach ($input['packet'] as $packet) {
                    $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
                }
                $input['packet'] = implode(',', $packetData);
            } else {
                $input['packet'] = null;
            }

            // Ensure at least one of jar or packet is provided
            if (empty($input['jar']) && empty($input['packet'])) {
                throw new ValidationException(Validator::make([], []), [
                    'jar_or_packet' => ['Either jar or packet must be provided.']
                ]);
            }

            $packingStock = PackingStock::where('location_id', $input['location_id'])->first();
            if (!$packingStock) {
                throw new Exception("PackingStock not found for the given location.");
            }

            $oldProductOutput = $packing->product_output;
            $newProductOutput = $input['product_output'];
            $difference = $newProductOutput - $oldProductOutput;

            if ($difference > 0 && $packingStock->total < $difference) {
                throw new Exception("Insufficient stock available in packing stock.");
            }

            $packingStock->total -= $difference;
            $packingStock->save();

            $packing->update($input);

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
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action([\App\Http\Controllers\PackingController::class, 'index'])->with('status', $output);
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

            $packingStock = PackingStock::where('location_id', $packing->location_id)->first();

            if (!$packingStock) {
                throw new Exception("PackingStock not found for the given location.");
            }

            // Restore the stock
            $packingStock->total += $packing->product_output;
            $packingStock->save();

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
