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

function build_db_config(): array
{
    return [
        'host' => env_value(['DB_HOST'], 'localhost'),
        'port' => env_value(['DB_PORT'], '3306'),
        'name' => env_value(['DB_NAME', 'DB_DATABASE'], 'sekum_db'),
        'user' => env_value(['DB_USER', 'DB_USERNAME'], 'root'),
        'pass' => env_value(['DB_PASS', 'DB_PASSWORD'], ''),
        'charset' => env_value(['DB_CHARSET'], 'utf8mb4'),
        'ssl_mode' => env_value(['DB_SSL_MODE'], 'disable'),
        'ssl_ca' => env_value(['DB_SSL_CA', 'MYSQL_ATTR_SSL_CA']),
    ];
}

function build_pdo_options(array $config): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (!empty($config['ssl_ca']) && defined('PDO::MYSQL_ATTR_SSL_CA')) {
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

if (!empty($dbConfig['ssl_mode']) && strtolower($dbConfig['ssl_mode']) !== 'disable') {
    $dsn .= ';sslmode=' . $dbConfig['ssl_mode'];
}

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
