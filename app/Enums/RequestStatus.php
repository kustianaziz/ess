<?php

namespace App\Enums;

enum RequestStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Menunggu Persetujuan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::PAID => 'Sudah Dibayarkan',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::DRAFT => 'bg-slate-100 text-slate-700 border-slate-200',
            self::SUBMITTED => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            self::APPROVED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::REJECTED => 'bg-rose-100 text-rose-800 border-rose-200',
            self::PAID => 'bg-sky-100 text-sky-800 border-sky-200',
            self::COMPLETED => 'bg-gray-100 text-gray-700 border-gray-200',
            self::CANCELLED => 'bg-zinc-100 text-zinc-600 border-zinc-200',
        };
    }
}
