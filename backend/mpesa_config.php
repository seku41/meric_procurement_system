<?php

function mpesa_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function mpesa_is_sandbox(): bool
{
    return strtolower(mpesa_env('MPESA_ENV', 'sandbox')) !== 'production';
}

function mpesa_base_url(): string
{
    return mpesa_is_sandbox()
        ? 'https://sandbox.safaricom.co.ke'
        : 'https://api.safaricom.co.ke';
}

function mpesa_callback_url(): string
{
    $explicit = mpesa_env('MPESA_CALLBACK_URL');
    if ($explicit) {
        return $explicit;
    }

    $appUrl = rtrim(mpesa_env('APP_URL', ''), '/');
    if ($appUrl === '') {
        return '';
    }

    return $appUrl . '/backend/mpesa.php?action=callback';
}

function mpesa_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if (strpos($digits, '254') === 0) {
        return $digits;
    }

    if (strpos($digits, '0') === 0) {
        return '254' . substr($digits, 1);
    }

    if (strpos($digits, '7') === 0 || strpos($digits, '1') === 0) {
        return '254' . $digits;
    }

    return $digits;
}

function mpesa_timestamp(): string
{
    return gmdate('YmdHis');
}

function mpesa_password(string $shortcode, string $passkey, string $timestamp): string
{
    return base64_encode($shortcode . $passkey . $timestamp);
}

function mpesa_http_json(string $method, string $url, array $headers = [], ?array $payload = null): array
{
    $headerLines = implode("\r\n", $headers);
    $body = $payload === null ? '' : json_encode($payload);
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => trim($headerLines . "\r\nContent-Type: application/json\r\nAccept: application/json"),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 30,
        ],
    ]);

    $responseBody = @file_get_contents($url, false, $context);
    $rawHeaders = $http_response_header ?? [];
    $statusCode = 0;

    foreach ($rawHeaders as $line) {
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $line, $matches)) {
            $statusCode = (int) $matches[1];
            break;
        }
    }

    $decoded = null;
    if ($responseBody !== false && $responseBody !== '') {
        $decoded = json_decode($responseBody, true);
    }

    return [
        'status' => $statusCode,
        'body' => $responseBody === false ? '' : $responseBody,
        'json' => is_array($decoded) ? $decoded : null,
    ];
}

function mpesa_access_token(): string
{
    $consumerKey = mpesa_env('MPESA_CONSUMER_KEY', '');
    $consumerSecret = mpesa_env('MPESA_CONSUMER_SECRET', '');

    if ($consumerKey === '' || $consumerSecret === '') {
        throw new RuntimeException('Missing M-Pesa consumer credentials.');
    }

    $url = mpesa_base_url() . '/oauth/v1/generate?grant_type=client_credentials';
    $authHeader = 'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret);
    $response = mpesa_http_json('GET', $url, [$authHeader]);

    if ($response['status'] < 200 || $response['status'] >= 300 || empty($response['json']['access_token'])) {
        throw new RuntimeException('Unable to get M-Pesa access token: ' . ($response['body'] ?: 'No response body'));
    }

    return $response['json']['access_token'];
}

function mpesa_stk_push(array $payload): array
{
    $token = mpesa_access_token();
    $url = mpesa_base_url() . '/mpesa/stkpush/v1/processrequest';
    $response = mpesa_http_json('POST', $url, ['Authorization: Bearer ' . $token], $payload);

    if ($response['status'] < 200 || $response['status'] >= 300 || !is_array($response['json'])) {
        throw new RuntimeException('M-Pesa STK push failed: ' . ($response['body'] ?: 'No response body'));
    }

    return $response['json'];
}

function mpesa_extract_callback_value(array $items, string $name)
{
    foreach ($items as $item) {
        if (($item['Name'] ?? null) === $name) {
            return $item['Value'] ?? null;
        }
    }

    return null;
}
