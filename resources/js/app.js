import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm.js';

import './bootstrap';

/**
 * Global Alpine store used by small UI islands
 * (mobile menu, dropdowns, share/copy actions).
 */
Alpine.store('app', {
    mobileMenuOpen: false,
    toggleMobileMenu() {
        this.mobileMenuOpen = !this.mobileMenuOpen;
    },
    toast(message) {
        window.dispatchEvent(new CustomEvent('app:toast', { detail: { message } }));
    },
});

document.addEventListener('alpine:init', () => {
    Alpine.data('clipboard', (target = null) => ({
        copied: false,
        async copy(e) {
            const value = target ?? this.$refs[target]?.value ?? '';
            try {
                await navigator.clipboard.writeText(value);
            } catch (err) {
                const el = document.createElement('textarea');
                el.value = value;
                el.style.position = 'fixed';
                el.style.opacity = '0';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    }));

    Alpine.data('passwordStrength', () => ({
        pw: '',
        show: false,
        get score() {
            let s = 0;
            if (this.pw.length >= 8) s++;
            if (this.pw.length >= 12) s++;
            if (/[a-z]/.test(this.pw) && /[A-Z]/.test(this.pw)) s++;
            if (/\d/.test(this.pw)) s++;
            if (/[^a-zA-Z0-9]/.test(this.pw)) s++;
            return s;
        },
        get strength() {
            const s = this.score;
            if (s === 0) return { label: 'Too short', color: 'bg-rose-500', text: 'text-rose-600' };
            if (s <= 2) return { label: 'Weak', color: 'bg-rose-500', text: 'text-rose-600' };
            if (s === 3) return { label: 'Fair', color: 'bg-amber-500', text: 'text-amber-600' };
            if (s === 4) return { label: 'Good', color: 'bg-brand-500', text: 'text-brand-600' };
            return { label: 'Strong', color: 'bg-emerald-500', text: 'text-emerald-600' };
        },
    }));
});

Livewire.start();
