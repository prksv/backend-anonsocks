<?php

namespace App\Http\Controllers\Api\v1_admin\Category;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Exceptions\CustomException;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\RentalTermResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class RentalTermController extends ApiController
{
    public function create(Request $request)
    {
        $request->validate([
            'days' => 'numeric',
            'price' => 'numeric',
        ]);

        $category = Category::where('name', $request->category_name)->firstOrFail();

        $rentalTerm = $category->rentalTerms()->create([
            'days' => $request->days,
            'price' => $request->price
        ]);

        return $this->okResponse('OK', new RentalTermResource($rentalTerm));
    }
}
