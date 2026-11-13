<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Identitas usaha (nama, alamat, kontak, logo, struk, printer).
 * Tabel ini hanya berisi satu baris; akses selalu lewat StoreSetting::current().
 */
class StoreSetting extends Model
{
    public const DEFAULT_NAME = 'Toko Saya';

    protected $guarded = [];

    private static ?self $cached = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::$cached = null);
    }

    public static function current(): self
    {
        if (static::$cached) {
            return static::$cached;
        }

        try {
            $setting = static::query()->first();
        } catch (Throwable) {
            // Tabel belum dimigrasi (mis. saat setup awal): pakai nilai default.
            return new static(['store_name' => self::DEFAULT_NAME, 'receipt_footer' => 'Terima Kasih']);
        }

        return static::$cached = $setting
            ?? static::query()->create(['store_name' => self::DEFAULT_NAME, 'receipt_footer' => 'Terima Kasih']);
    }

    public static function storeName(): string
    {
        return static::current()->store_name ?: self::DEFAULT_NAME;
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
