<?php

namespace App\Http\Resources;

use App\Models\MasterStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $kuota = (int) ($this->kuota ?? 0);
        $totalTerjualGroup = !empty($this->kdklp)
            ? (int) MasterStock::where('kdklp', $this->kdklp)->sum('stock')
            : (int) $this->stock;
        $sisaKuota = $kuota > 0 ? max(0, $kuota - $totalTerjualGroup) : 0;

        return [
            'TYUNIT'     => $this->TYUNIT,
            'NTYUNIT'    => $this->NTYUNIT,
            'kdklp'      => $this->kdklp,
            'hjual'      => $this->hjual,
            'stock'      => (int) $this->stock, // Terjual untuk varian ini
            'terjual'    => (int) $this->stock,
            'kuota'      => $kuota,
            'sisa_kuota' => $sisaKuota,
            'cmodule'    => $this->cmodule,
            'userup'     => $this->userup,
            'tglup'      => $this->tglup,
        ];
    }
}
