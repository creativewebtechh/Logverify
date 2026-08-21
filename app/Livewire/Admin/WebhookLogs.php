<?php

namespace App\Livewire\Admin;

use App\Models\WebhookLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class WebhookLogs extends Component
{
    use WithPagination;

    public ?string $gateway = null;

    public ?string $status = null;

    public ?string $search = null;

    protected $paginationTheme = 'tailwind';

    public function updatedGateway(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.webhook-logs', [
            'logs' => WebhookLog::query()
                ->forGateway($this->gateway)
                ->withStatus($this->status)
                ->search($this->search)
                ->latest()
                ->paginate(25),
        ]);
    }
}
