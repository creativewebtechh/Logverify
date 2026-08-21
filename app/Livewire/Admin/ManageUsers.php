<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageUsers extends Component
{
    use WithPagination;

    public string $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        $user->update(['status' => ! $user->status]);
    }

    public function toggleRole(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot change your own role.');

            return;
        }

        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        $user->update(['role' => $user->role === 'admin' ? 'customer' : 'admin']);
    }

    public function toggleVerified(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        $user->update(['is_verified' => ! $user->is_verified]);
    }

    public function delete(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user = User::findOrFail($userId);
        $this->authorize('delete', $user);

        $user->delete();
        session()->flash('success', 'User deleted.');
    }

    public function render()
    {
        return view('livewire.admin.manage-users', [
            'users' => User::with('wallet')
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('referral_code', 'like', "%{$this->search}%")))
                ->latest()
                ->paginate(15),
        ]);
    }
}
