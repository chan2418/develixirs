<?php
class RazorpayClient {
    private $keyId;
    private $keySecret;
    private $baseUrl = 'https://api.razorpay.com/v1/';

    public function __construct($keyId, $keySecret) {
        $this->keyId = $keyId;
        $this->keySecret = $keySecret;
    }

    public function createOrder($data) {
        $url = $this->baseUrl . 'orders';
        return $this->request('POST', $url, $data);
    }

    public function verifyPaymentSignature($attributes) {
        $expectedSignature = hash_hmac('sha256', $attributes['razorpay_order_id'] . '|' . $attributes['razorpay_payment_id'], $this->keySecret);
        return hash_equals($expectedSignature, $attributes['razorpay_signature']);
    }

    private function request($method, $url, $data = []) {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->keyId . ':' . $this->keySecret)
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Razorpay Curl Error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Razorpay API Error: ' . ($result['error']['description'] ?? 'Unknown error'));
        }

        return $result;
    }
}
