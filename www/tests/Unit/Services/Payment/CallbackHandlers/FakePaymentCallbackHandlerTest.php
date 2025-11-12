<?php

namespace Tests\Unit\Services\Payment\CallbackHandlers;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment\Payment;
use App\Services\Payment\CallbackHandlers\FakePaymentCallbackHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FakePaymentCallbackHandlerTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentCallbackHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new FakePaymentCallbackHandler();
    }

    #[Test]
    public function it_returns_the_correct_payment_method(): void
    {
        $this->assertEquals(PaymentMethodEnum::FAKE, $this->handler->getPaymentMethod());
    }

    #[Test]
    public function it_handles_successful_payment_callback(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
            'payload' => ['session_id' => 'SESSION_123'],
        ]);

        $request = new Request([
            'status' => 'success',
            'transaction_id' => 'TXN_123456',
            'session_id' => 'SESSION_123',
        ]);

        $result = $this->handler->handleCallback($payment, $request);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('TXN_123456', $result->transactionId);
        $this->assertArrayHasKey('gateway', $result->gatewayResponse);
        $this->assertEquals('fake', $result->gatewayResponse['gateway']);
        $this->assertEquals('payment.result', $result->redirectRoute);
        $this->assertEquals($payment->id, $result->redirectParams['payment']);
    }

    #[Test]
    public function it_handles_failed_payment_callback(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([
            'status' => 'failed',
            'error_message' => 'Insufficient funds',
            'error_code' => 'INSUFFICIENT_FUNDS',
        ]);

        $result = $this->handler->handleCallback($payment, $request);

        $this->assertTrue($result->isFailed());
        $this->assertEquals('Insufficient funds', $result->errorMessage);
        $this->assertEquals('INSUFFICIENT_FUNDS', $result->errorCode);
        $this->assertEquals('payment.result', $result->redirectRoute);
        $this->assertEquals($payment->id, $result->redirectParams['payment']);
    }

    #[Test]
    public function it_handles_callback_without_transaction_id_as_failure(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([
            'status' => 'success',
            // Missing transaction_id
        ]);

        $result = $this->handler->handleCallback($payment, $request);

        $this->assertTrue($result->isFailed());
    }

    #[Test]
    public function it_validates_callback_with_required_status(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        $request = new Request([
            'status' => 'success',
        ]);

        $this->assertTrue($this->handler->validateCallback($payment, $request));
    }

    #[Test]
    public function it_fails_validation_when_status_is_missing(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
        ]);

        $request = new Request([
            // Missing status
        ]);

        $this->assertFalse($this->handler->validateCallback($payment, $request));
    }

    #[Test]
    public function it_validates_session_id_when_present_in_both_payload_and_request(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'payload' => ['session_id' => 'SESSION_123'],
        ]);

        $request = new Request([
            'status' => 'success',
            'session_id' => 'SESSION_123',
        ]);

        $this->assertTrue($this->handler->validateCallback($payment, $request));
    }

    #[Test]
    public function it_fails_validation_when_session_ids_do_not_match(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'payload' => ['session_id' => 'SESSION_123'],
        ]);

        $request = new Request([
            'status' => 'success',
            'session_id' => 'DIFFERENT_SESSION',
        ]);

        $this->assertFalse($this->handler->validateCallback($payment, $request));
    }
}
