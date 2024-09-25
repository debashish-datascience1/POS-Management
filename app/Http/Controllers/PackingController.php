<?php

namespace App\Http\Controllers;

use App\Packing;
use App\Product;
use App\ProductionUnit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class PackingController extends Controller
{
    // public function index()
    // {
    //     if (!auth()->user()->can('packing.view')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     if (request()->ajax()) {
    //         $business_id = request()->session()->get('user.business_id');

    //         $packings = Packing::with('product')
    //             ->where('business_id', $business_id)
    //             ->select(['id', 'product_id', 'product_output', 'mix', 'jar', 'packet', 'total', 'grand_total', 'created_at', 'date']);

    //         return DataTables::of($packings)
    //             ->addColumn('action', function ($row) {
    //                 $html = '<div class="btn-group">
    //                             <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
    //                                 data-toggle="dropdown" aria-expanded="false">' .
    //                     __("messages.actions") .
    //                     '<span class="caret"></span><span class="sr-only">Toggle Dropdown
    //                                 </span>
    //                             </button>
    //                             <ul class="dropdown-menu dropdown-menu-right" role="menu">
    //                             <li><a href="' . action([self::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
    //                             <li><a href="#" data-href="' . action([self::class, 'destroy'], [$row->id]) . '" class="delete_packing_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>
    //                             </ul>
    //                         </div>';
    //                 return $html;
    //             })
    //             ->editColumn('product_id', function ($row) {
    //                 return $row->product->name ?? '';
    //             })
    //             ->editColumn('packing', function ($row) {
    //                 return implode(', ', $row->packing);
    //             })
    //             ->editColumn('date', '{{@format_date($date)}}')
    //             ->editColumn('created_at', '{{@format_datetime($created_at)}}')
    //             ->rawColumns(['action'])
    //             ->make(true);
    //     }

    //     return view('packing.index');
    // }
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
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');
        
        $packing_options = ['10L', '20L', '1L', '500ML']; // Define available packing options

        return view('packing.create', compact('products', 'packing_options'));
    }

    public function getProductOutput($id)
    {
        $productionUnit = ProductionUnit::where('product_id', $id)->first();
        return response()->json(['raw_material' => $productionUnit ? $productionUnit->raw_material : 0]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('packing.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->validate([
                'product_id' => 'required|exists:products,id',
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

            $packing = Packing::create($input);

            // Update the production unit's raw material if necessary
            $productionUnit = ProductionUnit::where('product_id', $input['product_id'])->first();
            if ($productionUnit) {
                $productionUnit->raw_material = $input['product_output'];
                $productionUnit->save();
            }

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

        return view('packing.edit', compact('packing', 'products'));
    }

    public function update(Request $request, $id)
    {
        \Log::info('Packing store request:', $request->all());
        if (!auth()->user()->can('packing.edit')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            DB::beginTransaction();
    
            $input = $request->validate([
                'product_id' => 'required|exists:products,id',
                'product_output' => 'required|numeric',
                'mix' => 'required|numeric|min:0',
                'jar' => 'required|array',
                'jar.*.size' => 'required|in:5L,10L,20L',
                'jar.*.quantity' => 'required|integer|min:1',
                'jar.*.price' => 'required|numeric|min:0',
                'packet' => 'required|array',
                'packet.*.size' => 'required|in:100ML,200ML,500ML',
                'packet.*.quantity' => 'required|integer|min:1',
                'packet.*.price' => 'required|numeric|min:0',
                'total' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'date' => 'required|date',
            ]);
    
            $packing = Packing::findOrFail($id);
    
            // Convert jar array to a formatted string
            $jarData = [];
            foreach ($input['jar'] as $jar) {
                $jarData[] = $jar['size'] . ':' . $jar['quantity'] . ':' . $jar['price'];
            }
            $input['jar'] = implode(',', $jarData);
    
            // Convert packet array to a formatted string
            $packetData = [];
            foreach ($input['packet'] as $packet) {
                $packetData[] = $packet['size'] . ':' . $packet['quantity'] . ':' . $packet['price'];
            }
            $input['packet'] = implode(',', $packetData);
    
            $packing->update($input);
    
            // Update the production unit's raw material if necessary
            $productionUnit = ProductionUnit::where('product_id', $input['product_id'])->first();
            if ($productionUnit) {
                $productionUnit->raw_material = $input['product_output'];
                $productionUnit->save();
            }
    
            DB::commit();
    
            $output = [
                'success' => true,
                'msg' => __('lang_v1.packing_updated_successfully')
            ];
    
        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error("Validation failed: " . json_encode($e->errors()));
    
            $output = [
                'success' => false,
                'msg' => __('messages.validation_failed'),
                'errors' => $e->errors()
            ];
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
   // Add other methods (store, edit, update, destroy) as needed
}