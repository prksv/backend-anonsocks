<?php

namespace App\Http\Controllers\Api\v1\Category;

use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;

/**
 * @group Category
 */
class CategoryController extends ApiController
{
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }

    /**
     * Список категорий
     *
     * Получить категории проксей
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $categories = $this->categoryService->getCategories();
        return $this->okResponse("Categories list", CategoryResource::collection($categories));
    }
    /**
     * Список стран категории
     *
     * Получить все страны у категории
     *
     */
    public function countries(Request $request)
    {
        $category = Category::where('name', $request->category_name)->firstOrFail();

        $countries = $this->categoryService->getCountries($category);

        return $this->okResponse("Countries list", $countries);
    }
}
