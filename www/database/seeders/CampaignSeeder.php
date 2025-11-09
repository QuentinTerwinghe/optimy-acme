<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Category;
use App\Models\Campaign\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'CHF'];

        // Get all user UUIDs for foreign key references
        $userIds = \App\Models\Auth\User::pluck('id')->toArray();

        // Create categories
        $categories = [
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'Campaigns focused on learning, schools, and educational resources',
                'is_active' => true,
            ],
            [
                'name' => 'Natural Disaster',
                'slug' => 'natural-disaster',
                'description' => 'Emergency relief and rebuilding efforts after natural disasters',
                'is_active' => true,
            ],
            [
                'name' => 'Nature',
                'slug' => 'nature',
                'description' => 'Environmental conservation, wildlife protection, and sustainability',
                'is_active' => true,
            ],
            [
                'name' => 'Homeless',
                'slug' => 'homeless',
                'description' => 'Supporting homeless individuals and families with shelter and resources',
                'is_active' => true,
            ],
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $createdCategories[$categoryData['slug']] = Category::create($categoryData);
        }

        // Create tags
        $tagNames = [
            'Emergency',
            'Community',
            'Children',
            'Environment',
            'Health',
            'Technology',
            'Animals',
            'Arts',
            'Empowerment',
            'Infrastructure',
            'Sustainability',
            'Relief',
        ];

        $createdTags = [];
        foreach ($tagNames as $tagName) {
            $createdTags[Str::slug($tagName)] = Tag::create([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
                'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)), // Random hex color
            ]);
        }

        // Define campaigns with their categories and tags
        $campaigns = [
            [
                'title' => 'Clean Water for Every Village',
                'description' => 'Help us bring clean and safe drinking water to rural communities struggling with access to basic sanitation.',
                'category' => 'homeless',
                'tags' => ['community', 'health', 'infrastructure'],
            ],
            [
                'title' => 'Save the Rainforest, One Tree at a Time',
                'description' => 'Join our effort to replant deforested areas and protect endangered wildlife in the Amazon rainforest.',
                'category' => 'nature',
                'tags' => ['environment', 'sustainability', 'animals'],
            ],
            [
                'title' => 'Tech for Tomorrow\'s Classrooms',
                'description' => 'Support our mission to provide underprivileged schools with modern computers and digital learning tools.',
                'category' => 'education',
                'tags' => ['technology', 'children'],
            ],
            [
                'title' => 'Warm Homes for the Winter',
                'description' => 'Your donation helps families in need keep their homes heated during the harsh winter months.',
                'category' => 'homeless',
                'tags' => ['community', 'relief'],
            ],
            [
                'title' => 'Feeding Hope: Meals for Children',
                'description' => 'Help us deliver nutritious meals to children who go to bed hungry every night.',
                'category' => 'homeless',
                'tags' => ['children', 'health', 'community'],
            ],
            [
                'title' => 'Emergency Relief for Earthquake Victims',
                'description' => 'We\'re raising funds to provide immediate shelter, food, and medical aid to families affected by the recent earthquake.',
                'category' => 'natural-disaster',
                'tags' => ['emergency', 'relief', 'health'],
            ],
            [
                'title' => 'Protect Our Oceans',
                'description' => 'Support cleanup initiatives to remove plastic waste from our oceans and save marine life.',
                'category' => 'nature',
                'tags' => ['environment', 'sustainability', 'animals'],
            ],
            [
                'title' => 'Books for Every Child',
                'description' => 'A small donation can put a book in the hands of a child and spark a lifelong love of learning.',
                'category' => 'education',
                'tags' => ['children', 'community'],
            ],
            [
                'title' => 'Community Garden Revival',
                'description' => 'Help us rebuild local community gardens that bring neighbors together and promote healthy living.',
                'category' => 'nature',
                'tags' => ['community', 'health', 'sustainability'],
            ],
            [
                'title' => 'Solar Lights for Rural Africa',
                'description' => 'Bring clean, renewable energy to villages that have no access to electricity.',
                'category' => 'nature',
                'tags' => ['technology', 'sustainability', 'infrastructure'],
            ],
            [
                'title' => 'Save the Stray Animals',
                'description' => 'Your support funds food, shelter, and medical care for abandoned cats and dogs.',
                'category' => 'nature',
                'tags' => ['animals', 'community'],
            ],
            [
                'title' => 'Artists Rising Fund',
                'description' => 'Help emerging artists showcase their work and access creative opportunities they could never afford alone.',
                'category' => 'education',
                'tags' => ['arts', 'empowerment', 'community'],
            ],
            [
                'title' => 'Mental Health Awareness Campaign',
                'description' => 'Join us in spreading awareness, support, and access to mental health resources for those in need.',
                'category' => 'homeless',
                'tags' => ['health', 'community'],
            ],
            [
                'title' => 'Rebuild the Old Town Library',
                'description' => 'Help restore a historic community landmark and turn it into a modern learning space.',
                'category' => 'education',
                'tags' => ['infrastructure', 'community'],
            ],
            [
                'title' => 'Support Women Entrepreneurs',
                'description' => 'Your donation helps provide training and small loans to women launching their own businesses.',
                'category' => 'education',
                'tags' => ['empowerment', 'community'],
            ],
            [
                'title' => 'Music for Healing',
                'description' => 'We\'re funding music therapy programs for hospitals and care centers around the world.',
                'category' => 'education',
                'tags' => ['health', 'arts'],
            ],
            [
                'title' => 'Plant a Million Wildflowers',
                'description' => 'Be part of a nationwide project to restore natural habitats and attract pollinators.',
                'category' => 'nature',
                'tags' => ['environment', 'sustainability'],
            ],
            [
                'title' => 'Rebuild After the Storm',
                'description' => 'Support families rebuilding their homes and lives after devastating hurricanes.',
                'category' => 'natural-disaster',
                'tags' => ['emergency', 'relief', 'infrastructure'],
            ],
            [
                'title' => 'Scholarships for Bright Minds',
                'description' => 'Give talented students from low-income families the chance to pursue higher education.',
                'category' => 'education',
                'tags' => ['children', 'empowerment'],
            ],
            [
                'title' => 'Save the Mountain Trails',
                'description' => 'We\'re raising funds to maintain hiking trails, protect wildlife, and preserve natural landscapes for future generations.',
                'category' => 'nature',
                'tags' => ['environment', 'infrastructure', 'sustainability'],
            ],
        ];

        $campaignIndex = 0;

        // Create 10 active campaigns
        for ($i = 0; $i < 10; $i++) {
            $campaignData = $campaigns[$campaignIndex];
            $campaign = Campaign::create([
                'title' => $campaignData['title'],
                'description' => $campaignData['description'],
                'goal_amount' => fake()->numberBetween(5000, 50000),
                'current_amount' => $i < 5 ? 0 : fake()->numberBetween(100, 10000), // First 5 with 0, others with random amount
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(10, 60)), // Started in the past
                'end_date' => now()->addDays(fake()->numberBetween(30, 90)), // Ends in the future
                'status' => CampaignStatus::ACTIVE,
                'category_id' => $createdCategories[$campaignData['category']]->id,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);

            // Attach tags
            $tagIds = collect($campaignData['tags'])->map(fn($tagSlug) => $createdTags[$tagSlug]->id)->toArray();
            $campaign->tags()->sync($tagIds);

            $campaignIndex++;
        }

        // Create 5 completed campaigns
        for ($i = 0; $i < 5; $i++) {
            $campaignData = $campaigns[$campaignIndex];
            $goalAmount = fake()->numberBetween(5000, 30000);
            $campaign = Campaign::create([
                'title' => $campaignData['title'],
                'description' => $campaignData['description'],
                'goal_amount' => $goalAmount,
                'current_amount' => fake()->numberBetween($goalAmount * 0.8, $goalAmount * 1.2), // 80% to 120% of goal
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(90, 180)),
                'end_date' => now()->subDays(fake()->numberBetween(1, 30)), // Ended in the past
                'status' => CampaignStatus::COMPLETED,
                'category_id' => $createdCategories[$campaignData['category']]->id,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);

            // Attach tags
            $tagIds = collect($campaignData['tags'])->map(fn($tagSlug) => $createdTags[$tagSlug]->id)->toArray();
            $campaign->tags()->sync($tagIds);

            $campaignIndex++;
        }

        // Create 2 cancelled campaigns
        for ($i = 0; $i < 2; $i++) {
            $campaignData = $campaigns[$campaignIndex];
            $campaign = Campaign::create([
                'title' => $campaignData['title'],
                'description' => $campaignData['description'],
                'goal_amount' => fake()->numberBetween(5000, 25000),
                'current_amount' => fake()->numberBetween(0, 5000),
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->subDays(fake()->numberBetween(10, 60)),
                'end_date' => now()->addDays(fake()->numberBetween(10, 60)),
                'status' => CampaignStatus::CANCELLED,
                'category_id' => $createdCategories[$campaignData['category']]->id,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);

            // Attach tags
            $tagIds = collect($campaignData['tags'])->map(fn($tagSlug) => $createdTags[$tagSlug]->id)->toArray();
            $campaign->tags()->sync($tagIds);

            $campaignIndex++;
        }

        // Create 3 draft campaigns
        for ($i = 0; $i < 3; $i++) {
            $campaignData = $campaigns[$campaignIndex];
            $campaign = Campaign::create([
                'title' => $campaignData['title'],
                'description' => $campaignData['description'],
                'goal_amount' => fake()->numberBetween(5000, 40000),
                'current_amount' => 0, // Drafts should have 0
                'currency' => fake()->randomElement($currencies),
                'start_date' => now()->addDays(fake()->numberBetween(1, 30)), // Future start date
                'end_date' => now()->addDays(fake()->numberBetween(60, 120)), // Future end date
                'status' => CampaignStatus::DRAFT,
                'category_id' => $createdCategories[$campaignData['category']]->id,
                'created_by' => fake()->randomElement($userIds),
                'updated_by' => fake()->randomElement($userIds),
            ]);

            // Attach tags
            $tagIds = collect($campaignData['tags'])->map(fn($tagSlug) => $createdTags[$tagSlug]->id)->toArray();
            $campaign->tags()->sync($tagIds);

            $campaignIndex++;
        }
    }
}
