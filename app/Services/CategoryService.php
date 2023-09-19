<?php

namespace App\Services;

use App\Facades\ProxyManager;
use App\Models\Category;

class CategoryService
{
    public function getCategories()
    {
        return Category::all();
    }

    public function getCountries(Category $category): array
    {
        return ProxyManager::driver($category->proxy_provider->name)->getCountries();
    }
}
