<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class OutboundItem extends Model { protected $guarded = []; public function outbound() { return $this->belongsTo(Outbound::class); } public function item() { return $this->belongsTo(Item::class); } 
    protected static function booted() {
        static::creating(function ($model) {
            $model->subtotal = $model->sell_price * $model->quantity;
            $model->profit = ($model->sell_price - $model->cogs_price) * $model->quantity;
        });
        static::created(function ($model) {
            $item = $model->item;
            $stockBefore = $item->current_stock;
            $item->current_stock -= $model->quantity;
            $item->save();
            \App\Models\StockMovement::create([
                'item_id' => $item->id, 'type' => 'outbound', 'reference_id' => $model->outbound_id, 'reference_type' => 'outbounds',
                'quantity' => -$model->quantity, 'stock_before' => $stockBefore, 'stock_after' => $item->current_stock,
                'price' => $model->sell_price, 'notes' => 'Barang Keluar', 'created_by' => auth()->id() ?? 1
            ]);
        });
    }
}