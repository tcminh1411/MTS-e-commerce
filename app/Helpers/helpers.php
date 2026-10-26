<?php

/** Cung cấp hàm định dạng số tiền theo tiền Việt Nam. */
if (! function_exists('vnd')) {
    function vnd(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').'đ';
    }
}
