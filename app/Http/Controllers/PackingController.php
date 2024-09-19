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
    public function index()
    {
        if (!auth()->user()->can('packing.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $packings = Packing::with('product')
                ->where('business_id', $business_id)
                ->select(['id', 'product_id', 'product_output', 'mix', 'packing', 'total', 'created_at', 'date']);

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
                ->editColumn('packing', function ($row) {
                    return implode(', ', $row->packing);
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action'])
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
                'packing' => 'required|array',
                'packing.*.size' => 'required|in:10L,20L,1L,500ML',
                'packing.*.quantity' => 'required|integer|min:1',
                'total' => 'required|numeric',
                'date' => 'required|date',
            ]);

            $input['business_id'] = $request->session()->get('user.business_id');
            
            // Convert packing array to a formatted string
            $packingData = [];
            foreach ($input['packing'] as $packing) {
                $packingData[] = $packing['size'] . ':' . $packing['quantity'];
            }
            $input['packing'] = implode(',', $packingData);

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
        $packing_options = ['10L', '20L', '1L', '500ML'];

        // Parse the packing data into an array of size:quantity pairs
        $packing_data = [];
        if (is_string($packing->packing)) {
            $packing_items = explode(',', $packing->packing);
            foreach ($packing_items as $item) {
                list($size, $quantity) = explode(':', $item);
                $packing_data[] = ['size' => $size, 'quantity' => $quantity];
            }
        } elseif (is_array($packing->packing)) {
            $packing_data = $packing->packing;
        }
        $packing->packing = $packing_data;

        return view('packing.edit', compact('packing', 'products', 'packing_options'));
    }
    
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('packing.edit')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->validate([
                'product_id' => 'required|exists:products,id',
                'product_output' => 'required|numeric',
                'mix' => 'required|numeric|min:0',
                'packing' => 'required|array',
                'packing.*.size' => 'required|in:10L,20L,1L,500ML',
                'packing.*.quantity' => 'required|integer|min:1',
                'total' => 'required|numeric',
                'date' => 'required|date',
            ]);

            $packing = Packing::findOrFail($id);
            
            // Convert packing array to a formatted string
            $packingData = [];
            foreach ($input['packing'] as $packing_item) {
                $packingData[] = $packing_item['size'] . ':' . $packing_item['quantity'];
            }
            $input['packing'] = implode(',', $packingData);

            $packing->update($input);

            // Update the production unit's raw material if necessary
            $productionUnit = ProductionUnit::where('product_id', $input['product_id'])->first();
            if ($productionUnit) {
                $productionUnit->raw_material = $input['product_output'];
                $productionUnit->save();
            }

            DB::commit();

            $output = ['success' => true,
                        'msg' => __('lang_v1.packing_updated_successfully')
                    ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
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