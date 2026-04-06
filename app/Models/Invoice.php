<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'invoice_type', 'date', 'customer_name', 'customer_address',
        'customer_phone', 'car_model', 'unit_price', 'currency', 'quantity',
        'sub_total', 'with_vat', 'vat_rate', 'vat_amount', 'grand_total',
        'payment_category', 'bank_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'unit_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'with_vat' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
