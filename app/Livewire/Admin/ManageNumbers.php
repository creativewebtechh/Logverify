<?php

namespace App\Livewire\Admin;

use App\Models\Number;
use App\Models\Provider;
use App\Services\Providers\ProviderSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageNumbers extends Component
{
    use WithPagination;

    public string $country = '';

    public string $category = '';

    public string $masked_number = '';

    public ?string $price = null;

    public string $provider_service_id = '';

    public ?int $provider_id = null;

    public string $status = 'available';

    public string $search = '';

    public string $filterStatus = '';

    #[Locked]
    public ?int $editingId = null;

    protected $paginationTheme = 'tailwind';

    public function add(): void
    {
        $data = $this->validate([
            'country' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:sms,whatsapp,voice'],
            'masked_number' => ['required', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0'],
            'provider_service_id' => ['nullable', 'string', 'max:100'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'status' => ['required', 'in:available,sold'],
        ]);

        $payload = $data + [
            'number' => $this->masked_number,
            'provider' => ProviderSettings::driver(ProviderSettings::CHANNEL_NUMBERS),
        ];

        if ($this->editingId !== null) {
            Number::findOrFail($this->editingId)->update($payload);
            $message = 'Number updated.';
        } else {
            Number::create($payload);
            $message = 'Number added.';
        }

        $this->startAdd();
        session()->flash('success', $message);
    }

    public function edit(int $id): void
    {
        $number = Number::findOrFail($id);

        $this->editingId = $number->id;
        $this->country = $number->country;
        $this->category = $number->category ?? '';
        $this->masked_number = $number->masked_number;
        $this->price = (string) $number->price;
        $this->provider_service_id = $number->provider_service_id ?? '';
        $this->provider_id = $number->provider_id;
        $this->status = $number->status;
        $this->resetValidation();
    }

    public function startAdd(): void
    {
        $this->reset([
            'country',
            'category',
            'masked_number',
            'price',
            'provider_service_id',
            'provider_id',
            'status',
            'editingId',
        ]);
        $this->status = 'available';
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        Number::findOrFail($id)->delete();
    }

    public function render()
    {
        $query = Number::query();

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('masked_number', 'like', "%{$this->search}%")
                    ->orWhere('country', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%");
            });
        }

        if (filled($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.admin.manage-numbers', [
            'numbers' => $query->latest()->paginate(15),
            'providers' => Provider::query()->forChannel(Provider::CHANNEL_NUMBERS)->active()->orderBy('priority')->orderBy('name')->get(),
            'provider_driver' => ProviderSettings::driver(ProviderSettings::CHANNEL_NUMBERS),
            'provider_connected' => ProviderSettings::configured(ProviderSettings::CHANNEL_NUMBERS),
            'provider_balance' => ProviderSettings::balance(ProviderSettings::CHANNEL_NUMBERS),
            'provider_last_sync' => ProviderSettings::lastSync(ProviderSettings::CHANNEL_NUMBERS),
        ]);
    }
}
