<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class InboundItem extends Model { protected $guarded = []; public function inbound() { return $this->belongsTo(Inbound::class); } public function item() { return $this->belongsTo(Item::class); } 
    protected static function booted() {
        static::creating(function ($model) {
            $model->subtotal = $model->buy_price * $model->quantity;
        });
        static::created(function ($model) {
            $item = $model->item;
            $stockBefore = $item->current_stock;
            $item->current_stock += $model->quantity;
            $item->save();
            \App\Models\StockMovement::create([
                'item_id' => $item->id, 'type' => 'inbound', 'reference_id' => $model->inbound_id, 'reference_type' => 'inbounds',
                'quantity' => $model->quantity, 'stock_before' => $stockBefore, 'stock_after' => $item->current_stock,
                'price' => $model->buy_price, 'notes' => 'Barang Masuk', 'created_by' => auth()->id() ?? 1
            ]);
        });
    }
}