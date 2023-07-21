<?php

namespace Database\Seeders;

use App\Enums\Proxy\ProxyProvider;
use App\Enums\Proxy\ProxyType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            "name" => "ipv6_64",
            "proxy_type_id" => ProxyType::IPV4_SHARED,
            "proxy_provider_id" => ProxyProvider::WEBSHARE,
        ]);

        Category::create([
            "name" => "ipv4_free",
            "proxy_type_id" => ProxyType::IPV4_SHARED_FREE,
            "proxy_provider_id" => ProxyProvider::WEBSHARE,
        ]);

        Category::create([
            "name" => "ipv4_premium",
            "proxy_type_id" => ProxyType::IPV4_PREMIUM,
            "proxy_provider_id" => ProxyProvider::WEBSHARE,
        ]);
    }
}
