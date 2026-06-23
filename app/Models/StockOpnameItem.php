<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class StockOpnameItem extends Model { protected $guarded = []; public function stockOpname() { return $this->belongsTo(StockOpname::class); } public function item() { return $this->belongsTo(Item::class); } 
    protected static function booted() {
        static::created(function ($model) {
            $item = $model->item;
            $stockBefore = $item->current_stock;
            // For MVP, we adjust stock as soon as it's saved if there's a difference
            if ($model->difference != 0) {
                $item->current_stock = $model->physical_stock;
                $item->save();
                \App\Models\StockMovement::create([
                    'item_id' => $item->id, 'type' => 'adjustment', 'reference_id' => $model->stock_opname_id, 'reference_type' => 'stock_opnames',
                    'quantity' => $model->difference, 'stock_before' => $stockBefore, 'stock_after' => $item->current_stock,
                    'price' => 0, 'notes' => 'Stock Opname Adjustment', 'created_by' => auth()->id() ?? 1
                ]);
            }
        });
    }
}