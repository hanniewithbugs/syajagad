<?php

$tmpStorage = '/tmp/syajagad';

foreach ([
    "{$tmpStorage}/app",
    "{$tmpStorage}/app/public",
    "{$tmpStorage}/bootstrap/cache",
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

function getRuntimeEnv(string $key): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return null;
    }

    return (string) $value;
}

function putRuntimeEnv(string $key, string $value): void
{
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

function putDefaultEnv(string $key, string $value): void
{
    if (getRuntimeEnv($key) !== null) {
        return;
    }

    putRuntimeEnv($key, $value);
}

function rewritePostgresUrlPort(string $url, int $port): string
{
    $parts = parse_url($url);

    if (($parts['scheme'] ?? '') === '' || ! str_starts_with($parts['scheme'], 'postgres')) {
        return $url;
    }

    if (($parts['port'] ?? null) !== 3306) {
        return $url;
    }

    $auth = '';
    if (isset($parts['user'])) {
        $auth = $parts['user'];

        if (isset($parts['pass'])) {
            $auth .= ':' . $parts['pass'];
        }

        $auth .= '@';
    }

    return $parts['scheme'] . '://' . $auth
        . ($parts['host'] ?? '')
        . ':' . $port
        . ($parts['path'] ?? '')
        . (isset($parts['query']) ? '?' . $parts['query'] : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

function normalizePostgresEnv(): void
{
    if (getRuntimeEnv('DB_CONNECTION') !== 'pgsql') {
        return;
    }

    foreach (['DATABASE_URL', 'DB_URL'] as $urlKey) {
        $url = getRuntimeEnv($urlKey);

        if ($url !== null) {
            putRuntimeEnv($urlKey, rewritePostgresUrlPort($url, 5432));
        }
    }

    if (getRuntimeEnv('DB_PORT') === null || getRuntimeEnv('DB_PORT') === '3306') {
        putRuntimeEnv('DB_PORT', '5432');
    }

    putDefaultEnv('DB_SSLMODE', 'require');
}

function normalizeServerlessStateEnv(): void
{
    if (in_array(getRuntimeEnv('SESSION_DRIVER'), [null, 'database', 'file'], true)) {
        putRuntimeEnv('SESSION_DRIVER', 'cookie');
    }

    if (in_array(getRuntimeEnv('CACHE_STORE'), [null, 'database', 'file'], true)) {
        putRuntimeEnv('CACHE_STORE', 'array');
    }
}

putDefaultEnv('VIEW_COMPILED_PATH', "{$tmpStorage}/framework/views");
putDefaultEnv('APP_SERVICES_CACHE', "{$tmpStorage}/bootstrap/cache/services.php");
putDefaultEnv('APP_PACKAGES_CACHE', "{$tmpStorage}/bootstrap/cache/packages.php");
putDefaultEnv('APP_CONFIG_CACHE', "{$tmpStorage}/bootstrap/cache/config.php");
putDefaultEnv('APP_ROUTES_CACHE', "{$tmpStorage}/bootstrap/cache/routes.php");
putDefaultEnv('APP_EVENTS_CACHE', "{$tmpStorage}/bootstrap/cache/events.php");
putDefaultEnv('LOG_CHANNEL', 'stderr');
putDefaultEnv('CACHE_STORE', 'array');
putDefaultEnv('SESSION_DRIVER', 'cookie');
putDefaultEnv('APP_ENV', 'production');
putDefaultEnv('APP_DEBUG', 'false');
normalizeServerlessStateEnv();
normalizePostgresEnv();

require __DIR__ . '/../public/index.php';
