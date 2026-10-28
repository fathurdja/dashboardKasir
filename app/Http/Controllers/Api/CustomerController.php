<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api/customers
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
     * GET /api/customers/{id}
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
     * POST /api/customers
     */
    public function store(Request $request)
    {
        if ($request->has('customer_name') && !$request->has('name')) {
            $request->merge(['name' => $request->input('customer_name')]);
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

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pelanggan berhasil ditambahkan',
            'data'    => $customer,
        ], 201);
    }

    /**
     * PUT/PATCH /api/customers/{id}
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

        return response()->json([
            'success' => true,
            'message' => 'Data pelanggan berhasil diperbarui',
            'data'    => $customer,
        ]);
    }

    /**
     * DELETE /api/customers/{id}
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
