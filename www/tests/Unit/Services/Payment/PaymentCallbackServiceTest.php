<?php

namespace Tests\Unit\Services\Payment;

use App\Contracts\Payment\PaymentCallbackHandlerInterface;
use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Donation\DonationStatus;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Exceptions\Payment\PaymentCallbackException;
use App\Models\Donation\Donation;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentCallbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCallbackServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentCallbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentCallbackService();
    }

    #[Test]
    public function it_registers_a_callback_handler(): void
    {
        $handler = Mockery::mock(PaymentCallbackHandlerInterface::class);
        $handler->shouldReceive('getPaymentMethod')
            ->andReturn(PaymentMethodEnum::FAKE);

        $this->service->registerHandler($handler);

        $handlers = $this->service->getHandlers();
        $this->assertArrayHasKey('fake', $handlers);
        $this->assertSame($handler, $handlers['fake']);
    }

    #[Test]
    public function it_processes_successful_payment_callback(): void
    {
        $donation = Donation::factory()->create([
            'status' => DonationStatus::PENDING,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([
            'status' => 'success',
            'transaction_id' => 'TXN_123',
        ]);

        $handler = Mockery::mock(PaymentCallbackHandlerInterface::class);
        $handler->shouldReceive('getPaymentMethod')
            ->andReturn(PaymentMethodEnum::FAKE);
        $handler->shouldReceive('validateCallback')
            ->with($payment, $request)
            ->andReturn(true);
        $handler->shouldReceive('handleCallback')
            ->with($payment, $request)
            ->andReturn(PaymentCallbackResultDTO::success(
                transactionId: 'TXN_123',
                gatewayResponse: ['gateway' => 'fake']
            ));

        $this->service->registerHandler($handler);

        $result = $this->service->processCallback($payment, $request);

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('TXN_123', $result->transactionId);

        // Verify payment was updated
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::COMPLETED, $payment->status);
        $this->assertEquals('TXN_123', $payment->transaction_id);

        // Verify donation was updated
        $donation->refresh();
        $this->assertEquals(DonationStatus::SUCCESS, $donation->status);
        $this->assertNull($donation->error_message);
    }

    #[Test]
    public function it_processes_failed_payment_callback(): void
    {
        $donation = Donation::factory()->create([
            'status' => DonationStatus::PENDING,
        ]);

        $payment = Payment::factory()->create([
            'donation_id' => $donation->id,
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([
            'status' => 'failed',
        ]);

        $handler = Mockery::mock(PaymentCallbackHandlerInterface::class);
        $handler->shouldReceive('getPaymentMethod')
            ->andReturn(PaymentMethodEnum::FAKE);
        $handler->shouldReceive('validateCallback')
            ->with($payment, $request)
            ->andReturn(true);
        $handler->shouldReceive('handleCallback')
            ->with($payment, $request)
            ->andReturn(PaymentCallbackResultDTO::failed(
                errorMessage: 'Payment declined',
                errorCode: 'DECLINED'
            ));

        $this->service->registerHandler($handler);

        $result = $this->service->processCallback($payment, $request);

        $this->assertTrue($result->isFailed());
        $this->assertEquals('Payment declined', $result->errorMessage);

        // Verify payment was updated
        $payment->refresh();
        $this->assertEquals(PaymentStatusEnum::FAILED, $payment->status);
        $this->assertEquals('Payment declined', $payment->error_message);

        // Verify donation was updated
        $donation->refresh();
        $this->assertEquals(DonationStatus::FAILED, $donation->status);
        $this->assertEquals('Payment declined', $donation->error_message);
    }

    #[Test]
    public function it_throws_exception_when_callback_validation_fails(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([]);

        $handler = Mockery::mock(PaymentCallbackHandlerInterface::class);
        $handler->shouldReceive('getPaymentMethod')
            ->andReturn(PaymentMethodEnum::FAKE);
        $handler->shouldReceive('validateCallback')
            ->with($payment, $request)
            ->andReturn(false);

        $this->service->registerHandler($handler);

        $this->expectException(PaymentCallbackException::class);
        $this->expectExceptionMessage('Payment callback validation failed');

        $this->service->processCallback($payment, $request);
    }

    #[Test]
    public function it_throws_exception_when_no_handler_is_registered(): void
    {
        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethodEnum::FAKE,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        $request = new Request([]);

        $this->expectException(PaymentCallbackException::class);
        $this->expectExceptionMessage('No callback handler registered for payment method: fake');

        $this->service->processCallback($payment, $request);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
