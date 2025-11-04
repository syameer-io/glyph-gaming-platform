<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchmakingConfiguration;
use App\Models\MatchmakingAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MatchmakingConfigurationController extends Controller
{
    /**
     * Display a listing of all matchmaking configurations
     */
    public function index()
    {
        $configurations = MatchmakingConfiguration::orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.matchmaking.configurations.index', compact('configurations'));
    }

    /**
     * Show the form for creating a new configuration
     */
    public function create()
    {
        $configuration = new MatchmakingConfiguration([
            'weights' => [
                'skill' => 0.40,
                'composition' => 0.25,
                'region' => 0.15,
                'schedule' => 0.10,
                'size' => 0.05,
                'language' => 0.05,
            ],
            'thresholds' => [
                'min_compatibility' => 50,
                'max_results' => 10,
            ],
            'settings' => [
                'enable_skill_penalty' => true,
                'skill_penalty_threshold' => 2,
                'skill_penalty_multiplier' => 0.5,
            ],
        ]);

        return view('admin.matchmaking.configurations.edit', [
            'configuration' => $configuration,
            'isNew' => true,
        ]);
    }

    /**
     * Store a newly created configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:matchmaking_configurations|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'applies_to' => 'required|string',
            'weights.skill' => 'required|numeric|min:0|max:1',
            'weights.composition' => 'required|numeric|min:0|max:1',
            'weights.region' => 'required|numeric|min:0|max:1',
            'weights.schedule' => 'required|numeric|min:0|max:1',
            'weights.size' => 'required|numeric|min:0|max:1',
            'weights.language' => 'required|numeric|min:0|max:1',
            'thresholds.min_compatibility' => 'required|numeric|min:0|max:100',
            'thresholds.max_results' => 'required|integer|min:1|max:50',
        ]);

        // Validate weights sum to 1.0
        $weightSum = array_sum($validated['weights']);
        if (abs($weightSum - 1.0) > 0.001) {
            return back()
                ->withInput()
                ->withErrors(['weights' => "Weights must sum to 1.0 (currently {$weightSum})"]);
        }

        // Set is_active default
        $validated['is_active'] = $request->has('is_active');

        $config = MatchmakingConfiguration::create($validated);

        return redirect()
            ->route('admin.matchmaking.configurations.index')
            ->with('success', 'Configuration created successfully');
    }

    /**
     * Show the form for editing a configuration
     */
    public function edit(MatchmakingConfiguration $configuration)
    {
        return view('admin.matchmaking.configurations.edit', [
            'configuration' => $configuration,
            'isNew' => false,
        ]);
    }

    /**
     * Update an existing configuration
     */
    public function update(Request $request, MatchmakingConfiguration $configuration)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|unique:matchmaking_configurations,name,' . $configuration->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'applies_to' => 'required|string',
            'weights.skill' => 'required|numeric|min:0|max:1',
            'weights.composition' => 'required|numeric|min:0|max:1',
            'weights.region' => 'required|numeric|min:0|max:1',
            'weights.schedule' => 'required|numeric|min:0|max:1',
            'weights.size' => 'required|numeric|min:0|max:1',
            'weights.language' => 'required|numeric|min:0|max:1',
            'thresholds.min_compatibility' => 'required|numeric|min:0|max:100',
            'thresholds.max_results' => 'required|integer|min:1|max:50',
        ]);

        // Validate weights sum to 1.0
        $weightSum = array_sum($validated['weights']);
        if (abs($weightSum - 1.0) > 0.001) {
            return back()
                ->withInput()
                ->withErrors(['weights' => "Weights must sum to 1.0 (currently {$weightSum})"]);
        }

        // Set is_active
        $validated['is_active'] = $request->has('is_active');

        $configuration->update($validated);

        return redirect()
            ->route('admin.matchmaking.configurations.index')
            ->with('success', 'Configuration updated successfully');
    }

    /**
     * Delete a configuration (except default)
     */
    public function destroy(MatchmakingConfiguration $configuration)
    {
        if ($configuration->name === 'default') {
            return back()->withErrors(['default' => 'Cannot delete default configuration']);
        }

        $configuration->delete();

        return redirect()
            ->route('admin.matchmaking.configurations.index')
            ->with('success', 'Configuration deleted successfully');
    }

    /**
     * Activate a configuration and deactivate others in the same scope
     */
    public function activate(MatchmakingConfiguration $configuration)
    {
        // Deactivate other configurations with same scope
        MatchmakingConfiguration::where('applies_to', $configuration->applies_to)
            ->update(['is_active' => false]);

        $configuration->update(['is_active' => true]);

        // Clear caches
        Cache::forget('matchmaking_config_' . $configuration->applies_to);
        Cache::forget('matchmaking_config_all');

        return back()->with('success', "Configuration '{$configuration->name}' activated successfully");
    }

    /**
     * Show analytics dashboard
     */
    public function analytics()
    {
        $configurations = MatchmakingConfiguration::all();
        $analytics = [];

        foreach ($configurations as $config) {
            $analytics[$config->name] = [
                'success_rate' => MatchmakingAnalytics::getSuccessRate($config->name),
                'average_breakdown' => MatchmakingAnalytics::getAverageBreakdown($config->name),
                'total_matches' => MatchmakingAnalytics::getTotalMatches($config->name),
                'avg_response_time' => MatchmakingAnalytics::getAverageResponseTime($config->name),
            ];
        }

        return view('admin.matchmaking.analytics.dashboard', compact('configurations', 'analytics'));
    }
}
