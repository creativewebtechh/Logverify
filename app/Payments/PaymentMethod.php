<?php

namespace App\Payments;

enum PaymentMethod: string
{
    case Card = 'card';

    case BankTransfer = 'bank_transfer';

    case Ussd = 'ussd';

    case Qr = 'qr';

    case MobileMoney = 'mobile_money';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Card',
            self::BankTransfer => 'Bank transfer',
            self::Ussd => 'USSD',
            self::Qr => 'QR code',
            self::MobileMoney => 'Mobile money',
        };
    }

    public static function tryFromLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if (strtolower($case->name) === strtolower($label)) {
                return $case;
            }

            if (strtolower($case->value) === strtolower($label)) {
                return $case;
            }
        }

        return null;
    }
}
