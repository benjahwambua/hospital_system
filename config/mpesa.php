<?php
// Daraja configuration. Set these through server environment variables.
// Never commit live Consumer Secret/Passkey values to this public repository.
define('MPESA_ENV', getenv('MPESA_ENV') ?: 'sandbox');
define('MPESA_CONSUMER_KEY', getenv('MPESA_CONSUMER_KEY') ?: '');
define('MPESA_CONSUMER_SECRET', getenv('MPESA_CONSUMER_SECRET') ?: '');
define('MPESA_SHORTCODE', getenv('MPESA_SHORTCODE') ?: '');
define('MPESA_PASSKEY', getenv('MPESA_PASSKEY') ?: '');
define('MPESA_CALLBACK_URL', getenv('MPESA_CALLBACK_URL') ?: '');

function mpesa_base_url(): string {
    return MPESA_ENV === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
}

function mpesa_http_json(string $url, string $method, ?string $body = null, array $headers = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 15
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) throw new Exception('M-Pesa connection failed: ' . $error);
    $data = json_decode($response, true);
    if (!is_array($data)) throw new Exception('Invalid M-Pesa response.');
    if ($httpCode >= 400) throw new Exception($data['errorMessage'] ?? $data['error_description'] ?? 'M-Pesa API request failed.');
    return $data;
}

function mpesa_access_token(): string {
    if (!MPESA_CONSUMER_KEY || !MPESA_CONSUMER_SECRET) throw new Exception('M-Pesa Consumer Key/Secret are not configured.');
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
    $data = mpesa_http_json(
        mpesa_base_url() . '/oauth/v1/generate?grant_type=client_credentials',
        'GET',
        null,
        ['Authorization: Basic ' . $credentials, 'Content-Type: application/json']
    );
    if (empty($data['access_token'])) throw new Exception('M-Pesa access token was not returned.');
    return $data['access_token'];
}

function mpesa_normalize_phone(string $phone): string {
    $phone = preg_replace('/\D+/', '', $phone);
    if (preg_match('/^0?7\d{8}$/', $phone)) return '254' . substr($phone, -9);
    if (preg_match('/^2547\d{8}$/', $phone)) return $phone;
    throw new Exception('Enter a valid Kenyan M-Pesa phone number.');
}

function mpesa_initiate_stk($conn, int $invoiceId, int $patientId, float $amount, string $phone): array {
    if (!MPESA_SHORTCODE || !MPESA_PASSKEY || !MPESA_CALLBACK_URL) {
        throw new Exception('M-Pesa Shortcode, Passkey and Callback URL must be configured.');
    }

    $amount = (int)round($amount);
    if ($amount < 1) throw new Exception('M-Pesa amount must be at least KES 1.');

    $phone = mpesa_normalize_phone($phone);
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    $token = mpesa_access_token();

    $payload = [
        'BusinessShortCode'=>MPESA_SHORTCODE,
        'Password'=>$password,
        'Timestamp'=>$timestamp,
        'TransactionType'=>'CustomerPayBillOnline',
        'Amount'=>$amount,
        'PartyA'=>$phone,
        'PartyB'=>MPESA_SHORTCODE,
        'PhoneNumber'=>$phone,
        'CallBackURL'=>MPESA_CALLBACK_URL,
        'AccountReference'=>'INV-'.$invoiceId,
        'TransactionDesc'=>'Hospital payment INV-'.$invoiceId
    ];

    $response = mpesa_http_json(
        mpesa_base_url() . '/mpesa/stkpush/v1/processrequest',
        'POST',
        json_encode($payload),
        ['Authorization: Bearer '.$token, 'Content-Type: application/json']
    );

    $stmt=$conn->prepare("INSERT INTO mpesa_transactions (invoice_id, patient_id, amount, phone, merchant_request_id, checkout_request_id, result_code, result_desc, status, raw_response, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())");
    if(!$stmt) throw new Exception('Unable to save M-Pesa transaction. Import mpesa_migration.sql first.');

    $merchant=$response['MerchantRequestID'] ?? null;
    $checkout=$response['CheckoutRequestID'] ?? null;
    $resultCode=isset($response['ResponseCode']) ? (string)$response['ResponseCode'] : null;
    $resultDesc=$response['ResponseDescription'] ?? null;
    $raw=json_encode($response);
    $stmt->bind_param('iidssssss',$invoiceId,$patientId,$amount,$phone,$merchant,$checkout,$resultCode,$resultDesc,$raw);
    $stmt->execute();
    $stmt->close();

    return $response;
}
