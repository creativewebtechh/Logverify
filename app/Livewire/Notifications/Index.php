<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Component;

class Index extends Component
{
    public function markRead(int $id): void
    {
        Notification::forUser(auth()->id())->findOrFail($id)->markAsRead();
    }

    public function markAllRead(): void
    {
        Notification::forUser(auth()->id())->unread()->update(['read_at' => now()]);
        Notification::forgetUnreadCache(auth()->id());
    }

    public function render()
    {
        return view('livewire.notifications.index', [
            'notifications' => Notification::forUser(auth()->id())->latest()->paginate(20),
        ]);
    }
}
