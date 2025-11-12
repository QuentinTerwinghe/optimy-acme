<?php

namespace Tests\Unit\DTOs\Payment;

use App\DTOs\Payment\PaymentCallbackResultDTO;
use App\Enums\Payment\PaymentStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCallbackResultDTOTest extends TestCase
{
    #[Test]
    public function it_creates_a_successful_callback_result(): void
    {
        $transactionId = 'TXN_123456';
        $gatewayResponse = ['status' => 'completed', 'amount' => 100.00];

        $result = PaymentCallbackResultDTO::success(
            transactionId: $transactionId,
            gatewayResponse: $gatewayResponse
        );

        $this->assertEquals(PaymentStatusEnum::COMPLETED, $result->status);
        $this->assertEquals($transactionId, $result->transactionId);
        $this->assertEquals($gatewayResponse, $result->gatewayResponse);
        $this->assertNull($result->errorMessage);
        $this->assertNull($result->errorCode);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('dashboard', $result->redirectRoute);
    }

    #[Test]
    public function it_creates_a_failed_callback_result(): void
    {
        $errorMessage = 'Insufficient funds';
        $errorCode = 'INSUFFICIENT_FUNDS';
        $gatewayResponse = ['error' => 'payment_failed'];

        $result = PaymentCallbackResultDTO::failed(
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            gatewayResponse: $gatewayResponse
        );

        $this->assertEquals(PaymentStatusEnum::FAILED, $result->status);
        $this->assertEquals($errorMessage, $result->errorMessage);
        $this->assertEquals($errorCode, $result->errorCode);
        $this->assertEquals($gatewayResponse, $result->gatewayResponse);
        $this->assertNull($result->transactionId);
        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->isFailed());
        $this->assertEquals('dashboard', $result->redirectRoute);
    }

    #[Test]
    public function it_creates_result_with_custom_redirect_route(): void
    {
        $result = PaymentCallbackResultDTO::success(
            transactionId: 'TXN_123',
            redirectRoute: 'custom.success.route',
            redirectParams: ['id' => 123]
        );

        $this->assertEquals('custom.success.route', $result->redirectRoute);
        $this->assertEquals(['id' => 123], $result->redirectParams);
    }

    #[Test]
    public function it_creates_result_via_constructor(): void
    {
        $result = new PaymentCallbackResultDTO(
            status: PaymentStatusEnum::PROCESSING,
            transactionId: 'TXN_789',
            gatewayResponse: ['processing' => true],
            errorMessage: null,
            errorCode: null,
            redirectRoute: 'donations.processing',
            redirectParams: []
        );

        $this->assertEquals(PaymentStatusEnum::PROCESSING, $result->status);
        $this->assertEquals('TXN_789', $result->transactionId);
        $this->assertFalse($result->isSuccessful());
        $this->assertFalse($result->isFailed());
    }
}
