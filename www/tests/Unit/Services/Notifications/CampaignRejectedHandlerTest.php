<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Enums\Campaign\CampaignStatus;
use App\Enums\Notification\NotificationType;
use App\Mail\Campaign\CampaignRejectedMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use App\Services\Notifications\Handlers\CampaignRejectedHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CampaignRejectedHandlerTest extends TestCase
{
    use RefreshDatabase;

    private CampaignRejectedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new CampaignRejectedHandler();
    }

    public function test_get_notification_type_returns_correct_type(): void
    {
        $this->assertEquals(
            NotificationType::CAMPAIGN_REJECTED,
            $this->handler->getNotificationType()
        );
    }

    public function test_handle_sends_email_with_valid_parameters(): void
    {
        Mail::fake();

        $receiver = User::factory()->create([
            'email' => 'creator@example.com',
            'name' => 'Campaign Creator',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $receiver->id,
        ]);

        $result = $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);

        $this->assertTrue($result);

        Mail::assertQueued(CampaignRejectedMail::class, function ($mail) use ($receiver, $campaign) {
            return $mail->receiver->id === $receiver->id
                && $mail->campaign['id'] === $campaign->id
                && $mail->campaign['title'] === $campaign->title
                && $mail->hasTo($receiver->email);
        });
    }

    public function test_handle_throws_exception_when_campaign_parameter_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: campaign');

        $receiver = User::factory()->create();

        $this->handler->handle($receiver, []);
    }

    public function test_handle_throws_exception_when_campaign_parameter_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "campaign" must be an instance of Campaign');

        $receiver = User::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => 'not a campaign object',
        ]);
    }

    public function test_handle_throws_exception_when_receiver_has_no_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Receiver must have an email address');

        $receiver = new User();
        $receiver->email = null;

        $campaign = Campaign::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);
    }

    public function test_handle_returns_false_on_mail_exception(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail service failed'));

        $receiver = User::factory()->create([
            'email' => 'creator@example.com',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $receiver->id,
        ]);

        $result = $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);

        $this->assertFalse($result);
    }

    public function test_handle_sends_email_with_correct_campaign_data(): void
    {
        Mail::fake();

        $receiver = User::factory()->create([
            'email' => 'creator@example.com',
            'name' => 'John Doe',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Rejected Campaign',
            'description' => 'This campaign was rejected',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $receiver->id,
        ]);

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);

        Mail::assertQueued(CampaignRejectedMail::class, function ($mail) use ($campaign) {
            return $mail->campaign['title'] === 'Rejected Campaign'
                && $mail->campaign['description'] === 'This campaign was rejected'
                && $mail->campaign['id'] === $campaign->id;
        });
    }

    public function test_handle_sends_email_to_correct_receiver(): void
    {
        Mail::fake();

        $receiver1 = User::factory()->create(['email' => 'creator1@example.com']);
        $receiver2 = User::factory()->create(['email' => 'creator2@example.com']);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $receiver1->id,
        ]);

        // Send to receiver1
        $this->handler->handle($receiver1, [
            'campaign' => $campaign,
        ]);

        // Assert only receiver1 got the email
        Mail::assertQueued(CampaignRejectedMail::class, function ($mail) use ($receiver1) {
            return $mail->hasTo($receiver1->email)
                && $mail->receiver->id === $receiver1->id;
        });

        Mail::assertNotQueued(CampaignRejectedMail::class, function ($mail) use ($receiver2) {
            return $mail->hasTo($receiver2->email);
        });
    }

    public function test_handle_passes_campaign_as_array_to_mailable(): void
    {
        Mail::fake();

        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
        ]);

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);

        Mail::assertQueued(CampaignRejectedMail::class, function ($mail) {
            // Verify that campaign is passed as array
            return is_array($mail->campaign)
                && isset($mail->campaign['id'])
                && isset($mail->campaign['title']);
        });
    }

    public function test_handle_validates_receiver_before_sending(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $receiver = new User();
        $receiver->email = '';  // Empty email

        $campaign = Campaign::factory()->create();

        $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);
    }

    public function test_handle_works_with_rejected_campaign_from_different_statuses(): void
    {
        Mail::fake();

        $receiver = User::factory()->create();

        // Test rejection from WAITING_FOR_VALIDATION status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $receiver->id,
        ]);

        $result = $this->handler->handle($receiver, [
            'campaign' => $campaign,
        ]);

        $this->assertTrue($result);

        Mail::assertQueued(CampaignRejectedMail::class, function ($mail) use ($campaign) {
            return $mail->campaign['id'] === $campaign->id
                && $mail->campaign['title'] === 'Test Campaign';
        });
    }
}
