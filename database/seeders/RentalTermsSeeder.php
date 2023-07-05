<?php

namespace Database\Seeders;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Models\Category;
use App\Models\RentalTerm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
