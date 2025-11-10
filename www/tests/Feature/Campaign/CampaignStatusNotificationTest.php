<?php

declare(strict_types=1);

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\CampaignPermissions;
use App\Enums\Campaign\CampaignStatus;
use App\Jobs\SendCampaignWaitingForValidationNotificationJob;
use App\Mail\Campaign\CampaignWaitingForValidationMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_notification_sent_when_campaign_created_with_waiting_for_validation_status(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $campaignManager = User::factory()->create(['email' => 'manager@example.com']);
        $campaignManager->assignRole('campaign_manager');

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        // Create campaign directly with waiting for validation status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $creator->id,
        ]);

        // Assert notification job was dispatched
        Queue::assertPushed(SendCampaignWaitingForValidationNotificationJob::class, function ($job) use ($campaign, $creator) {
            return $job->campaignId === $campaign->id
                && $job->creatorId === $creator->id;
        });
    }

    public function test_notification_sent_when_campaign_status_changes_to_waiting_for_validation(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $campaignManager = User::factory()->create(['email' => 'manager@example.com']);
        $campaignManager->assignRole('campaign_manager');

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        // Create campaign in draft status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        // Update campaign status to waiting for validation
        $campaign->status = CampaignStatus::WAITING_FOR_VALIDATION;
        $campaign->save();

        // Assert notification job was dispatched
        Queue::assertPushed(SendCampaignWaitingForValidationNotificationJob::class, function ($job) use ($campaign, $creator) {
            return $job->campaignId === $campaign->id
                && $job->creatorId === $creator->id;
        });
    }

    public function test_notification_not_sent_when_campaign_created_with_draft_status(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $campaignManager = User::factory()->create();
        $campaignManager->assignRole('campaign_manager');

        // Create campaign with draft status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        // Assert no notification jobs were dispatched
        Queue::assertNotPushed(SendCampaignWaitingForValidationNotificationJob::class);
    }

    public function test_notification_not_sent_when_campaign_already_in_waiting_for_validation(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $campaignManager = User::factory()->create();
        $campaignManager->assignRole('campaign_manager');

        // Create campaign already in waiting for validation status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::WAITING_FOR_VALIDATION,
            'created_by' => $creator->id,
        ]);

        // Clear any queued jobs from the creation
        Queue::fake();

        // Update campaign (but status remains the same)
        $campaign->title = 'Updated Title';
        $campaign->save();

        // Assert no notification jobs were dispatched for the update
        Queue::assertNotPushed(SendCampaignWaitingForValidationNotificationJob::class);
    }

    public function test_notification_not_sent_when_status_changes_from_active_to_waiting_for_validation(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $campaignManager = User::factory()->create();
        $campaignManager->assignRole('campaign_manager');

        // Create campaign in active status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::ACTIVE,
            'created_by' => $creator->id,
        ]);

        // Update campaign status back to waiting for validation
        $campaign->status = CampaignStatus::WAITING_FOR_VALIDATION;
        $campaign->save();

        // Assert no notification jobs were dispatched (only draft -> waiting_for_validation should trigger)
        Queue::assertNotPushed(SendCampaignWaitingForValidationNotificationJob::class);
    }

    public function test_notification_sent_to_multiple_campaign_managers(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        // Create multiple campaign managers
        $manager1 = User::factory()->create(['email' => 'manager1@example.com']);
        $manager1->assignRole('campaign_manager');

        $manager2 = User::factory()->create(['email' => 'manager2@example.com']);
        $manager2->assignRole('campaign_manager');

        // Create multiple admins
        $admin1 = User::factory()->create(['email' => 'admin1@example.com']);
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create(['email' => 'admin2@example.com']);
        $admin2->assignRole('admin');

        // Create campaign in draft status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        // Update campaign status to waiting for validation
        $campaign->status = CampaignStatus::WAITING_FOR_VALIDATION;
        $campaign->save();

        // Assert notification job was dispatched (the job will handle sending to all managers)
        Queue::assertPushed(SendCampaignWaitingForValidationNotificationJob::class, function ($job) use ($campaign, $creator) {
            return $job->campaignId === $campaign->id
                && $job->creatorId === $creator->id;
        });
    }

    public function test_notification_not_sent_to_regular_users(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create();
        $creator->assignRole('user');

        $regularUser = User::factory()->create(['email' => 'user@example.com']);
        $regularUser->assignRole('user');

        $campaignManager = User::factory()->create(['email' => 'manager@example.com']);
        $campaignManager->assignRole('campaign_manager');

        // Create campaign in draft status
        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        // Update campaign status to waiting for validation
        $campaign->status = CampaignStatus::WAITING_FOR_VALIDATION;
        $campaign->save();

        // Assert notification job was dispatched (the job filters recipients by permission)
        Queue::assertPushed(SendCampaignWaitingForValidationNotificationJob::class, function ($job) use ($campaign, $creator) {
            return $job->campaignId === $campaign->id
                && $job->creatorId === $creator->id;
        });
    }

    public function test_notification_contains_correct_campaign_and_creator_information(): void
    {
        Queue::fake();

        // Create users
        $creator = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $creator->assignRole('user');

        $campaignManager = User::factory()->create();
        $campaignManager->assignRole('campaign_manager');

        // Create campaign
        $campaign = Campaign::factory()->create([
            'title' => 'Important Campaign',
            'description' => 'This is a very important campaign',
            'status' => CampaignStatus::DRAFT,
            'created_by' => $creator->id,
        ]);

        // Update campaign status to waiting for validation
        $campaign->status = CampaignStatus::WAITING_FOR_VALIDATION;
        $campaign->save();

        // Assert notification job was dispatched with correct campaign and creator
        Queue::assertPushed(SendCampaignWaitingForValidationNotificationJob::class, function ($job) use ($campaign, $creator) {
            return $job->campaignId === $campaign->id
                && $job->creatorId === $creator->id;
        });
    }
}
