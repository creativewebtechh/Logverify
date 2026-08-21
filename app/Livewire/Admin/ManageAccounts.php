<?php

namespace App\Livewire\Admin;

use App\Models\Account;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageAccounts extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $platform = '';

    public string $title = '';

    public string $description = '';

    public ?string $price = null;

    public int $stock = 1;

    public bool $featured = false;

    public $image;

    public ?string $existingImage = null;

    public string $provider_service_id = '';

    public string $provider = '';

    public string $status = 'available';

    public ?string $account_link = null;

    public array $credentials = ['email' => '', 'password' => '', 'phone' => ''];

    public array $screenshots = [null, null, null];

    public array $existingScreenshots = [];

    public array $removedScreenshots = [];

    public string $search = '';

    public string $filterStatus = '';

    #[Locked]
    public ?int $editingId = null;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->screenshots = [null, null, null];
    }

    public function add(): void
    {
        $this->account_link = filled(trim((string) $this->account_link)) ? trim((string) $this->account_link) : null;

        foreach (['email', 'password', 'phone'] as $field) {
            $this->credentials[$field] = filled(trim((string) ($this->credentials[$field] ?? '')))
                ? trim((string) $this->credentials[$field])
                : null;
        }

        $data = $this->validate([
            'platform' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:available,sold,pending'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'featured' => ['boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'account_link' => ['nullable', 'url', 'max:255'],
            'credentials.email' => ['nullable', 'email', 'max:255'],
            'credentials.password' => ['nullable', 'string', 'max:255'],
            'credentials.phone' => ['nullable', 'string', 'max:50'],
            'provider_service_id' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:50'],
            'screenshots.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $credentials = array_filter($this->credentials, fn ($value) => filled($value));

        $editing = $this->editingId !== null ? Account::find($this->editingId) : null;

        $imagePath = $editing?->image;
        if ($this->image) {
            $imagePath = $this->image->store('account-images', 'public');
        }

        unset($data['credentials'], $data['screenshots'], $data['image']);

        $screenshots = $editing?->meta['screenshots'] ?? [];
        foreach ($this->screenshots as $index => $file) {
            if ($file) {
                $screenshots[$index] = $file->store('account-screenshots', 'public');
            }
        }
        $screenshots = array_values(array_filter(
            $screenshots,
            fn ($path) => filled($path) && ! in_array($path, $this->removedScreenshots, true)
        ));

        $meta = array_merge($editing?->meta ?? [], [
            'account_link' => $this->account_link,
        ]);
        if ($credentials !== []) {
            $data['credentials'] = $credentials;
        }
        if ($screenshots !== []) {
            $meta['screenshots'] = $screenshots;
        }
        if ($imagePath !== null) {
            $data['image'] = $imagePath;
        }

        if ($this->editingId !== null) {
            $editing->update([...$data, 'meta' => $meta]);
            $message = 'Account updated.';
        } else {
            Account::create([...$data, 'meta' => $meta]);
            $message = 'Account added.';
        }

        $this->startAdd();
        session()->flash('success', $message);
    }

    public function edit(int $id): void
    {
        $account = Account::findOrFail($id);

        $this->editingId = $account->id;
        $this->platform = $account->platform;
        $this->title = $account->title;
        $this->description = $account->description ?? '';
        $this->price = (string) $account->price;
        $this->stock = (int) $account->stock;
        $this->featured = (bool) $account->featured;
        $this->existingImage = $account->image;
        $this->image = null;
        $this->provider_service_id = $account->provider_service_id ?? '';
        $this->provider = $account->provider ?? '';
        $this->status = $account->status;
        $this->account_link = $account->meta['account_link'] ?? '';
        $this->credentials = array_merge(['email' => '', 'password' => '', 'phone' => ''], $account->credentials ?? []);
        $this->screenshots = [null, null, null];
        $this->existingScreenshots = $account->meta['screenshots'] ?? [];
        $this->removedScreenshots = [];
        $this->resetValidation();
    }

    public function removeScreenshot(int $index): void
    {
        if (isset($this->existingScreenshots[$index])) {
            $this->removedScreenshots[] = $this->existingScreenshots[$index];
            unset($this->existingScreenshots[$index]);
            $this->existingScreenshots = array_values($this->existingScreenshots);
        }
    }

    public function startAdd(): void
    {
        $this->reset([
            'platform',
            'title',
            'description',
            'price',
            'provider_service_id',
            'provider',
            'status',
            'account_link',
            'editingId',
        ]);
        $this->stock = 1;
        $this->featured = false;
        $this->image = null;
        $this->existingImage = null;
        $this->credentials = ['email' => '', 'password' => '', 'phone' => ''];
        $this->screenshots = [null, null, null];
        $this->existingScreenshots = [];
        $this->removedScreenshots = [];
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        Account::findOrFail($id)->delete();
    }

    public function render()
    {
        $query = Account::query();

        if (filled($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('platform', 'like', "%{$this->search}%");
            });
        }

        if (filled($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        return view('livewire.admin.manage-accounts', [
            'accounts' => $query->latest()->paginate(15),
        ]);
    }
}
