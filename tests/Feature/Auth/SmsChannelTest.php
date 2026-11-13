<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class SmsChannelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_msgowl_request_has_correct_url_header_and_prefix()
    {
        config([
            'services.sms.driver' => 'msgowl',
            'services.msgowl.access_key' => 'test-key',
            'services.msgowl.sender_id' => 'H4R',
        ]);
        Http::fake(['rest.msgowl.com/*' => Http::response(['message' => 'ok'], 200)]);

        $user = User::factory()->role('client')->create(['phone' => '7771234']);
        app(OtpService::class)->send($user, 'phone_verification', 'sms');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://rest.msgowl.com/messages'
                && $request->hasHeader('Authorization', 'AccessKey test-key')
                && $request['recipients'] === '9607771234'
                && $request['sender_id'] === 'H4R';
        });
    }

    public function test_msgowl_throws_exception_and_logs_error_when_request_fails()
    {
        config([
            'services.sms.driver' => 'msgowl',
            'services.msgowl.access_key' => 'test-key',
            'services.msgowl.sender_id' => 'H4R',
        ]);
        Http::fake([
            'rest.msgowl.com/*' => Http::response(['message' => 'Insufficient balance'], 402),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'MsgOwl SMS failed'
                    && $context['status'] === 402
                    && str_contains($context['body'], 'Insufficient balance');
            });

        $user = User::factory()->role('client')->create(['phone' => '7771234']);

        $this->expectException(RuntimeException::class);

        app(OtpService::class)->send($user, 'phone_verification', 'sms');
    }
}
