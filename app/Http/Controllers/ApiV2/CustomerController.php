<?php

namespace App\Http\Controllers\ApiV2;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api-v2/customers
     * Search, filter, pagination, or get all
     */
    public function index(Request $request)
    {
        $search = $request->query('search', $request->query('q'));
        $query = Customer::orderBy('name', 'asc');

        if (!empty($search)) {
            $query->search($search);
        }

        if ($request->has('per_page') || $request->boolean('paginate')) {
            $perPage = (int) $request->query('per_page', 25);
            return response()->json($query->paginate($perPage));
        }

        if ($request->has('limit')) {
            $query->limit((int) $request->query('limit'));
        }

        return response()->json($query->get());
    }

    /**
     * GET /api-v2/customers/{id}
     */
    public function show($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan',
            ], 404);
        }

        return response()->json($customer);
    }

    /**
     * POST /api-v2/customers
     * Creates or returns existing customer, with root & nested payload compatibility
     */
    public function store(Request $request)
    {
        // Parameter aliasing for frontend compatibility
        if ($request->has('customer_name') && !$request->has('name')) {
            $request->merge(['name' => $request->input('customer_name')]);
        }
        if ($request->has('customerName') && !$request->has('name')) {
            $request->merge(['name' => $request->input('customerName')]);
        }
        if ($request->has('address') && !$request->has('alamat')) {
            $request->merge(['alamat' => $request->input('address')]);
        }
        if ($request->has('phone') && !$request->has('telepon')) {
            $request->merge(['telepon' => $request->input('phone')]);
        }
        if ($request->has('no_hp') && !$request->has('telepon')) {
            $request->merge(['telepon' => $request->input('no_hp')]);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'alamat'     => 'nullable|string',
            'telepon'    => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        if (empty($validated['alamat'])) {
            $validated['alamat'] = 'Gorontalo';
        }

        // Safe upsert / firstOrCreate to prevent duplicate failure
        $customer = Customer::firstOrCreate(
            ['name' => trim($validated['name'])],
            $validated
        );

        // Update address/phone if customer existed but had fallback
        if (!empty($validated['alamat']) && $customer->alamat === 'Gorontalo' && $validated['alamat'] !== 'Gorontalo') {
            $customer->update(['alamat' => $validated['alamat']]);
        }

        // Hybrid response: works whether frontend reads root level (customer.id) or wrapped (response.data.id)
        $response = array_merge([
            'success' => true,
            'message' => 'Data pelanggan berhasil ditambahkan',
            'data'    => $customer,
        ], $customer->toArray());

        return response()->json($response, 201);
    }

    /**
     * PUT/PATCH /api-v2/customers/{id}
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan',
            ], 404);
        }

        if ($request->has('customer_name') && !$request->has('name')) {
            $request->merge(['name' => $request->input('customer_name')]);
        }
        if ($request->has('customerName') && !$request->has('name')) {
            $request->merge(['name' => $request->input('customerName')]);
        }
        if ($request->has('address') && !$request->has('alamat')) {
            $request->merge(['alamat' => $request->input('address')]);
        }
        if ($request->has('phone') && !$request->has('telepon')) {
            $request->merge(['telepon' => $request->input('phone')]);
        }
        if ($request->has('no_hp') && !$request->has('telepon')) {
            $request->merge(['telepon' => $request->input('no_hp')]);
        }

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'alamat'     => 'nullable|string',
            'telepon'    => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $customer->update($validated);

        $response = array_merge([
            'success' => true,
            'message' => 'Data pelanggan berhasil diperbarui',
            'data'    => $customer,
        ], $customer->toArray());

        return response()->json($response);
    }

    /**
     * DELETE /api-v2/customers/{id}
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data pelanggan tidak ditemukan',
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelanggan berhasil dihapus',
        ]);
    }
}
