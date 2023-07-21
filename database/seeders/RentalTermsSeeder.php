<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class RentalTermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::find(1)
            ->rentalTerms()
            ->create([
                "days" => 1,
                "price" => 1,
            ]);
    }
}
