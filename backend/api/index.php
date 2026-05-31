<?php

$tmpStorage = '/tmp/syajagad';

foreach ([
    "{$tmpStorage}/app",
    "{$tmpStorage}/app/public",
    "{$tmpStorage}/framework/cache",
    "{$tmpStorage}/framework/cache/data",
    "{$tmpStorage}/framework/sessions",
    "{$tmpStorage}/framework/views",
    "{$tmpStorage}/logs",
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

function putDefaultEnv(string $key, string $value): void
{
    if (($_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null) !== null) {
        return;
    }

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

putDefaultEnv('VIEW_COMPILED_PATH', "{$tmpStorage}/framework/views");
putDefaultEnv('LOG_CHANNEL', 'stderr');
putDefaultEnv('CACHE_STORE', 'array');
putDefaultEnv('SESSION_DRIVER', 'cookie');

require __DIR__ . '/../public/index.php';
