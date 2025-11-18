<?php
require_once __DIR__ . '/tripay-config.php';

class TripayPayment {
    
    /**
     * Get available payment channels (filter QRIS only)
     */
    public static function getPaymentChannels() {
        $apiKey = TripayConfig::getApiKey();
        $apiUrl = TripayConfig::getApiUrl();
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl . '/merchant/payment-channel',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey
            ],
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $result = json_decode($response, true);
        
        // Filter hanya QRIS
        if ($result && $result['success']) {
            $qrisChannels = array_filter($result['data'], function($channel) {
                return strtoupper($channel['code']) === 'QRIS' || 
                       strtoupper($channel['code']) === 'QRISC' ||
                       stripos($channel['name'], 'qris') !== false;
            });
            return array_values($qrisChannels);
        }
        
        return [];
    }
    
    /**
     * Create transaction with QRIS
     */
    public static function createTransaction($bookingCode, $amount, $customerName, $customerEmail, $customerPhone) {
        $apiKey = TripayConfig::getApiKey();
        $privateKey = TripayConfig::getPrivateKey();
        $merchantCode = TripayConfig::getMerchantCode();
        $apiUrl = TripayConfig::getApiUrl();
        
        // Get QRIS channel code
        $channels = self::getPaymentChannels();
        if (empty($channels)) {
            return ['success' => false, 'message' => 'QRIS payment channel not available'];
        }
        $qrisCode = $channels[0]['code'];
        
        $merchantRef = $bookingCode;
        $signature = TripayConfig::generateSignature($merchantRef, $amount);
        
        $data = [
            'method' => $qrisCode,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'order_items' => [
                [
                    'name' => 'Tiket Bioskop - ' . $bookingCode,
                    'price' => $amount,
                    'quantity' => 1
                ]
            ],
            'return_url' => SITE_URL . '/booking-success.php?booking_code=' . $bookingCode,
            'expired_time' => (time() + (60 * 15)), // 15 menit
            'signature' => $signature
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl . '/transaction/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($response, true);
    }
    
    /**
     * Get transaction detail
     */
    public static function getTransactionDetail($reference) {
        $apiKey = TripayConfig::getApiKey();
        $apiUrl = TripayConfig::getApiUrl();
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl . '/transaction/detail?reference=' . $reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey
            ],
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($response, true);
    }
    
    /**
     * Validate callback signature
     */
    public static function validateCallbackSignature($privateKey, $json) {
        $signature = hash_hmac('sha256', $json, $privateKey);
        return $signature;
    }
}
?>
