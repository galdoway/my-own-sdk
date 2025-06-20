<?php

return [
    'base_url' => env('DUMMYJSON_BASE_URL', 'https://dummyjson.com'),
    'timeout' => 30,
    'retry' => [
        'times' => 3,
        'sleep' => 100,
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 300,
    ],
];
