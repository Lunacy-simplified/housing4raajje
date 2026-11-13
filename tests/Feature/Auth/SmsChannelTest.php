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
        $this->expectExceptionMessage('SMS could not be sent.');

        app(OtpService::class)->send($user, 'phone_verification', 'sms');
    }

    public function test_user_without_a_phone_gets_no_sms_request()
    {
        config([
            'services.sms.driver' => 'msgowl',
            'services.msgowl.access_key' => 'test-key',
            'services.msgowl.sender_id' => 'H4R',
        ]);
        Http::fake();

        $user = User::factory()->role('client')->create(['phone' => null]);

        // try/catch instead of expectException, so we can still check Http afterwards.
        try {
            app(OtpService::class)->send($user, 'phone_verification', 'sms');
            $this->fail('Expected an exception for a user without a phone.');
        } catch (RuntimeException $e) {
            $this->assertSame('User has no phone number to send an SMS to.', $e->getMessage());
        }

        Http::assertNothingSent();
    }
}
