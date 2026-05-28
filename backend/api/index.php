<?php

$tmpStorage = '/tmp/syajagad';

foreach ([
    "{$tmpStorage}/framework/cache",
    "{$tmpStorage}/framework/sessions",
    "{$tmpStorage}/framework/views",
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

$_ENV['VIEW_COMPILED_PATH'] = $_ENV['VIEW_COMPILED_PATH'] ?? "{$tmpStorage}/framework/views";
$_SERVER['VIEW_COMPILED_PATH'] = $_SERVER['VIEW_COMPILED_PATH'] ?? "{$tmpStorage}/framework/views";
putenv('VIEW_COMPILED_PATH=' . $_ENV['VIEW_COMPILED_PATH']);

require __DIR__ . '/../public/index.php';
