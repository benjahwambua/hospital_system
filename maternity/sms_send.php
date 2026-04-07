<?php
// maternity/sms_send.php
// Simple helper function — replace with your provider credentials
function send_sms($to, $message) {
    // Example generic provider endpoint (replace)
    $api_url = 'https://api.example-sms.com/send';
    $api_key = 'YOUR_API_KEY_HERE';
    $sender = 'EMAQURE';

    $data = [
      'to' => $to,
      'message' => $message,
      'sender' => $sender,
      'api_key' => $api_key
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return ['success'=>false,'error'=>$err];
    $json = @json_decode($resp, true);
    return $json ?: ['success'=>true,'raw'=>$resp];
}
