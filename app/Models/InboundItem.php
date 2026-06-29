<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InboundItem extends Model
{
    protected $guarded = [];

    public function inbound()
    {
        return $this->belongsTo(Inbound::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->subtotal = $model->buy_price * $model->quantity;
        });

        static::created(function ($model) {
            if ($model->inbound->status->value !== \App\Enums\TransactionStatus::RECEIVED->value) {
                return;
            }

            DB::transaction(function () use ($model) {
                $item = Item::lockForUpdate()->find($model->item_id);
                $stockBefore = $item->current_stock;
                $item->current_stock += $model->quantity;
                $item->save();

                // Update per-supplier stock in item_supplier pivot
                $supplierId = $model->inbound->supplier_id;
                if ($supplierId) {
                    \App\Models\ItemSupplier::where('item_id', $model->item_id)
                        ->where('supplier_id', $supplierId)
                        ->increment('current_stock', $model->quantity);
                }

                \App\Models\StockMovement::create([
                    'item_id' => $item->id,
                    'type' => MovementType::INBOUND->value,
                    'reference_id' => $model->inbound_id,
                    'reference_type' => 'inbounds',
                    'quantity' => $model->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->current_stock,
                    'price' => $model->buy_price,
                    'notes' => 'Barang Masuk',
                    'created_by' => auth()->id() ?? 1
                ]);
            });
        });
    }
}