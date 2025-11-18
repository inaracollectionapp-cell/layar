<?php
// Tripay Configuration
class TripayConfig {
    private static $apiKey;
    private static $privateKey;
    private static $merchantCode;
    private static $isProduction;
    
    public static function init() {
        self::$apiKey = getSetting('tripay_api_key', '');
        self::$privateKey = getSetting('tripay_private_key', '');
        self::$merchantCode = getSetting('tripay_merchant_code', '');
        self::$isProduction = (getSetting('tripay_environment', 'sandbox') === 'production');
    }
    
    public static function getApiUrl() {
        return self::$isProduction ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
    }
    
    public static function getApiKey() {
        return self::$apiKey;
    }
    
    public static function getPrivateKey() {
        return self::$privateKey;
    }
    
    public static function getMerchantCode() {
        return self::$merchantCode;
    }
    
    public static function generateSignature($merchantRef, $amount) {
        $data = self::$merchantCode . $merchantRef . $amount;
        return hash_hmac('sha256', $data, self::$privateKey);
    }
}

// Initialize Tripay Config
TripayConfig::init();
?>
