<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Server;

/**
 * Clear Permission Caches Command
 *
 * Clears cached permission data for users within servers.
 * Useful for maintenance, debugging, or after bulk permission changes.
 *
 * Usage:
 *   php artisan permissions:clear-cache              # Clear all servers
 *   php artisan permissions:clear-cache --server=1   # Clear specific server
 *
 * @package App\Console\Commands
 * @since Phase 6 - Role Permissions System
 */
class ClearPermissionCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:clear-cache {--server= : Clear for specific server ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all permission caches';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $serverId = $this->option('server');

        if ($serverId) {
            $server = Server::find($serverId);

            if (!$server) {
                $this->error("Server with ID {$serverId} not found.");
                return Command::FAILURE;
            }

            $count = $this->clearServerCache($server);
            $this->info("Permission cache cleared for server '{$server->name}' (ID: {$serverId}).");
            $this->info("Cleared cache for {$count} user(s).");
        } else {
            $totalUsers = 0;
            $serverCount = 0;

            Server::chunk(100, function ($servers) use (&$totalUsers, &$serverCount) {
                foreach ($servers as $server) {
                    $count = $this->clearServerCache($server);
                    $totalUsers += $count;
                    $serverCount++;
                }
            });

            $this->info("Permission cache cleared for {$serverCount} server(s).");
            $this->info("Cleared cache for {$totalUsers} user-server combination(s).");
        }

        return Command::SUCCESS;
    }

    /**
     * Clear permission cache for all members of a server.
     *
     * @param Server $server
     * @return int Number of cache entries cleared
     */
    protected function clearServerCache(Server $server): int
    {
        $memberIds = $server->members()->pluck('users.id');
        $count = 0;

        foreach ($memberIds as $userId) {
            $cacheKey = "user.{$userId}.server.{$server->id}.permissions";
            if (Cache::forget($cacheKey)) {
                $count++;
            }
        }

        return $count;
    }
}
