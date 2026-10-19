<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case WaitingConfirmation = 'waiting_confirmation';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::WaitingConfirmation => 'Menunggu Konfirmasi',
            self::Paid => 'Lunas',
            self::Rejected => 'Ditolak',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Dikembalikan',
        };
    }

    /** Kelas badge Tailwind */
    public function badge(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-slate-100 text-slate-700',
            self::WaitingConfirmation => 'bg-amber-100 text-amber-800',
            self::Paid => 'bg-green-100 text-green-800',
            self::Rejected, self::Expired => 'bg-red-100 text-red-700',
            self::Refunded => 'bg-blue-100 text-blue-800',
        };
    }
}
