<?php

return [
    "services" => [
        "webshare" => [
            "premium" => [
                "api_key" => env("WEBSHARE_PREMIUM_API_KEY", ""),
            ],
            "default" => [
                "api_key" => env("WEBSHARE_SHARED_API_KEY", ""),
            ],
        ],
    ],
    "lease_terms" => [5, 10, 20, 30, 30 * 2, 30 * 3],
];
