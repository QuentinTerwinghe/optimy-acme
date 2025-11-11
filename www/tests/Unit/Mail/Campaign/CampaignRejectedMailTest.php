<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Campaign;

use App\Enums\Campaign\CampaignStatus;
use App\Mail\Campaign\CampaignRejectedMail;
use App\Models\Auth\User;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignRejectedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_builds_successfully(): void
    {
        $receiver = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'description' => 'Test Description',
            'status' => CampaignStatus::REJECTED,
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        // Test envelope
        $envelope = $mailable->envelope();
        $this->assertEquals('Your Campaign was rejected: Test Campaign', $envelope->subject);

        // Test content
        $content = $mailable->content();
        $this->assertEquals('emails.campaign.rejected', $content->markdown);
    }

    public function test_mailable_contains_correct_receiver(): void
    {
        $receiver = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Campaign Title',
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $this->assertEquals($receiver->id, $mailable->receiver->id);
        $this->assertEquals($receiver->email, $mailable->receiver->email);
        $this->assertEquals($receiver->name, $mailable->receiver->name);
    }

    public function test_mailable_contains_campaign_data(): void
    {
        $receiver = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'title' => 'Rejected Campaign',
            'description' => 'This campaign needs work',
            'status' => CampaignStatus::REJECTED,
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $this->assertIsArray($mailable->campaign);
        $this->assertEquals('Rejected Campaign', $mailable->campaign['title']);
        $this->assertEquals('This campaign needs work', $mailable->campaign['description']);
        $this->assertEquals($campaign->id, $mailable->campaign['id']);
    }

    public function test_mailable_uses_correct_markdown_template(): void
    {
        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $content = $mailable->content();

        $this->assertEquals('emails.campaign.rejected', $content->markdown);
    }

    public function test_mailable_has_correct_subject_with_campaign_title(): void
    {
        $receiver = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'title' => 'My Failed Campaign',
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $envelope = $mailable->envelope();

        $this->assertEquals('Your Campaign was rejected: My Failed Campaign', $envelope->subject);
    }

    public function test_mailable_has_no_attachments(): void
    {
        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $this->assertEmpty($mailable->attachments());
    }

    public function test_mailable_is_queueable(): void
    {
        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }

    public function test_mailable_can_be_rendered(): void
    {
        $receiver = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
            'description' => 'Test Description',
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        // Test that the mailable can be rendered without errors
        $rendered = $mailable->render();

        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
    }

    public function test_mailable_passes_all_campaign_attributes(): void
    {
        $receiver = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'title' => 'Full Campaign',
            'description' => 'Full Description',
            'goal_amount' => 50000,
            'status' => CampaignStatus::REJECTED,
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        // Verify all important campaign data is available
        $this->assertArrayHasKey('id', $mailable->campaign);
        $this->assertArrayHasKey('title', $mailable->campaign);
        $this->assertArrayHasKey('description', $mailable->campaign);
        $this->assertArrayHasKey('goal_amount', $mailable->campaign);
        $this->assertArrayHasKey('status', $mailable->campaign);
    }

    public function test_mailable_receiver_is_readonly(): void
    {
        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        // Test that receiver property is readonly (will cause error if we try to modify)
        $reflectionProperty = new \ReflectionProperty($mailable, 'receiver');
        $this->assertTrue($reflectionProperty->isReadOnly());
    }

    public function test_mailable_campaign_is_readonly(): void
    {
        $receiver = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        // Test that campaign property is readonly
        $reflectionProperty = new \ReflectionProperty($mailable, 'campaign');
        $this->assertTrue($reflectionProperty->isReadOnly());
    }

    public function test_mailable_subject_differs_from_validated_mail(): void
    {
        $receiver = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'title' => 'Test Campaign',
        ]);

        $mailable = new CampaignRejectedMail(
            receiver: $receiver,
            campaign: $campaign->toArray()
        );

        $envelope = $mailable->envelope();

        // Verify it says "rejected" not "validated"
        $this->assertStringContainsString('rejected', $envelope->subject);
        $this->assertStringNotContainsString('validated', $envelope->subject);
    }
}
