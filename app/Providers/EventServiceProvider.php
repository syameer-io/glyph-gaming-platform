<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Goal Events
use App\Events\GoalCreated;
use App\Events\GoalCompleted;
use App\Events\GoalProgressUpdated;
use App\Events\UserJoinedGoal;
use App\Events\GoalMilestoneReached;

// Team Events
use App\Events\TeamCreated;
use App\Events\TeamMemberJoined;
use App\Events\TeamMemberLeft;

// Goal Telegram Listeners
use App\Listeners\TelegramGoalCreated;
use App\Listeners\TelegramGoalCompleted;
use App\Listeners\TelegramGoalProgressUpdated;
use App\Listeners\TelegramUserJoinedGoal;
use App\Listeners\TelegramGoalMilestoneReached;

// Team Telegram Listeners
use App\Listeners\TelegramTeamCreated;
use App\Listeners\TelegramTeamMemberJoined;
use App\Listeners\TelegramTeamMemberLeft;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        GoalCreated::class => [
            TelegramGoalCreated::class,
        ],

        GoalCompleted::class => [
            TelegramGoalCompleted::class,
        ],

        GoalProgressUpdated::class => [
            TelegramGoalProgressUpdated::class,
        ],

        UserJoinedGoal::class => [
            TelegramUserJoinedGoal::class,
        ],

        GoalMilestoneReached::class => [
            TelegramGoalMilestoneReached::class,
        ],

        // ========================================
        // Team Events → Telegram Notifications
        // ========================================
        TeamCreated::class => [
            TelegramTeamCreated::class,
        ],

        TeamMemberJoined::class => [
            TelegramTeamMemberJoined::class,
        ],

        TeamMemberLeft::class => [
            TelegramTeamMemberLeft::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}