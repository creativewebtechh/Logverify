<?php

namespace App\Livewire\Wallet;

use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?string $type = null;

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::where('user_id', auth()->id())
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->latest();

        return view('livewire.wallet.transactions', [
            'transactions' => $query->paginate(15),
            'types' => [
                '' => 'All',
                Transaction::TYPE_DEPOSIT => 'Deposits',
                Transaction::TYPE_PURCHASE => 'Purchases',
                Transaction::TYPE_REFERRAL_COMMISSION => 'Referrals',
            ],
        ]);
    }
}
