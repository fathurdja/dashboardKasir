<?php

namespace App\Http\Resources;

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
        return [
            'TYUNIT'   => $this->TYUNIT,
            'NTYUNIT'  => $this->NTYUNIT,
            'kdklp'    => $this->kdklp,
            'hjual'    => $this->hjual,
            'cmodule'  => $this->cmodule,
            'userup'   => $this->userup,
            'tglup'    => $this->tglup,
        ];
    }
}
