<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Contracts\Notifications\NotificationServiceInterface;
use App\Enums\Campaign\CampaignStatus;
use App\Enums\Notification\NotificationType;
use App\Jobs\SendCampaignStatusFlowNotificationJob;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendCampaignStatusFlowNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_sends_validated_notification_for_active_campaign(): void
    {
        // Create user and campaign
        $user = User::factory()->create(['email' => 'creator@example.com']);
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $user->id,
        ]);

        // Mock notification service
        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $notificationService->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function ($receiver) use ($user) {
                    return $receiver->id === $user->id;
                }),
                NotificationType::CAMPAIGN_VALIDATED,
                $this->callback(function ($params) use ($campaign) {
                    return isset($params['campaign'])
                        && $params['campaign']->id === $campaign->id;
                })
            );

        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        // Execute job
        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_sends_rejected_notification_for_rejected_campaign(): void
    {
        // Create user and campaign
        $user = User::factory()->create(['email' => 'creator@example.com']);
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::REJECTED,
            'created_by' => $user->id,
        ]);

        // Mock notification service
        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $notificationService->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function ($receiver) use ($user) {
                    return $receiver->id === $user->id;
                }),
                NotificationType::CAMPAIGN_REJECTED,
                $this->callback(function ($params) use ($campaign) {
                    return isset($params['campaign'])
                        && $params['campaign']->id === $campaign->id;
                })
            );

        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        // Execute job
        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_throws_exception_for_invalid_campaign_status(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('is not a valid status for campaign status flow');

        // Create user and campaign with DRAFT status (not valid for this job)
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::DRAFT,
            'created_by' => $user->id,
        ]);

        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        // Execute job - should throw exception
        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_handles_user_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
        ]);

        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        // Execute job with non-existent user ID
        $job = new SendCampaignStatusFlowNotificationJob(
            'non-existent-user-id',
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_handles_campaign_not_found(): void
    {
        Log::shouldReceive('info')->times(1);
        Log::shouldReceive('error')
            ->once()
            ->with('Campaign not found for notification job', [
                'campaign_id' => 'non-existent-campaign-id',
            ]);

        $user = User::factory()->create();

        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $notificationService->expects($this->never())->method('send');

        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        // Execute job with non-existent campaign ID
        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            'non-existent-campaign-id'
        );

        $job->handle($notificationService);
    }

    public function test_job_logs_success(): void
    {
        $user = User::factory()->create(['email' => 'creator@example.com']);
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $user->id,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Processing campaign status flow notification job', [
                'user_id' => (string) $user->id,
                'email' => $user->email,
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Campaign validation notification job completed', [
                'campaign_id' => (string) $campaign->id,
            ]);

        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $notificationService->method('send')->willReturn(true);

        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_logs_and_rethrows_exception_on_failure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Notification service failed');

        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $user->id,
        ]);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to process campaign validation notification job', \Mockery::on(function ($arg) {
                return isset($arg['campaign_id'])
                    && isset($arg['error'])
                    && isset($arg['trace']);
            }));

        $notificationService = $this->createMock(NotificationServiceInterface::class);
        $notificationService->method('send')
            ->willThrowException(new \Exception('Notification service failed'));

        $this->app->instance(NotificationServiceInterface::class, $notificationService);

        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $job->handle($notificationService);
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $user->id,
        ]);

        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(10, $job->backoff);
    }

    public function test_job_failed_method_logs_permanent_failure(): void
    {
        $campaignId = 'test-campaign-id';
        $userId = 'test-user-id';

        Log::shouldReceive('error')
            ->once()
            ->with('Campaign status flow notification job failed permanently', [
                'campaign_id' => $campaignId,
                'error' => 'Test failure',
            ]);

        $job = new SendCampaignStatusFlowNotificationJob($userId, $campaignId);
        $job->failed(new \Exception('Test failure'));
    }

    public function test_job_serialization_with_valid_ids(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $user->id,
        ]);

        $job = new SendCampaignStatusFlowNotificationJob(
            (string) $user->id,
            (string) $campaign->id
        );

        // Test that the job can be serialized and unserialized
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertEquals($job->campaignId, $unserialized->campaignId);
        $this->assertInstanceOf(SendCampaignStatusFlowNotificationJob::class, $unserialized);
    }
}
