<?php
// --------- .env loader ----------
function env($key, $default = null)
{
    static $loaded = false;
    if (!$loaded && file_exists(__DIR__ . '/.env')) {
        foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, null);
            $v = trim($v ?? '');
            $v = preg_replace('/^"(.*)"$|^\x27(.*)\x27$/', '$1$2', $v);
            $_ENV[trim($k)] = $v;
        }
        $loaded = true;
    }
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

header('Content-Type: application/json; charset=utf-8');

// 1️⃣ Nhận dữ liệu từ MoMo
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
file_put_contents(__DIR__ . '/momo_ipn_log.txt', date('c') . " | " . $raw . PHP_EOL, FILE_APPEND);

if (!$data || !is_array($data)) {
    echo json_encode(['resultCode' => 99, 'message' => 'Invalid payload']);
    exit;
}

// 2️⃣ Xác minh chữ ký MoMo
$accessKey = env('MOMO_ACCESS_KEY');
$secretKey = env('MOMO_SECRET_KEY');

$rawHash = "accessKey={$accessKey}"
    . "&amount={$data['amount']}"
    . "&extraData={$data['extraData']}"
    . "&message={$data['message']}"
    . "&orderId={$data['orderId']}"
    . "&orderInfo={$data['orderInfo']}"
    . "&orderType={$data['orderType']}"
    . "&partnerCode={$data['partnerCode']}"
    . "&payType={$data['payType']}"
    . "&requestId={$data['requestId']}"
    . "&responseTime={$data['responseTime']}"
    . "&resultCode={$data['resultCode']}"
    . "&transId={$data['transId']}";

$calcSignature = hash_hmac('sha256', $rawHash, $secretKey);
if (!isset($data['signature']) || $calcSignature !== $data['signature']) {
    file_put_contents(__DIR__ . '/momo_ipn_log.txt', "❌ Invalid signature\n", FILE_APPEND);
    echo json_encode(['resultCode' => 97, 'message' => 'Invalid signature']);
    exit;
}

// 3️⃣ Gọi về cầu nối InfinityFree
$orderId = (int)($data['orderId'] ?? 0);
if ($data['resultCode'] === 0 && $orderId > 0) {
    $bridgeUrl = env('BRIDGE_UPDATE_URL');
    $bridgeSecret = env('BRIDGE_SECRET');

    $payload = [
        'order_id' => $orderId,
        'status'   => 'confirmed',
        'method'   => 'MoMo',
        'token'    => hash_hmac('sha256', $orderId . '|' . 'confirmed', $bridgeSecret),
    ];

    $ch = curl_init($bridgeUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents(__DIR__ . '/momo_ipn_log.txt', "➡️ Bridge response ({$code}): {$resp}\n", FILE_APPEND);
    echo json_encode(['resultCode' => 0, 'message' => 'Confirm Success']);
    exit;
}

echo json_encode(['resultCode' => 1001, 'message' => 'Payment failed or missing orderId']);
