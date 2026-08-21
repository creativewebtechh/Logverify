<?php

namespace App\Livewire\Admin;

use App\Models\Tool;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageTools extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public ?string $price = null;

    public string $category = '';

    public string $icon = 'sparkles';

    public int $stock = 1;

    public bool $featured = false;

    public $image;

    public ?string $existingImage = null;

    public string $download_url = '';

    #[Locked]
    public ?int $editingId = null;

    protected $paginationTheme = 'tailwind';

    public function add(): void
    {
        if (blank($this->slug)) {
            $this->slug = Str::slug($this->name);
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', 'unique:tools,slug,'.($this->editingId ?? 'NULL').',id'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'featured' => ['boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'download_url' => ['nullable', 'url', 'max:255'],
        ]);

        $imagePath = $this->editingId !== null ? Tool::find($this->editingId)?->image : null;
        if ($this->image) {
            $imagePath = $this->image->store('tool-images', 'public');
        }

        unset($data['image']);

        if ($imagePath !== null) {
            $data['image'] = $imagePath;
        }

        if ($this->editingId !== null) {
            Tool::findOrFail($this->editingId)->update($data);
            $message = 'Tool updated.';
        } else {
            Tool::create($data + ['status' => 'active']);
            $message = 'Tool added.';
        }

        $this->startAdd();
        session()->flash('success', $message);
    }

    public function edit(int $id): void
    {
        $tool = Tool::findOrFail($id);

        $this->editingId = $tool->id;
        $this->name = $tool->name;
        $this->slug = $tool->slug;
        $this->description = $tool->description ?? '';
        $this->price = (string) $tool->price;
        $this->category = $tool->category ?? '';
        $this->icon = $tool->icon ?? 'sparkles';
        $this->stock = (int) $tool->stock;
        $this->featured = (bool) $tool->featured;
        $this->existingImage = $tool->image;
        $this->image = null;
        $this->download_url = $tool->download_url ?? '';
        $this->resetValidation();
    }

    public function startAdd(): void
    {
        $this->reset([
            'name',
            'slug',
            'description',
            'price',
            'category',
            'icon',
            'download_url',
            'editingId',
        ]);
        $this->stock = 1;
        $this->featured = false;
        $this->image = null;
        $this->existingImage = null;
        $this->resetValidation();
    }

    public function toggleStatus(int $id): void
    {
        $tool = Tool::findOrFail($id);
        $tool->update(['status' => $tool->status === 'active' ? 'disabled' : 'active']);
    }

    public function delete(int $id): void
    {
        Tool::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.admin.manage-tools', [
            'tools' => Tool::latest()->paginate(15),
        ]);
    }
}
