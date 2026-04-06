<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_number', 'date', 'customer_name', 'customer_phone',
        'car_model', 'unit_price', 'currency', 'quantity', 'total_amount',
        'payment_category', 'bank_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
