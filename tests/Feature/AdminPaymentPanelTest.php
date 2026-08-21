<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageTransactions;
use App\Livewire\Admin\WebhookLogs;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPaymentPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    private function pendingDeposit(float $amount = 2500): array
    {
        $user = User::create([
            'name' => 'Pending Customer',
            'email' => 'pending@logverify.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        app(WalletService::class)->getOrCreateWallet($user);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'currency' => 'NGN',
            'balance_after' => 0,
            'status' => Transaction::STATUS_PENDING,
            'payment_status' => Transaction::PAYMENT_PENDING,
            'reference' => 'LV-ADMIN-RETRY',
            'gateway' => 'paystack',
            'payment_method' => 'card',
            'description' => 'Wallet funding',
        ]);

        return [$user, $transaction];
    }

    public function test_retry_verification_credits_wallet(): void
    {
        config(['paystack.test_mode' => false, 'paystack.secret_key' => 'secret-key']);

        [$user, $transaction] = $this->pendingDeposit();

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'success',
                    'reference' => 'LV-ADMIN-RETRY',
                    'amount' => 250000,
                    'fees' => 2500,
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'id' => 77,
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->admin())
            ->test(ManageTransactions::class)
            ->call('retryVerification', $transaction->id)
            ->assertHasNoErrors();

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->fresh()->status);
        $this->assertSame(2500.0, (float) $user->fresh()->wallet->balance);
    }

    public function test_excel_export_streams_a_spreadsheet(): void
    {
        $this->pendingDeposit();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.transactions.export.excel', ['type' => 'deposit']));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', substr($content, 0, 2));

        $path = tempnam(sys_get_temp_dir(), 'lv-xlsx');
        file_put_contents($path, $content);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Export should be a valid ZIP/XLSX archive.');
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertNotNull($sheet);
        $this->assertStringContainsString('LV-ADMIN-RETRY', $sheet);
        $this->assertStringContainsString('Gateway Reference', $sheet);
    }

    public function test_csv_export_includes_payment_columns(): void
    {
        $this->pendingDeposit();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.transactions.export', ['type' => 'deposit']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('Payment Status', $response->streamedContent());
        $this->assertStringContainsString('Gateway Reference', $response->streamedContent());
    }

    public function test_webhook_logs_page_renders_with_logs(): void
    {
        $admin = $this->admin();

        WebhookLog::create([
            'gateway' => 'paystack',
            'event' => 'charge.success',
            'reference' => 'LV-WH-1',
            'status' => WebhookLog::STATUS_PROCESSED,
            'response_status' => 200,
            'source_ip' => '127.0.0.1',
            'payload' => ['event' => 'charge.success'],
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.webhooks'))
            ->assertOk()
            ->assertSee('LV-WH-1');

        Livewire::actingAs($admin)
            ->test(WebhookLogs::class)
            ->set('gateway', 'paystack')
            ->assertOk()
            ->assertSee('charge.success');
    }

    public function test_transactions_page_filters_by_search(): void
    {
        $this->pendingDeposit(5000);

        Livewire::actingAs($this->admin())
            ->test(ManageTransactions::class)
            ->set('search', 'LV-ADMIN-RETRY')
            ->assertOk()
            ->assertSee('LV-ADMIN-RETRY')
            ->assertDontSee('LV-OTHER-REFERENCE');
    }
}
