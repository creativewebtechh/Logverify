<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\BrandingService;
use App\Services\MailSettings;
use App\Services\PaymentSettings;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Settings extends Component
{
    use WithFileUploads;

    public string $tab = 'general';

    // General
    public ?int $referral_commission_percent = null;

    public string $site_name = '';

    public string $currency = '';

    // Branding
    public $logo;

    public ?string $custom_logo = null;

    public $favicon;

    public ?string $custom_favicon = null;

    public string $brand_primary = '';

    public string $brand_accent = '';

    // Payments
    public string $payment_default_gateway = 'paystack';

    public string $paystack_public_key = '';

    public string $paystack_secret_key = '';

    public bool $paystack_test_mode = true;

    public string $monnify_client_key = '';

    public string $monnify_client_secret = '';

    public string $monnify_contract_code = '';

    public string $monnify_base_url = '';

    public bool $monnify_test_mode = true;

    // SMS numbers
    public ?int $numbers_timeout_minutes = null;

    public ?int $numbers_duplicate_window_seconds = null;

    public ?int $numbers_markup = null;

    // SMTP
    public bool $smtp_enabled = false;

    public string $smtp_host = '';

    public int $smtp_port = 2525;

    public string $smtp_username = '';

    public string $smtp_password = '';

    public string $smtp_encryption = 'tls';

    public string $smtp_from_address = '';

    public string $smtp_from_name = '';

    public string $smtp_test_email = '';

    // WhatsApp
    public bool $whatsapp_enabled = false;

    public string $whatsapp_number = '';

    public string $whatsapp_message = '';

    public string $whatsapp_label = '';

    public function mount(): void
    {
        $this->referral_commission_percent = (int) Setting::get('referral_commission_percent', config('app.referral_commission_percent', 10));
        $this->site_name = (string) Setting::get('site_name', config('app.name'));
        $this->currency = (string) Setting::get('currency', config('app.currency', 'NGN'));
        $this->custom_logo = filled(Setting::get('branding.logo')) ? (string) Setting::get('branding.logo') : null;
        $this->custom_favicon = filled(Setting::get('branding.favicon')) ? (string) Setting::get('branding.favicon') : null;
        $this->brand_primary = BrandingService::brandPrimary();
        $this->brand_accent = BrandingService::accentPrimary();

        $this->numbers_timeout_minutes = (int) Setting::get('smm.numbers.timeout_minutes', 15);
        $this->numbers_duplicate_window_seconds = (int) Setting::get('smm.numbers.duplicate_window_seconds', 10);
        $this->numbers_markup = (int) Setting::get('smm.pricing.numbers_markup', 30);

        $this->payment_default_gateway = PaymentSettings::defaultGateway();
        $this->paystack_public_key = (string) PaymentSettings::paystackPublicKey();
        $this->paystack_secret_key = (string) PaymentSettings::paystackSecretKey();
        $this->paystack_test_mode = PaymentSettings::paystackTestMode();
        $this->monnify_client_key = (string) PaymentSettings::monnifyClientKey();
        $this->monnify_client_secret = (string) PaymentSettings::monnifyClientSecret();
        $this->monnify_contract_code = (string) PaymentSettings::monnifyContractCode();
        $this->monnify_base_url = (string) PaymentSettings::monnifyBaseUrl();
        $this->monnify_test_mode = PaymentSettings::monnifyTestMode();

        $this->smtp_enabled = MailSettings::enabled();
        $this->smtp_host = (string) (MailSettings::host() ?? '');
        $this->smtp_port = MailSettings::port();
        $this->smtp_username = (string) (MailSettings::username() ?? '');
        $this->smtp_encryption = (string) (MailSettings::encryption() ?? 'tls');
        $this->smtp_from_address = (string) (MailSettings::fromAddress() ?? '');
        $this->smtp_from_name = (string) (MailSettings::fromName() ?? BrandingService::siteName());

        $this->whatsapp_enabled = WhatsAppService::enabled();
        $this->whatsapp_number = (string) (WhatsAppService::number() ?? '');
        $this->whatsapp_message = WhatsAppService::message();
        $this->whatsapp_label = WhatsAppService::label();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['general', 'branding', 'payments', 'numbers', 'smtp', 'whatsapp'], true)
            ? $tab
            : 'general';
    }

    public function saveLogo(): void
    {
        $this->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $directory = public_path('images/branding');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = strtolower($this->logo->getClientOriginalExtension()) ?: 'jpg';
        $path = 'images/branding/logo.'.$extension;

        File::copy($this->logo->getRealPath(), public_path($path));

        Setting::set('branding.logo', $path);
        $this->custom_logo = $path;
        $this->logo = null;

        session()->flash('success', 'Logo updated. It now appears across the site.');
    }

    public function removeLogo(): void
    {
        Setting::set('branding.logo', '');
        $this->custom_logo = null;
        $this->logo = null;

        session()->flash('success', 'Logo reset to the default.');
    }

    public function saveBranding(): void
    {
        $this->validate([
            'brand_primary' => ['required', 'regex:/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/'],
            'brand_accent' => ['required', 'regex:/^#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/'],
            'favicon' => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::set('branding.primary', str_starts_with($this->brand_primary, '#') ? $this->brand_primary : '#'.$this->brand_primary);
        Setting::set('branding.accent', str_starts_with($this->brand_accent, '#') ? $this->brand_accent : '#'.$this->brand_accent);

        if ($this->favicon) {
            $directory = public_path('images/branding');
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $extension = strtolower($this->favicon->getClientOriginalExtension()) ?: 'png';
            $path = 'images/branding/favicon.'.$extension;

            File::copy($this->favicon->getRealPath(), public_path($path));

            Setting::set('branding.favicon', $path);
            $this->custom_favicon = $path;
            $this->favicon = null;
        }

        session()->flash('success', 'Branding updated.');
    }

    public function save(): void
    {
        $data = $this->validate([
            'referral_commission_percent' => ['required', 'integer', 'between:0,50'],
            'site_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:5'],
            'payment_default_gateway' => ['required', 'in:paystack,monnify'],
            'paystack_public_key' => ['nullable', 'string', 'max:255'],
            'paystack_secret_key' => ['nullable', 'string', 'max:255'],
            'monnify_client_key' => ['nullable', 'string', 'max:255'],
            'monnify_client_secret' => ['nullable', 'string', 'max:255'],
            'monnify_contract_code' => ['nullable', 'string', 'max:255'],
            'monnify_base_url' => ['nullable', 'url', 'max:255'],
        ]);

        foreach (['referral_commission_percent', 'site_name', 'currency'] as $key) {
            Setting::set($key, $data[$key]);
        }

        PaymentSettings::set('default_gateway', $data['payment_default_gateway']);
        PaymentSettings::set('paystack_public_key', trim((string) $data['paystack_public_key']));
        PaymentSettings::set('paystack_secret_key', trim((string) $data['paystack_secret_key']));
        PaymentSettings::set('paystack_test_mode', $this->paystack_test_mode);
        PaymentSettings::set('monnify_client_key', trim((string) $data['monnify_client_key']));
        PaymentSettings::set('monnify_client_secret', trim((string) $data['monnify_client_secret']));
        PaymentSettings::set('monnify_contract_code', trim((string) $data['monnify_contract_code']));
        PaymentSettings::set('monnify_base_url', rtrim(trim((string) $data['monnify_base_url']), '/'));
        PaymentSettings::set('monnify_test_mode', $this->monnify_test_mode);

        session()->flash('success', 'Settings saved.');
    }

    public function saveNumbers(): void
    {
        $data = $this->validate([
            'numbers_timeout_minutes' => ['required', 'integer', 'between:1,240'],
            'numbers_duplicate_window_seconds' => ['required', 'integer', 'between:0,120'],
            'numbers_markup' => ['required', 'integer', 'between:0,1000'],
        ]);

        Setting::set('smm.numbers.timeout_minutes', $data['numbers_timeout_minutes']);
        Setting::set('smm.numbers.duplicate_window_seconds', $data['numbers_duplicate_window_seconds']);
        Setting::set('smm.pricing.numbers_markup', $data['numbers_markup']);

        session()->flash('success', 'SMS numbers settings saved.');
    }

    public function saveSmtp(): void
    {
        $this->validate([
            'smtp_host' => $this->smtp_enabled ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['required', 'in:tls,ssl,none'],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
        ]);

        MailSettings::set('enabled', $this->smtp_enabled);
        MailSettings::set('host', trim($this->smtp_host));
        MailSettings::set('port', $this->smtp_port);
        MailSettings::set('username', trim($this->smtp_username));
        MailSettings::set('encryption', $this->smtp_encryption === 'none' ? '' : $this->smtp_encryption);
        MailSettings::set('from_address', trim($this->smtp_from_address));
        MailSettings::set('from_name', trim($this->smtp_from_name));

        if (filled(trim($this->smtp_password))) {
            MailSettings::savePassword($this->smtp_password);
        }
        $this->smtp_password = '';

        MailSettings::apply();

        session()->flash('success', 'SMTP settings saved.');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'smtp_test_email' => ['required', 'email'],
        ]);

        MailSettings::apply();

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').'. Your mail settings are working correctly.',
                function ($message) {
                    $message->to($this->smtp_test_email)
                        ->subject('Test email from '.config('app.name'));
                }
            );
        } catch (\Throwable $e) {
            session()->flash('error', 'Test email could not be sent: '.$e->getMessage());

            return;
        }

        $this->smtp_test_email = '';

        session()->flash('success', 'Test email sent.');
    }

    public function saveWhatsApp(): void
    {
        $this->validate([
            'whatsapp_number' => ['required_if:whatsapp_enabled,true', 'nullable', 'string', 'max:20'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'whatsapp_label' => ['nullable', 'string', 'max:100'],
        ]);

        Setting::set('whatsapp.enabled', $this->whatsapp_enabled);
        Setting::set('whatsapp.number', trim($this->whatsapp_number));
        Setting::set('whatsapp.message', trim($this->whatsapp_message));
        Setting::set('whatsapp.label', trim($this->whatsapp_label));

        session()->flash('success', 'WhatsApp widget settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
