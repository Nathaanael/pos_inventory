<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $guarded = [];

    public function returnTransaction()
    {
        return $this->belongsTo(ReturnTransaction::class, 'return_transaction_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Price might not be set, default to 0
            $model->subtotal = ($model->price ?? 0) * $model->quantity;
        });

        static::created(function ($model) {
            $item = $model->item;
            $stockBefore = $item->current_stock;

            // Always return_out (retur ke supplier)
            $qty = -$model->quantity;
            $item->current_stock += $qty;
            $item->save();

            \App\Models\StockMovement::create([
                'item_id' => $item->id,
                'type' => 'return_out',
                'reference_id' => $model->return_transaction_id,
                'reference_type' => 'returns',
                'quantity' => $qty,
                'stock_before' => $stockBefore,
                'stock_after' => $item->current_stock,
                'price' => $model->price ?? 0,
                'notes' => 'Retur ke Supplier',
                'created_by' => auth()->id() ?? 1
            ]);
        });
    }
}