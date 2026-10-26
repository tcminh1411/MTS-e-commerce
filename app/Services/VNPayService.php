<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;

/** Xử lý nghiệp vụ thanh toán VNPay dùng chung. */
class VNPayService
{
    /**
     * Sinh URL thanh toán VNPay có ký HMAC-SHA512.
     * Redirect user sang URL này để vào cổng thanh toán.
     */
    public function buildPaymentUrl(Order $order, string $ipAddr): string
    {
        $cfg = config('vnpay');

        // VNPay dùng timezone GMT+7 (giờ VN), không dùng UTC
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // Số tiền: VNPay yêu cầu đơn vị "xu" = VND * 100
        $amount = (int) round($order->total) * 100;

        // OrderInfo: KHÔNG dấu tiếng Việt (VNPay khuyến nghị, tránh lỗi encoding)
        $orderInfo = 'Thanh toan don hang '.$order->order_number;

        $params = [
            'vnp_Version' => $cfg['version'],
            'vnp_Command' => $cfg['command'],
            'vnp_TmnCode' => $cfg['tmn_code'],
            'vnp_Locale' => $cfg['locale'],
            'vnp_CurrCode' => $cfg['curr_code'],
            'vnp_TxnRef' => $order->order_number,
            'vnp_OrderInfo' => $orderInfo,
            'vnp_OrderType' => $cfg['order_type'],
            'vnp_Amount' => $amount,
            'vnp_ReturnUrl' => $cfg['return_url'],
            'vnp_IpAddr' => $ipAddr,
            'vnp_CreateDate' => $now->format('YmdHis'),
        ];

        // Sắp xếp key theo alphabet — VNPay bắt buộc
        ksort($params);

        // Build hashData + query string theo đúng format VNPay yêu cầu
        $hashData = '';
        $query = '';
        $i = 0;
        foreach ($params as $key => $value) {
            if ($i === 1) {
                $hashData .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key).'='.urlencode($value).'&';
        }

        $paymentUrl = $cfg['url'].'?'.$query;
        $secureHash = hash_hmac('sha512', $hashData, $cfg['hash_secret']);
        $paymentUrl .= 'vnp_SecureHash='.$secureHash;

        return $paymentUrl;
    }

    /**
     * Verify chữ ký HMAC-SHA512 từ VNPay gửi về.
     * Dùng ở cả Return URL và IPN URL.
     */
    public function verifySignature(array $params): bool
    {
        $cfg = config('vnpay');

        if (empty($params['vnp_SecureHash'])) {
            return false;
        }

        $receivedHash = $params['vnp_SecureHash'];

        // Loại các field không tham gia ký
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        // Chỉ giữ lại các key bắt đầu bằng vnp_
        $params = array_filter(
            $params,
            fn ($key) => str_starts_with($key, 'vnp_'),
            ARRAY_FILTER_USE_KEY
        );

        ksort($params);

        // Build hashData y hệt như lúc build URL
        $hashData = '';
        $i = 0;
        foreach ($params as $key => $value) {
            if ($i === 1) {
                $hashData .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashData .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $expectedHash = hash_hmac('sha512', $hashData, $cfg['hash_secret']);

        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Kiểm tra response VNPay có phải giao dịch thành công không.
     * ResponseCode = 00 (thành công) VÀ TransactionStatus = 00 (đã thanh toán).
     */
    public function isSuccessResponse(array $params): bool
    {
        return ($params['vnp_ResponseCode'] ?? null) === '00'
            && ($params['vnp_TransactionStatus'] ?? null) === '00';
    }
}
