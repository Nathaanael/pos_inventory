<?php

namespace Tests;

use App\Enums\Feature;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // StoreSetting menyimpan cache statis; kosongkan supaya tiap test mulai bersih.
        StoreSetting::flushCache();
    }

    /**
     * Tandai toko sudah melewati wizard setup, dengan fitur tertentu aktif (default: semua
     * yang tersedia untuk jenis usahanya; fitur khusus FNB hanya aktif bila $business = 'makanan').
     */
    protected function markStoreAsSetUp(?array $features = null, string $business = 'umum'): void
    {
        StoreSetting::current()->update(['business_type' => $business, 'setup_completed_at' => now()]);
        StoreSetting::saveFeatures($features ?? Feature::cases());
        StoreSetting::flushCache();
    }
}
