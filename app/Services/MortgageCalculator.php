<?php

namespace App\Services;

class MortgageCalculator
{
    public static function monthly(
        int|float $price,
        float $downPaymentPercent = 20,
        int $tenorYears = 15,
        float $annualRate = 8,
    ): int {
        $principal = max(0, $price * (1 - ($downPaymentPercent / 100)));
        $months = max(1, $tenorYears * 12);
        $monthlyRate = $annualRate / 100 / 12;

        if ($monthlyRate <= 0) {
            return (int) round($principal / $months);
        }

        $factor = (1 + $monthlyRate) ** $months;
        $payment = $principal * ($monthlyRate * $factor) / ($factor - 1);

        return (int) round($payment);
    }

    /**
     * @return array{principal:int,monthly:int,total:int,interest:int,down_payment:int}
     */
    public static function breakdown(
        int|float $price,
        float $downPaymentPercent = 20,
        int $tenorYears = 15,
        float $annualRate = 8,
    ): array {
        $downPayment = (int) round($price * ($downPaymentPercent / 100));
        $principal = (int) max(0, $price - $downPayment);
        $monthly = self::monthly($price, $downPaymentPercent, $tenorYears, $annualRate);
        $total = $monthly * $tenorYears * 12;
        $interest = max(0, $total - $principal);

        return [
            'principal' => $principal,
            'monthly' => $monthly,
            'total' => $total,
            'interest' => $interest,
            'down_payment' => $downPayment,
        ];
    }
}
