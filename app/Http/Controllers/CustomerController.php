<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{

    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }
        if ($request->filled('portal')) {
            $query->where('portal', $request->portal);
        }

        $customers = $query->paginate(10);

        $types = Customer::whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->pluck('type');

        $services = Customer::whereNotNull('service')
            ->where('service', '!=', '')
            ->distinct()
            ->pluck('service');

        $portals = Customer::whereNotNull('portal')
            ->where('portal', '!=', '')
            ->distinct()
            ->pluck('portal');
        if ($request->ajax()) {
            return view('customer.index-table', compact('customers'))->render();
        }

        return view('customer.view', compact('customers', 'types', 'services', 'portals'));
    }






    // Show create form
    public function create()
    {
        return view('admin.customers.create'); // point to your Blade file
    }

    // Store customer data

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact' => 'nullable|string|max:20',
            'other_phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'type' => 'required|in:Individual,Corporate',
            'company_name' => 'nullable|string|max:255',
            'address' => 'required|string|max:255', // make required if DB not nullable
            'country' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255',
            'heard_us' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['date_of_entry'] = now();
            $data['portal'] = 'Admin BE';

            // Generate unique customer code
            $lastCustomer = Customer::orderBy('id', 'desc')->first();
            if ($lastCustomer && $lastCustomer->customer_code) {
                // Extract numeric part
                $lastNumber = (int) filter_var($lastCustomer->customer_code, FILTER_SANITIZE_NUMBER_INT);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Format as VG001, VG002, ...
            $data['customer_code'] = 'VG' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            Customer::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please check your input.'
            ], 500);
        }
    }


    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully!'
        ]);
    }
}
