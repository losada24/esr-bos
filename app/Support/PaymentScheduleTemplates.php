<?php

namespace App\Support;

use App\Enum\PaymentScheduleTypeEnum;

class PaymentScheduleTemplates
{
    public static function templates(): array
    {
        return [
            PaymentScheduleTypeEnum::ALL_SERVICES->value => [
                ['label' => 'Initial deposit', 'percentage' => 50],
                ['label' => 'Material ready for pick up or delivery', 'percentage' => 30],
                ['label' => 'Material installed', 'percentage' => 15],
                ['label' => 'Final (after inspection)', 'percentage' => 5],
            ],
            PaymentScheduleTypeEnum::WITHOUT_PERMITS->value => [
                ['label' => 'Initial deposit', 'percentage' => 50],
                ['label' => 'Material ready for pick up or delivery', 'percentage' => 30],
                ['label' => 'Installation completed', 'percentage' => 20],
            ],
            PaymentScheduleTypeEnum::WITHOUT_PERMITS_INSTALLATION->value => [
                ['label' => 'Initial deposit', 'percentage' => 60],
                ['label' => 'Material ready for pick up or delivery', 'percentage' => 40],
            ],
            PaymentScheduleTypeEnum::PROJECT_MORE_THAN_50K_ALL_SERVICES->value => [
                ['label' => 'Initial deposit', 'percentage' => 40],
                ['label' => 'Material ready for pick up or delivery', 'percentage' => 40],
                ['label' => 'Material installed', 'percentage' => 15],
                ['label' => 'Final (after inspection)', 'percentage' => 5],
            ],
            PaymentScheduleTypeEnum::PROJECT_MORE_THAN_50K_WITHOUT_PERMIT->value => [
                ['label' => 'Initial deposit', 'percentage' => 40],
                ['label' => 'Material ready for pick up or delivery', 'percentage' => 40],
                ['label' => 'Installation completed', 'percentage' => 20],
            ],
            PaymentScheduleTypeEnum::FULL_PAYMENT->value => [
                ['label' => 'Initial deposit', 'percentage' => 100],
            ],
            PaymentScheduleTypeEnum::CUSTOMIZED->value => [],
        ];
    }

    public static function itemsFor(string $scheduleType): array
    {
        $templates = self::templates();
        return $templates[$scheduleType] ?? [];
    }

    public static function types(): array
    {
        return array_keys(self::templates());
    }
}
