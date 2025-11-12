<?php

declare(strict_types=1);

use App\Contracts\Campaign\CampaignWriteServiceInterface;
use App\Jobs\Campaign\UpdateCampaignAmountJob;
use App\Models\Campaign\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('UpdateCampaignAmountJob', function () {
    test('can be instantiated with campaign ID', function () {
        $campaignId = 'test-campaign-id';
        $job = new UpdateCampaignAmountJob($campaignId);

        expect($job->campaignId)->toBe($campaignId);
    });

    test('implements ShouldQueue interface', function () {
        $job = new UpdateCampaignAmountJob('test-id');

        expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    test('has correct retry configuration', function () {
        $job = new UpdateCampaignAmountJob('test-id');

        expect($job->tries)->toBe(3)
            ->and($job->backoff)->toBe(5);
    });

    test('calls recalculateTotalAmount on campaign write service', function () {
        $campaign = Campaign::factory()->create();

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldReceive('recalculateTotalAmount')
            ->once()
            ->with(Mockery::on(function ($arg) use ($campaign) {
                return $arg instanceof Campaign && $arg->id === $campaign->id;
            }))
            ->andReturn(true);

        app()->instance(CampaignWriteServiceInterface::class, $mockService);

        $job = new UpdateCampaignAmountJob($campaign->id);
        $job->handle($mockService);
    });

    test('logs info when processing starts', function () {
        $campaign = Campaign::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($campaign) {
                return $message === 'Processing campaign amount update job'
                    && $context['campaign_id'] === $campaign->id;
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Campaign amount update job completed successfully';
            });

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldReceive('recalculateTotalAmount')->andReturn(true);

        $job = new UpdateCampaignAmountJob($campaign->id);
        $job->handle($mockService);
    });

    test('logs success when job completes', function () {
        $campaign = Campaign::factory()->create();

        Log::shouldReceive('info')->twice();

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldReceive('recalculateTotalAmount')
            ->andReturn(true);

        $job = new UpdateCampaignAmountJob($campaign->id);
        $job->handle($mockService);
    });

    test('logs error and returns early when campaign not found', function () {
        $invalidId = 'non-existent-campaign-id';

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return $message === 'Processing campaign amount update job';
            });

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($invalidId) {
                return $message === 'Campaign not found for amount update job'
                    && $context['campaign_id'] === $invalidId;
            });

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldNotReceive('recalculateTotalAmount');

        $job = new UpdateCampaignAmountJob($invalidId);
        $job->handle($mockService);
    });

    test('throws exception when recalculation fails', function () {
        $campaign = Campaign::factory()->create();

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldReceive('recalculateTotalAmount')
            ->andReturn(false);

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        $job = new UpdateCampaignAmountJob($campaign->id);

        expect(fn () => $job->handle($mockService))->toThrow(\Exception::class);
    });

    test('logs error and rethrows exception on failure', function () {
        $campaign = Campaign::factory()->create();
        $errorMessage = 'Database connection failed';

        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($campaign, $errorMessage) {
                return $message === 'Failed to process campaign amount update job'
                    && $context['campaign_id'] === $campaign->id
                    && $context['error'] === $errorMessage;
            });

        $mockService = Mockery::mock(CampaignWriteServiceInterface::class);
        $mockService->shouldReceive('recalculateTotalAmount')
            ->andThrow(new \Exception($errorMessage));

        $job = new UpdateCampaignAmountJob($campaign->id);

        expect(fn () => $job->handle($mockService))->toThrow(\Exception::class, $errorMessage);
    });

    test('failed method logs permanent failure', function () {
        $campaignId = 'test-campaign-id';
        $exception = new \Exception('Max retries exceeded');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($campaignId) {
                return $message === 'Campaign amount update job failed permanently'
                    && $context['campaign_id'] === $campaignId;
            });

        $job = new UpdateCampaignAmountJob($campaignId);
        $job->failed($exception);
    });

    test('can be dispatched to queue', function () {
        Queue::fake();

        $campaignId = 'test-campaign-id';
        UpdateCampaignAmountJob::dispatch($campaignId);

        Queue::assertPushed(UpdateCampaignAmountJob::class, function ($job) use ($campaignId) {
            return $job->campaignId === $campaignId;
        });
    });

    test('uses correct queue traits', function () {
        $job = new UpdateCampaignAmountJob('test-id');

        expect(class_uses_recursive($job))
            ->toContain(\Illuminate\Bus\Queueable::class)
            ->toContain(\Illuminate\Foundation\Bus\Dispatchable::class)
            ->toContain(\Illuminate\Queue\InteractsWithQueue::class)
            ->toContain(\Illuminate\Queue\SerializesModels::class);
    });
});
