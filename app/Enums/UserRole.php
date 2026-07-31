<?php

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'employee';
    case MANAGER = 'manager';
    case HRD_FINANCE = 'hrd_finance';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::EMPLOYEE => 'Karyawan',
            self::MANAGER => 'Atasan / Manager',
            self::HRD_FINANCE => 'HRD & Finance',
            self::ADMIN => 'Administrator',
        };
    }
}
