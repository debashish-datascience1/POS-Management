<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Contact;
use App\FinalProductSell;
use App\CustomerGroup;
use App\TaxRate;
use App\BusinessLocation;
use App\Product;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FinalProductSellController extends Controller
{
    protected $moduleUtil;
    protected $productUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;

    public function __construct(
        ModuleUtil $moduleUtil,
        ContactUtil $contactUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = request()->session()->get('user.business_id');
        $is_admin = auth()->user()->hasRole('Admin#' . $business_id) ? true : false;
        
        if (request()->ajax()) {
            $query = FinalProductSell::with(['sell_lines'])
                ->where('final_product_sells.business_id', $business_id)
                ->select(
                    'final_product_sells.*', 
                    'contacts.name as customer_name',
                    'business_locations.name as location_name'
                )
                ->leftJoin('contacts', 'final_product_sells.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations', 'final_product_sells.location_id', '=', 'business_locations.id');
    
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $query->whereBetween('final_product_sells.date', [request()->start_date, request()->end_date]);
            }
    
            if (!empty(request()->customer_id)) {
                $query->where('final_product_sells.contact_id', request()->customer_id);
            }
    
            return DataTables::of($query)
                ->addColumn('products_info', function ($row) {
                    $html = '<table class="table table-condensed bg-gray" style="margin-bottom: 0;">';
                    foreach ($row->sell_lines as $sell_line) {
                        $html .= '<tr>
                            <td>' . $sell_line->product_temperature . '°C</td>
                            <td>' . $this->productUtil->num_f($sell_line->quantity, false, null, true) . ' KG</td>
                            <td class="display_currency" data-currency_symbol="true">' . $sell_line->amount . '</td>
                        </tr>';
                    }
                    $html .= '</table>';
                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                    data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") .
                                    '<span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">';
    
                    if (auth()->user()->can("sell.view")) {
                        $html .= '<li><a href="#" class="btn-modal" data-href="' . action([\App\Http\Controllers\FinalProductSellController::class, 'show'], [$row->id]) . 
                                '" data-container=".view_modal"><i class="fas fa-eye"></i> ' . __("messages.view") . '</a></li>';
                    }
    
                    if (auth()->user()->can("sell.update")) {
                        $html .= '<li><a href="' . action([\App\Http\Controllers\FinalProductSellController::class, 'edit'], [$row->id]) . 
                                '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }
    
                    if (auth()->user()->can("sell.delete")) {
                        $html .= '<li><a href="#" class="delete-sale" data-href="' . action([\App\Http\Controllers\FinalProductSellController::class, 'destroy'], [$row->id]) . 
                                '"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                    }
    
                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('grand_total', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->grand_total . '</span>';
                })
                ->rawColumns(['action', 'grand_total', 'products_info'])
                ->make(true);
        }
    
        $customers = Contact::customersDropdown($business_id);
        return view('finalproductsell.index', compact('customers'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $business_details = $this->businessUtil->getDetails($business_id);
        $customer_groups = CustomerGroup::forDropdown($business_id);
        
        // Get business locations
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        // Get product temperatures
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }

        return view('finalproductsell.create')
            ->with(compact(
                'walk_in_customer',
                'business_details',
                'types',
                'customer_groups',
                'business_locations',
                'bl_attributes',
                'product_temperatures'
            ));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        try {
            DB::beginTransaction();
            
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Validate request
            $request->validate([
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
                'contact_id' => 'required|exists:contacts,id',
                'product_temperature' => 'required|array',
                'product_temperature.*' => 'required|exists:temperature_fixed,temperature',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0.01',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|min:0.01',
                'grand_total' => 'required|numeric',
            ]);

            // Calculate grand total
            // $grand_total = array_sum($request->amount);

            // Create final product sell
            $final_product_sell = FinalProductSell::create([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'contact_id' => $request->contact_id,
                'date' => $request->date,
                'grand_total' => $request->grand_total,
                'created_by' => $user_id
            ]);

            // Create sell lines
            $sell_lines = [];
            foreach ($request->product_temperature as $key => $temperature) {
                // Validate available quantity
                $available_qty = DB::table('temperature_fixed')
                    ->where('temperature', $temperature)
                    ->value('quantity');

                if ($available_qty < $request->quantity[$key]) {
                    throw new \Exception("Insufficient quantity available for temperature: {$temperature}");
                }

                // Update temperature quantity
                DB::table('temperature_fixed')
                    ->where('temperature', $temperature)
                    ->decrement('quantity', $request->quantity[$key]);

                // Create sell line
                $final_product_sell->sell_lines()->create([
                    'product_temperature' => $temperature,
                    'quantity' => $request->quantity[$key],
                    'amount' => $request->amount[$key]
                ]);
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action([\App\Http\Controllers\FinalProductSellController::class, 'index'])
        ->with('status', $output);
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $final_product_sell = FinalProductSell::with('sell_lines')
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $business_details = $this->businessUtil->getDetails($business_id);
        $customer_groups = CustomerGroup::forDropdown($business_id);
        
        // Get business locations
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        // Get product temperatures
        $product_temperatures = DB::table('temperature_fixed')
            ->pluck('temperature', 'temperature');

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }

        return view('finalproductsell.edit')
            ->with(compact(
                'final_product_sell',
                'walk_in_customer',
                'business_details',
                'types',
                'customer_groups',
                'business_locations',
                'bl_attributes',
                'product_temperatures'
            ));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Find the final product sell
            $final_product_sell = FinalProductSell::where('business_id', $business_id)
                ->findOrFail($id);

            // Validate request
            $request->validate([
                'date' => 'required|date',
                'location_id' => 'required|exists:business_locations,id',
                'contact_id' => 'required|exists:contacts,id',
                'product_temperature' => 'required|array',
                'product_temperature.*' => 'required|exists:temperature_fixed,temperature',
                'quantity' => 'required|array',
                'quantity.*' => 'required|numeric|min:0.01',
                'amount' => 'required|array',
                'amount.*' => 'required|numeric|min:0.01',
            ]);

            // Calculate grand total
            $grand_total = array_sum($request->amount);

            // Update final product sell
            $final_product_sell->update([
                'location_id' => $request->location_id,
                'contact_id' => $request->contact_id,
                'date' => $request->date,
                'grand_total' => $request->grand_total,
                'updated_by' => $user_id
            ]);

            // Restore quantities from old sell lines
            foreach ($final_product_sell->sell_lines as $old_line) {
                DB::table('temperature_fixed')
                    ->where('temperature', $old_line->product_temperature)
                    ->increment('quantity', $old_line->quantity);
            }

            // Delete old sell lines
            $final_product_sell->sell_lines()->delete();

            // Create new sell lines
            foreach ($request->product_temperature as $key => $temperature) {
                // Validate available quantity
                $available_qty = DB::table('temperature_fixed')
                    ->where('temperature', $temperature)
                    ->value('quantity');

                if ($available_qty < $request->quantity[$key]) {
                    throw new \Exception("Insufficient quantity available for temperature: {$temperature}");
                }

                // Update temperature quantity
                DB::table('temperature_fixed')
                    ->where('temperature', $temperature)
                    ->decrement('quantity', $request->quantity[$key]);

                // Create sell line
                $final_product_sell->sell_lines()->create([
                    'product_temperature' => $temperature,
                    'quantity' => $request->quantity[$key],
                    'amount' => $request->amount[$key]
                ]);
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action([\App\Http\Controllers\FinalProductSellController::class, 'index'])
            ->with('status', $output);
    }

    public function destroy($id)
    {
        try {
            if (!auth()->user()->can('sell.delete')) {
                abort(403, 'Unauthorized action.');
            }

            $business_id = request()->session()->get('user.business_id');

            DB::beginTransaction();

            $final_product_sell = FinalProductSell::where('business_id', $business_id)
                ->with(['sell_lines'])
                ->findOrFail($id);

            // Restore the quantities back to temperature_fixed table
            foreach ($final_product_sell->sell_lines as $line) {
                DB::table('temperature_fixed')
                    ->where('temperature', $line->product_temperature)
                    ->increment('quantity', $line->quantity);
            }

            // Delete sell lines first
            $final_product_sell->sell_lines()->delete();

            // Delete the main record
            $final_product_sell->delete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.sale_delete_success')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}