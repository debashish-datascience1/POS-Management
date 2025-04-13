<?php

namespace App\Http\Controllers;

use App\PurchaseLine;
use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use DB;

class StockController extends Controller
{
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $purchaseLines = PurchaseLine::join('products', 'purchase_lines.product_id', '=', 'products.id')
                ->where('products.business_id', $business_id)
                ->select([
                    'products.name as product_name',
                    'purchase_lines.created_at',
                    'purchase_lines.quantity',
                    'purchase_lines.purchase_price_inc_tax as unit_price',
                    'products.id as product_id'
                ]);

            return Datatables::of($purchaseLines)
                ->editColumn('created_at', function ($row) {
                    return date('Y-m-d', strtotime($row->created_at));
                })
                ->editColumn('unit_price', function ($row) {
                    return number_format($row->unit_price, 2);
                })
                ->make(true);
        }

        // First get the total purchased quantities
        $purchasedQuantities = PurchaseLine::join('products', 'purchase_lines.product_id', '=', 'products.id')
            ->where('products.business_id', $business_id)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(purchase_lines.quantity) as total_purchased')
            )
            ->groupBy('products.id', 'products.name');

        // Then join with production_stock to get the used quantities
        $availableStocks = DB::table(DB::raw("({$purchasedQuantities->toSql()}) as purchased"))
            ->mergeBindings($purchasedQuantities->getQuery())
            ->leftJoin('production_stock', 'purchased.product_id', '=', 'production_stock.product_id')
            ->select(
                'purchased.product_id',
                'purchased.product_name',
                'purchased.total_purchased',
                DB::raw('COALESCE(production_stock.total_raw_material, 0) as total_used'),
                DB::raw('(purchased.total_purchased - COALESCE(production_stock.total_raw_material, 0)) as available_quantity')
            )
            ->having('available_quantity', '>=', 0)
            ->get();

        return view('stock.index', compact('availableStocks'));
    }
}