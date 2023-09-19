<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            "name" => $this->faker->name(),
            "price" => $this->faker->randomFloat(),
            "proxy_type" => $this->faker->randomNumber(),
            "proxy_provider" => $this->faker->randomNumber(),
            "available" => $this->faker->boolean(),
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now(),
        ];
    }
}
