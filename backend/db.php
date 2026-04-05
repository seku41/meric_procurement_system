<?php
function env_value(array $keys, ?string $default = null): ?string
{
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

function env_flag(array $keys, bool $default = false): bool
{
    $value = env_value($keys);
    if ($value === null) {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function build_db_config_from_uri(string $uri): array
{
    $parts = parse_url($uri);

    if ($parts === false || !isset($parts['host'])) {
        return [];
    }

    parse_str($parts['query'] ?? '', $query);

    return [
        'host' => $parts['host'] ?? 'localhost',
        'port' => isset($parts['port']) ? (string) $parts['port'] : '3306',
        'name' => ltrim($parts['path'] ?? '/sekum_db', '/'),
        'user' => $parts['user'] ?? 'root',
        'pass' => $parts['pass'] ?? '',
        'charset' => $query['charset'] ?? 'utf8mb4',
        'ssl_mode' => $query['ssl-mode'] ?? $query['sslmode'] ?? 'require',
        'ssl_ca' => env_value(['DB_SSL_CA', 'MYSQL_ATTR_SSL_CA']),
        'ssl_verify' => env_flag(['DB_SSL_VERIFY'], false),
    ];
}

function build_db_config(): array
{
    $uri = env_value(['DB_URL', 'DATABASE_URL', 'MYSQL_URL', 'AIVEN_SERVICE_URI']);
    if ($uri !== null) {
        $uriConfig = build_db_config_from_uri($uri);
        if (!empty($uriConfig)) {
            return $uriConfig;
        }
    }

    return [
        'host' => env_value(['DB_HOST'], 'localhost'),
        'port' => env_value(['DB_PORT'], '3306'),
        'name' => env_value(['DB_NAME', 'DB_DATABASE'], 'sekum_db'),
        'user' => env_value(['DB_USER', 'DB_USERNAME'], 'root'),
        'pass' => env_value(['DB_PASS', 'DB_PASSWORD'], ''),
        'charset' => env_value(['DB_CHARSET'], 'utf8mb4'),
        'ssl_mode' => env_value(['DB_SSL_MODE'], 'disable'),
        'ssl_ca' => env_value(['DB_SSL_CA', 'MYSQL_ATTR_SSL_CA']),
        'ssl_verify' => env_flag(['DB_SSL_VERIFY'], false),
    ];
}

function build_pdo_options(array $config): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $sslEnabled = !empty($config['ssl_mode']) && strtolower($config['ssl_mode']) !== 'disable';

    if ($sslEnabled && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) ($config['ssl_verify'] ?? false);
    }

    if ($sslEnabled && !empty($config['ssl_ca']) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
    }

    return $options;
}

$dbConfig = build_db_config();

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['name'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], build_pdo_options($dbConfig));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'details' => getenv('APP_ENV') === 'local' ? $e->getMessage() : null,
    ]);
    exit;
}
