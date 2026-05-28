<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Casts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'name', 'description', 'due_date', 'amount', 'penalty', 'total', 'status', 'payment_method', 'midtrans_order_id', 'midtrans_transaction_id', 'paid_date', 'midtrans_response'])]
#[Casts(['midtrans_response' => 'array', 'due_date' => 'date', 'paid_date' => 'date'])]
class Invoice extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
