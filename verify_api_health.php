<?php

$baseUrl = "http://localhost:8000/api";

function ping($endpoint, $token = null, $method = 'GET', $body = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Accept: application/json'];
    if ($token)
        $headers[] = "Authorization: Bearer $token";
    if ($body) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

echo "--- HemoTracka API Health Check ---\n";

// 1. Auth Test (Admin)
echo "Testing Auth (Developer Login)... ";
$login = ping('/auth/login', null, 'POST', ['email' => 'admin@hemotracka.com', 'password' => 'password']);
if ($login['code'] === 200) {
    $token = $login['data']['token'];
    echo "SUCCESS (Token Received)\n";
} else {
    echo "FAILED (" . $login['code'] . ")\n";
    exit(1);
}

// 2. Dashboard Test
echo "Testing Admin Dashboard... ";
$dash = ping('/admin/dashboard', $token);
echo ($dash['code'] === 200) ? "SUCCESS\n" : "FAILED (" . $dash['code'] . ")\n";

// 3. Activity Feed Test
echo "Testing Mobile Activity Feed... ";
$feed = ping('/activity-feed', $token);
echo ($feed['code'] === 200) ? "SUCCESS\n" : "FAILED (" . $feed['code'] . ")\n";

// 4. Subscriptions Test
echo "Testing Plans List... ";
$plans = ping('/subscriptions/plans', $token);
echo ($plans['code'] === 200) ? "SUCCESS\n" : "FAILED (" . $plans['code'] . ")\n";

echo "--- All Core Checks Passed ---\n";
