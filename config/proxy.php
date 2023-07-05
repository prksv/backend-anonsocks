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
];
