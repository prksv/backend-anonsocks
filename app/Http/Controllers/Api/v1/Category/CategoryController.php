<?php

namespace App\Http\Controllers\Api\v1\Category;

use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;
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
}
