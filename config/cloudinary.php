<?php
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload

use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dcgd4x4eo',
        'api_key'    => '832621975987972',
        'api_secret' => 'C780x8g6uoxkzBAwMLmgrKDthXI',
    ],
    'url' => [
        'secure' => true
    ]
]);
