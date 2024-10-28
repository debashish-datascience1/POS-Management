<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use App\Http\Controllers\ProductController;
use App\Product;
use App\ProductionUnit;
use Yajra\DataTables\Facades\DataTables;
use App\Models\UtilizedMaterial; // Make sure to create this model

class UtilizeController extends Controller
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
                    ->make(true);
            }

            return view('utilize.index');
        } catch (Exception $e) {
            Log::error('UtilizeController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}