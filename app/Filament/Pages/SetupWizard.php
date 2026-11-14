<?php

namespace App\Filament\Pages;

use App\Enums\BusinessType;
use App\Enums\Feature;
use App\Models\StoreSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Wizard setup toko. Muncul untuk admin selama toko belum di-setup
 * (lihat App\Http\Middleware\EnsureStoreIsSetUp).
 *
 * Langkah: 1 Identitas Toko → 2 Jenis Usaha → 3 Fitur → 4 Ringkasan.
 */
class SetupWizard extends Page
{
    protected static ?string $slug = 'setup';

    protected static ?string $title = 'Setup Toko';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.base';

    protected string $view = 'filament.pages.setup-wizard';

    public const STEPS = [
        1 => 'Identitas Toko',
        2 => 'Jenis Usaha',
        3 => 'Fitur',
        4 => 'Ringkasan',
    ];

    public int $step = 1;

    public string $store_name = '';

    public ?string $tagline = null;

    public ?string $phone = null;

    public ?string $address = null;

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public ?string $businessType = null;

    /** @var array<int, string> */
    public array $features = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $setting = StoreSetting::current();

        if ($setting->isSetUp()) {
            $this->redirect(Dashboard::getUrl());

            return;
        }

        $this->store_name = $setting->store_name === StoreSetting::DEFAULT_NAME ? '' : (string) $setting->store_name;
        $this->tagline = $setting->tagline;
        $this->phone = $setting->phone;
        $this->address = $setting->address;
    }

    /* ------------------------------ Navigasi ------------------------------ */

    public function next(): void
    {
        $this->validateStep($this->step);

        if ($this->step < count(self::STEPS)) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    /** Klik langkah di panel kiri: hanya boleh ke langkah yang sudah dilewati. */
    public function goTo(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    /* ------------------------------ Pilihan ------------------------------ */

    public function selectBusinessType(string $type): void
    {
        $business = BusinessType::tryFrom($type);

        if (! $business) {
            return;
        }

        // Ganti jenis usaha → centang ulang fitur sesuai preset.
        if ($this->businessType !== $business->value) {
            $this->businessType = $business->value;
            $this->features = array_map(fn (Feature $f) => $f->value, $business->defaultFeatures());
        }

        $this->resetErrorBag('businessType');
    }

    public function toggleFeature(string $feature): void
    {
        if (! Feature::tryFrom($feature)) {
            return;
        }

        $this->features = in_array($feature, $this->features, true)
            ? array_values(array_diff($this->features, [$feature]))
            : [...$this->features, $feature];
    }

    public function removeLogo(): void
    {
        $this->logo = null;
    }

    /* ------------------------------ Simpan ------------------------------ */

    public function finish(): void
    {
        foreach (array_keys(self::STEPS) as $step) {
            try {
                $this->validateStep($step);
            } catch (ValidationException $e) {
                $this->step = $step;

                throw $e;
            }
        }

        $setting = StoreSetting::current();

        $data = [
            'store_name' => trim($this->store_name),
            'tagline' => filled($this->tagline) ? trim($this->tagline) : null,
            'phone' => filled($this->phone) ? trim($this->phone) : null,
            'address' => filled($this->address) ? trim($this->address) : null,
            'business_type' => $this->businessType,
            'setup_completed_at' => now(),
        ];

        if ($this->logo instanceof TemporaryUploadedFile) {
            $data['logo'] = $this->logo->store('store', 'public');
        }

        DB::transaction(function () use ($setting, $data) {
            $setting->update($data);
            StoreSetting::saveFeatures($this->features);
        });

        StoreSetting::flushCache();

        Notification::make()
            ->title('Toko siap digunakan')
            ->body('Pengaturan bisa diubah kapan saja di menu Pengaturan Toko.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }

    private function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate([
                'store_name' => ['required', 'string', 'max:100'],
                'tagline' => ['nullable', 'string', 'max:150'],
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:500'],
                'logo' => ['nullable', 'image', 'max:1024'],
            ], [], [
                'store_name' => 'Nama toko',
                'tagline' => 'Keterangan singkat',
                'phone' => 'Telepon',
                'address' => 'Alamat',
                'logo' => 'Logo',
            ]),
            2 => $this->validate(
                ['businessType' => ['required', Rule::enum(BusinessType::class)]],
                ['businessType.required' => 'Pilih salah satu jenis usaha.'],
            ),
            3 => $this->validate([
                'features' => ['array'],
                'features.*' => [Rule::enum(Feature::class)],
            ]),
            default => null,
        };
    }

    /* ------------------------------ Untuk view ------------------------------ */

    public function getSelectedBusiness(): ?BusinessType
    {
        return $this->businessType ? BusinessType::tryFrom($this->businessType) : null;
    }
}
