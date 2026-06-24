<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'item_supplier')
            ->withPivot('supplier_sku', 'default_buy_price', 'is_primary')
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function latestMovement()
    {
        return $this->hasOne(StockMovement::class)->latestOfMany();
    }
}