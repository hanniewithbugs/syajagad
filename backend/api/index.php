<?php

$tmpStorage = '/tmp/syajagad';

foreach ([
    "{$tmpStorage}/app",
    "{$tmpStorage}/app/public",
    "{$tmpStorage}/bootstrap/cache",
    "{$tmpStorage}/database",
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

function putDefaultAppKey(): void
{
    if (getRuntimeEnv('APP_KEY') !== null) {
        return;
    }

    $seed = getRuntimeEnv('VERCEL_PROJECT_ID')
        ?? getRuntimeEnv('VERCEL_PROJECT_PRODUCTION_URL')
        ?? getRuntimeEnv('VERCEL_URL')
        ?? 'syajagad-vercel-runtime';

    putRuntimeEnv('APP_KEY', 'base64:' . base64_encode(hash('sha256', $seed, true)));
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
    $postgresUrl = getRuntimeEnv('DATABASE_URL')
        ?? getRuntimeEnv('DB_URL')
        ?? getRuntimeEnv('POSTGRES_URL')
        ?? getRuntimeEnv('POSTGRES_PRISMA_URL')
        ?? getRuntimeEnv('POSTGRES_URL_NON_POOLING');

    if (getRuntimeEnv('DATABASE_URL') === null && $postgresUrl !== null) {
        putRuntimeEnv('DATABASE_URL', $postgresUrl);
    }

    $connection = strtolower(trim((string) getRuntimeEnv('DB_CONNECTION')));

    if (in_array($connection, ['postgres', 'postgresql'], true)) {
        putRuntimeEnv('DB_CONNECTION', 'pgsql');
        $connection = 'pgsql';
    }

    if ($connection === '' && $postgresUrl !== null) {
        putRuntimeEnv('DB_CONNECTION', 'pgsql');
        $connection = 'pgsql';
    }

    if ($connection !== 'pgsql') {
        return;
    }

    foreach (['DATABASE_URL', 'DB_URL', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL', 'POSTGRES_URL_NON_POOLING'] as $urlKey) {
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

function normalizeDatabaseEnv(string $tmpStorage): void
{
    if (strtolower(trim((string) getRuntimeEnv('SYAJAGAD_DATABASE_MODE'))) === 'sqlite') {
        useTmpSqliteDatabase($tmpStorage);
        return;
    }

    $postgresUrl = getRuntimeEnv('DATABASE_URL')
        ?? getRuntimeEnv('DB_URL')
        ?? getRuntimeEnv('POSTGRES_URL')
        ?? getRuntimeEnv('POSTGRES_PRISMA_URL')
        ?? getRuntimeEnv('POSTGRES_URL_NON_POOLING');

    if ($postgresUrl !== null) {
        normalizePostgresEnv();
        return;
    }

    $connection = strtolower(trim((string) getRuntimeEnv('DB_CONNECTION')));
    $hasExplicitDatabaseHost = getRuntimeEnv('DB_HOST') !== null
        && getRuntimeEnv('DB_DATABASE') !== null
        && getRuntimeEnv('DB_USERNAME') !== null;

    if (! $hasExplicitDatabaseHost || in_array($connection, ['', 'sqlite', 'null', 'pgsql', 'postgres', 'postgresql'], true)) {
        useTmpSqliteDatabase($tmpStorage);
    }
}

function useTmpSqliteDatabase(string $tmpStorage): void
{
    $sqlitePath = "{$tmpStorage}/database/database.sqlite";

    if (! file_exists($sqlitePath)) {
        touch($sqlitePath);
    }

    putRuntimeEnv('DB_CONNECTION', 'sqlite');
    putRuntimeEnv('DB_DATABASE', $sqlitePath);

    foreach (['DATABASE_URL', 'DB_URL', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL', 'POSTGRES_URL_NON_POOLING'] as $key) {
        putRuntimeEnv($key, '');
    }
}

function normalizeServerlessStateEnv(): void
{
    $sessionDriver = strtolower(trim((string) getRuntimeEnv('SESSION_DRIVER')));
    $cacheStore = strtolower(trim((string) getRuntimeEnv('CACHE_STORE')));
    $queueConnection = strtolower(trim((string) getRuntimeEnv('QUEUE_CONNECTION')));
    $filesystemDisk = strtolower(trim((string) getRuntimeEnv('FILESYSTEM_DISK')));

    if (in_array($sessionDriver, ['', 'null', 'database', 'file'], true)) {
        putRuntimeEnv('SESSION_DRIVER', 'cookie');
    }

    if (in_array($cacheStore, ['', 'null', 'database', 'file'], true)) {
        putRuntimeEnv('CACHE_STORE', 'array');
    }

    if (in_array($queueConnection, ['', 'null', 'database'], true)) {
        putRuntimeEnv('QUEUE_CONNECTION', 'sync');
    }

    if (in_array($filesystemDisk, ['', 'null'], true)) {
        putRuntimeEnv('FILESYSTEM_DISK', 'local');
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
putDefaultEnv('QUEUE_CONNECTION', 'sync');
putDefaultEnv('FILESYSTEM_DISK', 'local');
putDefaultEnv('APP_ENV', 'production');
putDefaultEnv('APP_DEBUG', 'false');
putDefaultAppKey();
normalizeServerlessStateEnv();
normalizeDatabaseEnv($tmpStorage);

require __DIR__ . '/../public/index.php';
