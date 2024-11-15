<?php

namespace NaturaSiberica\Api\Helpers\Sale;

class PriceHelper
{
    /**
     * Форматирует цену в копейки
     *
     * @param     $price
     * @param int $ratio
     *
     * @return int
     */
    public static function format($price, int $ratio = 100): int
    {
        return (int) ($price * $ratio);
    }

    /**
     * @param $price
     *
     * @return string
     */
    public static function formatToFloat($price): string
    {
        $float = $price / 100;
        return number_format($float, 2, '.', '');
    }
}
