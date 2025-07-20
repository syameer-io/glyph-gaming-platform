<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPhase1Recommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:phase1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Phase 1 Steam API enhancement features';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎮 Testing Phase 1 Steam API Enhancement Features');
        $this->newLine();

        // Test 1: Gaming Preferences
        $this->info('1️⃣ Testing Gaming Preferences...');
        $user = \App\Models\User::where('username', 'csgo_pro')->first();
        if ($user) {
            $preferences = $user->gamingPreferences;
            $this->info("   ✅ User '{$user->username}' has {$preferences->count()} gaming preferences");
            foreach ($preferences as $pref) {
                $hours = $pref->getPlaytimeHours();
                $this->info("   - {$pref->game_name}: {$hours} hours ({$pref->skill_level} level)");
            }
        }

        $this->newLine();

        // Test 2: Server Tags
        $this->info('2️⃣ Testing Server Tags...');
        $servers = \App\Models\Server::with('tags')->get();
        foreach ($servers as $server) {
            $tags = $server->tags;
            $this->info("   ✅ Server '{$server->name}' has {$tags->count()} tags");
            foreach ($tags as $tag) {
                $this->info("   - {$tag->tag_type}: {$tag->tag_value}");
            }
        }

        $this->newLine();

        // Test 3: Recommendations
        $this->info('3️⃣ Testing Server Recommendations...');
        $user = \App\Models\User::where('username', 'dota_player')->first();
        if ($user) {
            $service = new \App\Services\ServerRecommendationService();
            $recommendations = $service->getRecommendationsForUser($user, 5);
            
            $this->info("   ✅ Generated {$recommendations->count()} recommendations for '{$user->username}'");
            foreach ($recommendations as $rec) {
                $score = round($rec['score'], 1);
                $reasons = implode(', ', $rec['reasons']);
                $this->info("   - {$rec['server']->name}: {$score}% match");
                $this->info("     Reasons: {$reasons}");
            }
        }

        $this->newLine();

        // Test 4: Tag Suggestions
        $this->info('4️⃣ Testing Tag Suggestions...');
        $server = \App\Models\Server::first();
        if ($server) {
            $service = new \App\Services\ServerRecommendationService();
            $suggestions = $service->suggestTagsForServer($server);
            $this->info("   ✅ Generated ".count($suggestions)." tag suggestions for '{$server->name}'");
            foreach ($suggestions as $suggestion) {
                $this->info("   - {$suggestion['type']}: {$suggestion['value']} ({$suggestion['confidence']}% confidence)");
            }
        }

        $this->newLine();
        $this->info('🎉 Phase 1 testing completed successfully!');
        
        return 0;
    }
}
