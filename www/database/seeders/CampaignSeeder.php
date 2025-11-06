<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'CHF'];

        // Get all user UUIDs for foreign key references
        $userIds = \App\Models\User::pluck('id')->toArray();

        $campaigns = [
            [
                'title' => 'Clean Water for Every Village',
                'description' => 'Help us bring clean and safe drinking water to rural communities struggling with access to basic sanitation.'
            ],
            [
                'title' => 'Save the Rainforest, One Tree at a Time',
                'description' => 'Join our effort to replant deforested areas and protect endangered wildlife in the Amazon rainforest.'
            ],
            [
                'title' => 'Tech for Tomorrow’s Classrooms',
                'description' => 'Support our mission to provide underprivileged schools with modern computers and digital learning tools.'
            ],
            [
                'title' => 'Warm Homes for the Winter',
                'description' => 'Your donation helps families in need keep their homes heated during the harsh winter months.'
            ],
            [
                'title' => 'Feeding Hope: Meals for Children',
                'description' => 'Help us deliver nutritious meals to children who go to bed hungry every night.'
            ],
            [
                'title' => 'Emergency Relief for Earthquake Victims',
                'description' => 'We’re raising funds to provide immediate shelter, food, and medical aid to families affected by the recent earthquake.'
            ],
            [
                'title' => 'Protect Our Oceans',
                'description' => 'Support cleanup initiatives to remove plastic waste from our oceans and save marine life.'
            ],
            [
                'title' => 'Books for Every Child',
                'description' => 'A small donation can put a book in the hands of a child and spark a lifelong love of learning.'
            ],
            [
                'title' => 'Community Garden Revival',
                'description' => 'Help us rebuild local community gardens that bring neighbors together and promote healthy living.'
            ],
            [
                'title' => 'Solar Lights for Rural Africa',
                'description' => 'Bring clean, renewable energy to villages that have no access to electricity.'
            ],
            [
                'title' => 'Save the Stray Animals',
                'description' => 'Your support funds food, shelter, and medical care for abandoned cats and dogs.'
            ],
            [
                'title' => 'Artists Rising Fund',
                'description' => 'Help emerging artists showcase their work and access creative opportunities they could never afford alone.'
            ],
            [
                'title' => 'Mental Health Awareness Campaign',
                'description' => 'Join us in spreading awareness, support, and access to mental health resources for those in need.'
            ],
            [
                'title' => 'Rebuild the Old Town Library',
                'description' => 'Help restore a historic community landmark and turn it into a modern learning space.'
            ],
            [
                'title' => 'Support Women Entrepreneurs',
                'description' => 'Your donation helps provide training and small loans to women launching their own businesses.'
            ],
            [
                'title' => 'Music for Healing',
                'description' => 'We’re funding music therapy programs for hospitals and care centers around the world.'
            ],
            [
                'title' => 'Plant a Million Wildflowers',
                'description' => 'Be part of a nationwide project to restore natural habitats and attract pollinators.'
            ],
            [
                'title' => 'Rebuild After the Storm',
                'description' => 'Support families rebuilding their homes and lives after devastating hurricanes.'
            ],
            [
                'title' => 'Scholarships for Bright Minds',
                'description' => 'Give talented students from low-income families the chance to pursue higher education.'
            ],
            [
                'title' => 'Save the Mountain Trails',
                'description' => 'We’re raising funds to maintain hiking trails, protect wildlife, and preserve natural landscapes for future generations.'
            ],
        ];

        $campaignIndex = 0;

        // Create 10 active campaigns
        for ($i = 0; $i < 10; $i++) {
            Campaign::create([
                'title' => $campaigns[$campaignIndex]['title'],
                'description' => $campaigns[$campaignIndex]['description'],
                'goal_amount' => fake()->numberBetween(5000, 50000),
                'current_amount' => $i < 5 ? 0 : fake()->numberBetween(100, 10000), // First 5 with 0, others with random amount
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(10, 60)), // Started in the past
                'end_date' => now()->addDays(fake()->numberBetween(30, 90)), // Ends in the future
                'status' => CampaignStatus::ACTIVE,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
            $campaignIndex++;
        }

        // Create 5 completed campaigns
        for ($i = 0; $i < 5; $i++) {
            $goalAmount = fake()->numberBetween(5000, 30000);
            Campaign::create([
                'title' => $campaigns[$campaignIndex]['title'],
                'description' => $campaigns[$campaignIndex]['description'],
                'goal_amount' => $goalAmount,
                'current_amount' => fake()->numberBetween($goalAmount * 0.8, $goalAmount * 1.2), // 80% to 120% of goal
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(90, 180)),
                'end_date' => now()->subDays(fake()->numberBetween(1, 30)), // Ended in the past
                'status' => CampaignStatus::COMPLETED,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
            $campaignIndex++;
        }

        // Create 2 cancelled campaigns
        for ($i = 0; $i < 2; $i++) {
            Campaign::create([
                'title' => $campaigns[$campaignIndex]['title'],
                'description' => $campaigns[$campaignIndex]['description'],
                'goal_amount' => fake()->numberBetween(5000, 25000),
                'current_amount' => fake()->numberBetween(0, 5000),
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(10, 60)),
                'end_date' => now()->addDays(fake()->numberBetween(10, 60)),
                'status' => CampaignStatus::CANCELLED,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
            $campaignIndex++;
        }

        // Create 3 draft campaigns
        for ($i = 0; $i < 3; $i++) {
            Campaign::create([
                'title' => $campaigns[$campaignIndex]['title'],
                'description' => $campaigns[$campaignIndex]['description'],
                'goal_amount' => fake()->numberBetween(5000, 40000),
                'current_amount' => 0, // Drafts should have 0
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->addDays(fake()->numberBetween(1, 30)), // Future start date
                'end_date' => now()->addDays(fake()->numberBetween(60, 120)), // Future end date
                'status' => CampaignStatus::DRAFT,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);
            $campaignIndex++;
        }
    }
}
