<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XenditPayment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'location_id',
        'external_id',
        'xendit_invoice_id',
        'status',
        'currency',
        'amount',
        'payer_email',
        'description',
        'invoice_url',
        'metadata',
        'last_webhook_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'last_webhook_payload' => 'array',
        ];
    }
}
