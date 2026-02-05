<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterStockResource;
use App\Models\MasterStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterStockController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stocks = MasterStock::with('items')->get();
        return MasterStockResource::collection($stocks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'TYUNIT'  => 'required|string|unique:munit,TYUNIT',
            'NTYUNIT' => 'required|string',
            'kdklp'   => 'nullable|string',
            'hjual'   => 'required|numeric',
            'cmodule' => 'nullable|string',
            'userup'  => 'nullable|string',
            'tglup'   => 'nullable|date',
        ]);

        $stock = MasterStock::create($validated);

        return new MasterStockResource($stock);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stock = MasterStock::with('items')->findOrFail($id);
        return new MasterStockResource($stock);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $stock = MasterStock::findOrFail($id);

        $validated = $request->validate([
            'TYUNIT'  => [
                'required',
                'string',
                Rule::unique('munit', 'TYUNIT')->ignore($stock->TYUNIT, 'TYUNIT'),
            ],
            'NTYUNIT' => 'required|string',
            'kdklp'   => 'nullable|string',
            'hjual'   => 'required|numeric',
            'cmodule' => 'nullable|string',
            'userup'  => 'nullable|string',
            'tglup'   => 'nullable|date',
        ]);

        $stock->update($validated);

        return new MasterStockResource($stock);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stock = MasterStock::findOrFail($id);
        $stock->delete();

        return response()->json([
            'message' => 'Master stock berhasil dihapus.'
        ]);
    }
}
