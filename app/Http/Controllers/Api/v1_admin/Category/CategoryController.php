<?php

namespace App\Http\Controllers\Api\v1_admin\Category;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Enums\Proxy\WebshareAccountType;
use App\Exceptions\CustomException;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class CategoryController extends ApiController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }
    public function view(Request $request)
    {
        $category = Category::with('rentalTerms')->where(['name' => $request->category_name])->firstOrFail();
        return $this->okResponse('', $category);
    }

    /**
     * @throws \Throwable
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'note' => 'required|string',
            'proxy_provider' => ['required', 'in:' . join(',', array_column(ProxyProvider::cases(), 'name'))],
            'proxy_type' => ['required', 'in:' . join(',', array_column(ProxyType::cases(), 'name'))],
        ]);


        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'note' => $request->note,
            'proxy_provider' => ProxyProvider::get($request->proxy_provider),
            'proxy_type' => ProxyType::get($request->proxy_type),
        ]);

        return $this->okResponse('OK', new CategoryResource($category));
    }
}
