<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "name" => $this->name,
            "available" => $this->available,
            "description" => $this->description,
            "note" => $this->note,
            "rental_terms" => RentalTermResource::collection($this->rentalTerms),
        ];
    }
}
