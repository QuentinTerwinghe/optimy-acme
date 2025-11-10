<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Notification\NotificationType;
use App\Mail\Campaign\CampaignWaitingForValidationMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Services\Notifications\Handlers\CampaignWaitingForValidationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CampaignWaitingForValidationHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CampaignWaitingForValidationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new CampaignWaitingForValidationHandler();
    }

    public function test_get_notification_type_returns_correct_type(): void
    {
        $this->assertEquals(
            NotificationType::CAMPAIGN_WAITING_FOR_VALIDATION,
            $this->handler->getNotificationType()
        );
    }

    public function test_handle_sends_email_with_valid_parameters(): void
    {
        Mail::fake();

        $receiver = User::factory()->create([
            'email' => 'manager@example.com',
            'name' => 'Campaign Manager',
        ]);

        $creator = User::factory()->create([
            'email' => 'creator@example.com',
            'name' => 'Campaign Creator',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $creator->id,
        ]);

        $result = $this->handler->handle($receiver, [
            'campaign' => $campaign,
            'creator' => $creator,
        ]);

        $this->assertTrue($result);

        Mail::assertQueued(CampaignWaitingForValidationMail::class, function ($mail) use ($receiver, $campaign, $creator) {
            return $mail->receiver->id === $receiver->id
                && $mail->campaign['id'] === $campaign->id
                && $mail->creator->id === $creator->id
                && $mail->hasTo($receiver->email);
        });
    }

    public function test_handle_throws_exception_when_campaign_parameter_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: campaign');

        $receiver = User::factory()->create();
        $creator = User::factory()->create();

        $this->handler->handle($receiver, [
            'creator' => $creator,
        ]);
    }

    public function test_handle_throws_exception_when_creator_parameter_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: creator');

        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);
    }

    public function test_handle_throws_exception_when_campaign_parameter_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "campaign" must be an instance of Campaign');

        $receiver = User::factory()->create();
        $creator = User::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => 'not a campaign object',
            'creator' => $creator,
        ]);
    }

    public function test_handle_throws_exception_when_creator_parameter_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "creator" must be an instance of User');

        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
            'creator' => 'not a user object',
        ]);
    }

    public function test_handle_throws_exception_when_receiver_has_no_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Receiver must have an email address');

        $receiver = new User();
        $receiver->email = null;

        $creator = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
            'creator' => $creator,
        ]);
    }
}
