<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'account_name',
        'account_number',
        'qr_data',
        'is_active',
        'description',
        'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
