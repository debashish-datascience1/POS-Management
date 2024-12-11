<?php

namespace App\Http\Controllers;

use App\Packing;
use App\Product;
use App\Temperature;
use App\FinalProduct;
use App\BusinessLocation;
use App\ProductionStock;
use App\PackingStock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class FinalProductController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('packing.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $finalProducts = FinalProduct::where('business_id', $business_id)
                ->select([
                    'id', 
                    'date', 
                    'temperature', 
                    'product_temperature', 
                    'quantity', 
                    'mix', 
                    'total', 
                    'grand_total', 
                    'created_at'
                ]);

            return DataTables::of($finalProducts)
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
                                <li><a href="#" data-href="' . action([self::class, 'destroy'], [$row->id]) . '" class="delete_final_product_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
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
                    'temperature', 
                    'product_temperature', 
                    'quantity', 
                    'mix', 
                    'total'
                ])
                ->make(true);
        }

        return view('final-product.index');
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
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');     
        return view('final-product.create', compact(         
            'business_locations',         
            'bl_attributes',         
            'temperatures',         
            'product_temperatures'     
        )); 
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
                'temperatures' => 'required|array',
                'temperatures.*' => 'required|string',
                'product_temperature' => 'required|array', 
                'product_temperature.*' => 'required|string', 
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0',
                'mix' => 'required|array',
                'mix.*' => 'required|numeric|min:0',
                'total' => 'required|array',
                'total.*' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            $business_id = $request->session()->get('user.business_id');

            // Prepare arrays to store section data
            $temperatures_array = [];
            $product_temperatures_array = [];
            $quantities_array = [];
            $mix_array = [];
            $total_array = [];

            // Process each section
            for ($i = 0; $i < count($input['temperatures']); $i++) {
                // Convert product_temperature to integer
                $productTemperature = (int)$input['product_temperature'][$i];

                // Find the corresponding temperature record in temperature_fixed table
                $existingTemperature = DB::table('temperature_fixed')
                    ->where('temperature', $productTemperature)
                    ->first();

                // Verify temperature quantity availability in Temperature table
                $temperature = Temperature::where('temperature', $input['temperatures'][$i])
                    ->where('temp_quantity', '>=', $input['quantity'][$i])
                    ->first();

                if (!$temperature) {
                    throw new Exception("Insufficient quantity available for temperature {$input['temperatures'][$i]}");
                }

                // Store section data in arrays
                $temperatures_array[] = $input['temperatures'][$i];
                $product_temperatures_array[] = $input['product_temperature'][$i];
                $quantities_array[] = $input['quantity'][$i];
                $mix_array[] = $input['mix'][$i];
                $total_array[] = $input['total'][$i];

                // Update temperature quantity
                $temperature->temp_quantity -= $input['quantity'][$i];
                $temperature->save();

                // Ensure quantity is a decimal
                $totalQuantity = (float)$input['total'][$i];

                // Update temperature_fixed table
                if ($existingTemperature) {
                    // If record exists, update the quantity
                    DB::table('temperature_fixed')
                        ->where('temperature', $productTemperature)
                        ->update([
                            'quantity' => DB::raw("COALESCE(quantity, 0) + {$totalQuantity}")
                        ]);
                } else {
                    // This should not happen given your existing data, but included for completeness
                    DB::table('temperature_fixed')->insert([
                        'temperature' => $productTemperature,
                        'quantity' => $totalQuantity
                    ]);
                }
            }

            // Create single final product record with all sections
            $finalProduct = FinalProduct::create([
                'business_id' => $business_id,
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'temperature' => json_encode($temperatures_array),
                'product_temperature' => json_encode($product_temperatures_array),
                'quantity' => json_encode($quantities_array),
                'mix' => json_encode($mix_array),
                'total' => json_encode($total_array),
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

        return redirect()->action([\App\Http\Controllers\FinalProductController::class, 'index'])->with('status', $output);
    }

    public function edit($id) 
    {
        if (!auth()->user()->can('packing.edit')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $packing = FinalProduct::where('business_id', $business_id)->findOrFail($id);

        // Format the date to yyyy-MM-dd
        $packing->date = date('Y-m-d', strtotime($packing->date));

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        // Get temperatures for dropdown
        $temperatures = Temperature::select('temperature')
            ->distinct()
            ->pluck('temperature', 'temperature');

        // Get product temperatures
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');

        // Ensure all JSON fields are properly decoded
        $packing->temperature = is_string($packing->temperature) 
            ? json_decode($packing->temperature, true) 
            : $packing->temperature;
        
        $packing->quantity = is_string($packing->quantity) 
            ? json_decode($packing->quantity, true) 
            : $packing->quantity;
        
        $packing->mix = is_string($packing->mix) 
            ? json_decode($packing->mix, true) 
            : $packing->mix;
        
        $packing->product_temperature = is_string($packing->product_temperature) 
            ? json_decode($packing->product_temperature, true) 
            : $packing->product_temperature;

        // Decode and clean up total values
        $total = is_string($packing->total) 
            ? json_decode($packing->total, true) 
            : $packing->total;
        
        // Ensure total values are clean numbers
        $packing->total = array_map(function($value) {
            // Remove any extra quotes or brackets
            $value = is_array($value) ? array_shift($value) : $value;
            return is_numeric($value) ? floatval($value) : $value;
        }, $total);

        return view('final-product.edit', compact(
            'packing', 
            'business_locations', 
            'bl_attributes', 
            'temperatures', 
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

            // Find the existing final product
            $existingFinalProduct = FinalProduct::findOrFail($id);

            $input = $request->validate([
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
                'temperatures' => 'required|array',
                'temperatures.*' => 'required|string',
                'product_temperature' => 'required|array', 
                'product_temperature.*' => 'required|string', 
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0',
                'mix' => 'required|array',
                'mix.*' => 'required|numeric|min:0',
                'total' => 'required|array',
                'total.*' => 'required|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
            ]);

            $business_id = $request->session()->get('user.business_id');

            // Prepare arrays to store section data
            $temperatures_array = [];
            $product_temperatures_array = [];
            $quantities_array = [];
            $mix_array = [];
            $total_array = [];

            // Decode existing data to restore previous quantities
            $existingTemperatures = json_decode($existingFinalProduct->temperature, true);
            $existingQuantities = json_decode($existingFinalProduct->quantity, true);
            $existingProductTemperatures = json_decode($existingFinalProduct->product_temperature, true);
            $existingMix = json_decode($existingFinalProduct->mix, true);
            $existingTotal = json_decode($existingFinalProduct->total, true);

            // Process each section
            for ($i = 0; $i < count($input['temperatures']); $i++) {
                // Convert product_temperature to integer
                $productTemperature = (int)$input['product_temperature'][$i];

                // Restore previous quantity for the existing temperature
                $previousTemperature = Temperature::where('temperature', $existingTemperatures[$i])
                    ->first();
                
                if ($previousTemperature) {
                    // Restore the previous quantity
                    $previousTemperature->temp_quantity += $existingQuantities[$i];
                    $previousTemperature->save();
                }

                // Find the corresponding temperature record in temperature_fixed table
                $existingTemperatureFixed = DB::table('temperature_fixed')
                    ->where('temperature', $productTemperature)
                    ->first();

                // Verify temperature quantity availability in Temperature table
                $temperature = Temperature::where('temperature', $input['temperatures'][$i])
                    ->where('temp_quantity', '>=', $input['quantity'][$i])
                    ->first();

                if (!$temperature) {
                    throw new Exception("Insufficient quantity available for temperature {$input['temperatures'][$i]}");
                }

                // Store section data in arrays
                $temperatures_array[] = $input['temperatures'][$i];
                $product_temperatures_array[] = $input['product_temperature'][$i];
                $quantities_array[] = $input['quantity'][$i];
                $mix_array[] = $input['mix'][$i];
                $total_array[] = $input['total'][$i];

                // Update temperature quantity
                $temperature->temp_quantity -= $input['quantity'][$i];
                $temperature->save();

                // Ensure quantity is a decimal
                $newTotal = (float)$input['total'][$i];
                $newMix = (float)$input['mix'][$i];

                // Update temperature_fixed table
                if ($existingTemperatureFixed) {
                    // Calculate the difference in total and mix
                    $totalDifference = $newTotal - $existingTotal[$i];
                    $mixDifference = $newMix - $existingMix[$i];

                    // Get the current quantity in temperature_fixed
                    $currentQuantity = (float)$existingTemperatureFixed->quantity;

                    // Update the quantity by adding the total difference
                    $updatedQuantity = $currentQuantity + $totalDifference;

                    DB::table('temperature_fixed')
                        ->where('temperature', $productTemperature)
                        ->update([
                            'quantity' => $updatedQuantity,
                        ]);
                    } else {
                    // Insert new record if it doesn't exist
                    DB::table('temperature_fixed')->insert([
                        'temperature' => $productTemperature,
                        'quantity' => $newTotal,
                        'mix' => $newMix
                    ]);
                }
            }

            // Update the existing final product record
            $existingFinalProduct->update([
                'business_id' => $business_id,
                'date' => $input['date'],
                'location_id' => $input['location_id'],
                'temperature' => json_encode($temperatures_array),
                'product_temperature' => json_encode($product_temperatures_array),
                'quantity' => json_encode($quantities_array),
                'mix' => json_encode($mix_array),
                'total' => json_encode($total_array),
                'grand_total' => $input['grand_total']
            ]);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_updated_successfully')
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

        return redirect()->action([\App\Http\Controllers\FinalProductController::class, 'index'])->with('status', $output);
    }

    // public function destroy($id)
    // {
    //     if (!auth()->user()->can('final_product.delete')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     try {
    //         $business_id = request()->session()->get('user.business_id');
    //         $finalProduct = FinalProduct::where('business_id', $business_id)->findOrFail($id);

    //         DB::beginTransaction();

    //         // Get the temperatures and quantities from the JSON stored in the final product record
    //         $productTemperatures = json_decode($finalProduct->product_temperature, true);
    //         $totals = json_decode($finalProduct->total, true);
    //         $quantities = json_decode($finalProduct->quantity, true);
    //         $temperatures = json_decode($finalProduct->temperature, true);

    //         // Restore the temperature quantities and update temperature_fixed
    //         for ($i = 0; $i < count($productTemperatures); $i++) {
    //             // Convert product temperature to integer
    //             $productTemperature = (int)$productTemperatures[$i];
    //             $totalQuantity = (float)$totals[$i];
    //             $quantity = (float)$quantities[$i];

    //             // Find the corresponding temperature record in temperature_fixed table
    //             $temperatureFixed = DB::table('temperature_fixed')
    //                 ->where('temperature', $productTemperature)
    //                 ->first();

    //             if (!$temperatureFixed) {
    //                 throw new Exception("Temperature {$productTemperature} not found in temperature_fixed table");
    //             }

    //             // Subtract the total from temperature_fixed
    //             DB::table('temperature_fixed')
    //                 ->where('temperature', $productTemperature)
    //                 ->update([
    //                     'quantity' => DB::raw("GREATEST(COALESCE(quantity, 0) - {$totalQuantity}, 0)")
    //                 ]);

    //             // Restore the quantity in Temperature table
    //             $temperature = Temperature::where('temperature', $temperatures[$i])->first();
                
    //             if (!$temperature) {
    //                 throw new Exception("Temperature {$temperatures[$i]} not found");
    //             }

    //             // Add back the quantity that was used
    //             $temperature->temp_quantity += $quantity;
    //             $temperature->save();
    //         }

    //         // Delete the final product record
    //         $finalProduct->delete();

    //         DB::commit();

    //         $output = [
    //             'success' => true,
    //             'msg' => __('lang_v1.final_product_deleted_successfully')
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
    //         $output = [
    //             'success' => false,
    //             'msg' => $e->getMessage() ?: __('messages.something_went_wrong')
    //         ];
    //     }

    //     return $output;
    // }

    public function destroy($id)
    {
        if (!auth()->user()->can('final_product.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $finalProduct = FinalProduct::where('business_id', $business_id)->findOrFail($id);

            // Convert stored JSON to arrays
            $finalProductTemperatures = json_decode($finalProduct->product_temperature, true);
            $finalProductDate = $finalProduct->date;

            // Check for related packings
            $relatedPackings = Packing::where('business_id', $business_id)
                ->where('date', '>=', $finalProductDate)
                ->get();

            // Check if any packing has matching temperatures
            $canDelete = true;
            foreach ($relatedPackings as $packing) {
                $packingTemperatures = json_decode($packing->product_temperature, true);
                
                // Check for common temperatures
                $commonTemperatures = array_intersect($finalProductTemperatures, $packingTemperatures);
                
                if (!empty($commonTemperatures)) {
                    $canDelete = false;
                    break;
                }
            }

            // If there are matching temperatures, prevent deletion
            if (!$canDelete) {
                throw new Exception(__('lang_v1.cannot_delete_final_product_with_related_packing'));
            }

            DB::beginTransaction();

            // Get the temperatures and quantities from the JSON stored in the final product record
            $productTemperatures = json_decode($finalProduct->product_temperature, true);
            $totals = json_decode($finalProduct->total, true);
            $quantities = json_decode($finalProduct->quantity, true);
            $temperatures = json_decode($finalProduct->temperature, true);

            // Restore the temperature quantities and update temperature_fixed
            for ($i = 0; $i < count($productTemperatures); $i++) {
                // Convert product temperature to integer
                $productTemperature = (int)$productTemperatures[$i];
                $totalQuantity = (float)$totals[$i];
                $quantity = (float)$quantities[$i];

                // Find the corresponding temperature record in temperature_fixed table
                $temperatureFixed = DB::table('temperature_fixed')
                    ->where('temperature', $productTemperature)
                    ->first();

                if (!$temperatureFixed) {
                    throw new Exception("Temperature {$productTemperature} not found in temperature_fixed table");
                }

                // Subtract the total from temperature_fixed
                DB::table('temperature_fixed')
                    ->where('temperature', $productTemperature)
                    ->update([
                        'quantity' => DB::raw("GREATEST(COALESCE(quantity, 0) - {$totalQuantity}, 0)")
                    ]);

                // Restore the quantity in Temperature table
                $temperature = Temperature::where('temperature', $temperatures[$i])->first();
                
                if (!$temperature) {
                    throw new Exception("Temperature {$temperatures[$i]} not found");
                }

                // Add back the quantity that was used
                $temperature->temp_quantity += $quantity;
                $temperature->save();
            }

            // Delete the final product record
            $finalProduct->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.final_product_deleted_successfully')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
