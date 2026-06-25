<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OutboundItem extends Model
{
    protected $guarded = [];

    public function outbound()
    {
        return $this->belongsTo(Outbound::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->subtotal = $model->sell_price * $model->quantity;
            $model->profit = ($model->sell_price - $model->cogs_price) * $model->quantity;

            // 1.6 Validasi Stok Negatif
            $item = Item::find($model->item_id);
            if ($item && $item->current_stock < $model->quantity) {
                throw new \Exception(
                    "Stok {$item->name} tidak cukup. " .
                    "Tersedia: {$item->current_stock}, " .
                    "Diminta: {$model->quantity}"
                );
            }
        });

        static::created(function ($model) {
            DB::transaction(function () use ($model) {
                // Jangan kurangi stok jika statusnya belum selesai
                if ($model->outbound->status->value !== \App\Enums\TransactionStatus::COMPLETED->value) {
                    return;
                }

                $item = Item::lockForUpdate()->find($model->item_id);
                $stockBefore = $item->current_stock;
                
                // Ensure double check within transaction lock
                if ($item->current_stock < $model->quantity) {
                    throw new \Exception("Stok {$item->name} tidak cukup.");
                }

                $item->current_stock -= $model->quantity;
                $item->save();

                \App\Models\StockMovement::create([
                    'item_id' => $item->id,
                    'type' => MovementType::OUTBOUND->value,
                    'reference_id' => $model->outbound_id,
                    'reference_type' => 'outbounds',
                    'quantity' => -$model->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $item->current_stock,
                    'price' => $model->sell_price,
                    'notes' => 'Barang Keluar',
                    'created_by' => auth()->id() ?? 1
                ]);
            });
        });
    }
}