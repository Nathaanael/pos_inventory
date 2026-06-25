<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            $model->subtotal = ($model->price ?? 0) * $model->quantity;
        });

        static::created(function ($model) {
            DB::transaction(function () use ($model) {
                $returnTransaction = ReturnTransaction::find($model->return_transaction_id);
                $item = Item::lockForUpdate()->find($model->item_id);
                $stockBefore = $item->current_stock;

                // Check direction based on transaction type
                $isSupplierReturn = ($returnTransaction->type === 'supplier' || empty($returnTransaction->type));

                if ($isSupplierReturn) {
                    // Retur KE supplier -> stok BERKURANG
                    $qty = -$model->quantity;
                    $movementType = MovementType::RETURN_OUT->value;
                    $notes = 'Retur ke Supplier';
                } else {
                    // Retur DARI customer -> stok BERTAMBAH
                    $qty = $model->quantity;
                    $movementType = MovementType::RETURN_IN->value;
                    $notes = 'Retur dari Customer';
                }

                $item->current_stock += $qty;
                $item->save();

                \App\Models\StockMovement::create([
                    'item_id' => $item->id,
                    'type' => $movementType,
                    'reference_id' => $model->return_transaction_id,
                    'reference_type' => 'returns',
                    'quantity' => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->current_stock,
                    'price' => $model->price ?? 0,
                    'notes' => $notes,
                    'created_by' => auth()->id() ?? 1
                ]);
            });
        });
    }
}