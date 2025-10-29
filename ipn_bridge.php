<?php
// 🔄 Cầu nối để MoMo gọi vào Render, sau đó chuyển kết quả về InfinityFree
$target = "https://techstore16.kesug.com/Web/api/order/webhook_momo.php";

$input = file_get_contents("php://input");
$response = file_get_contents($target, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $input,
        'ignore_errors' => true
    ]
]));

echo $response;
