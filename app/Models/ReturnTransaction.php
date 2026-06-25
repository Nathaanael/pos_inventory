<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\ReturnType;
use App\Traits\HasSequentialCode;
use Illuminate\Database\Eloquent\Model;

class ReturnTransaction extends Model
{
    use HasSequentialCode;

    protected $table = 'returns';
    
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'status' => TransactionStatus::class,
        // type cast will be enabled later when column is re-added
        // 'type' => ReturnType::class, 
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->transaction_code)) {
                $model->transaction_code = self::generateTransactionCode('RET');
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }
}