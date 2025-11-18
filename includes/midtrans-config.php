<?php
require_once __DIR__ . '/../vendor/midtrans/midtrans-php/Midtrans.php';

\Midtrans\Config::$serverKey = getSetting('midtrans_server_key', '');
\Midtrans\Config::$isProduction = (getSetting('midtrans_environment', 'sandbox') === 'production');
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
?>
