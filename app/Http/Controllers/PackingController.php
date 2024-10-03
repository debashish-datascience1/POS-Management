<?php

namespace App\Http\Controllers;

use App\Packing;
use App\Product;
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

    public function index()
    {
        if (!auth()->user()->can('packing.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $packings = Packing::with('product')
                ->where('business_id', $business_id)
                ->select(['id', 'product_id', 'product_output', 'mix', 'jar', 'packet', 'total', 'grand_total', 'created_at', 'date']);

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
                ->editColumn('product_id', function ($row) {
                    return $row->product->name ?? '';
                })
                ->editColumn('jar', function ($row) {
                    return $this->formatPackingData($row->jar);
                })
                ->editColumn('packet', function ($row) {
                    return $this->formatPackingData($row->packet);
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('packing.index');
    }

    private function formatPackingData($data)
    {
        $items = explode(',', $data);
        $formatted = [];
        foreach ($items as $item) {
            list($size, $quantity, $price) = explode(':', $item);
            $formatted[] = "$size: $quantity ₹ $price";
        }
        return implode(', ', $formatted);
    }

    public function create()
    {
        if (!auth()->user()->can('packing.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        // $products = Product::where('business_id', $business_id)->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        
        $packing_options = ['10L', '20L', '1L', '500ML']; // Define available packing options

        return view('packing.create', compact('packing_options', 'business_locations', 'bl_attributes'));
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
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
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

            $input['business_id'] = $request->session()->get('user.business_id');
            
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

            // Remove product_id from the input array as it's no longer needed
            unset($input['product_id']);

            $packing = Packing::create($input);

            $packingStock = PackingStock::where('location_id', $request->location_id)->first();
            if (!$packingStock || $packingStock->total < $request->product_output) {
                throw new Exception('Insufficient stock available');
            }

            // Update the packing stock
            $packingStock->total -= $request->product_output;
            $packingStock->save();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_added_successfully')
            ];

        } catch (ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
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
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        return view('packing.edit', compact('packing', 'products', 'business_locations','bl_attributes'));
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
            Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

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
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
        
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}