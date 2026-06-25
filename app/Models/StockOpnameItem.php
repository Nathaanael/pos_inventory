<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockOpnameItem extends Model
{
    protected $guarded = [];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected static function booted()
    {
        static::created(function ($model) {
            if ($model->difference != 0) {
                DB::transaction(function () use ($model) {
                    $item = Item::lockForUpdate()->find($model->item_id);
                    $stockBefore = $item->current_stock;
                    
                    $item->current_stock = $model->physical_stock;
                    $item->save();

                    \App\Models\StockMovement::create([
                        'item_id' => $item->id,
                        'type' => MovementType::ADJUSTMENT->value,
                        'reference_id' => $model->stock_opname_id,
                        'reference_type' => 'stock_opnames',
                        'quantity' => $model->difference,
                        'stock_before' => $stockBefore,
                        'stock_after' => $item->current_stock,
                        'price' => 0,
                        'notes' => 'Stock Opname Adjustment',
                        'created_by' => auth()->id() ?? 1
                    ]);
                });
            }
        });
    }
}