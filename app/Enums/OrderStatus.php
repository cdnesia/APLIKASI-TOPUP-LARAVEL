<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Waiting = 'waiting';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu Pembayaran',
            self::Processing => 'Diproses',
            self::Success => 'Sukses',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Waiting => 'bg-slate-100 text-slate-700',
            self::Processing => 'bg-amber-100 text-amber-800',
            self::Success => 'bg-green-100 text-green-800',
            self::Failed => 'bg-red-100 text-red-700',
            self::Cancelled => 'bg-slate-200 text-slate-600',
        };
    }

    /** Status dari Digiflazz: "Sukses", "Gagal", "Pending" */
    public static function fromProvider(?string $status): self
    {
        return match (strtolower((string) $status)) {
            'sukses' => self::Success,
            'gagal' => self::Failed,
            default => self::Processing,
        };
    }
}
