<?php

// Cấu hình cổng thanh toán VNPay; thông tin bí mật được đọc từ môi trường.

return [

    'tmn_code' => env('VNPAY_TMN_CODE'),

    'hash_secret' => env('VNPAY_HASH_SECRET'),

    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),

    'return_url' => env('VNPAY_RETURN_URL'),

    'ipn_url' => env('VNPAY_IPN_URL'),

    'locale' => env('VNPAY_LOCALE', 'vn'),

    'version' => '2.1.0',

    'command' => 'pay',

    'order_type' => 'other',

    'curr_code' => 'VND',
];
