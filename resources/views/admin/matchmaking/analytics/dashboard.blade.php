@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Matchmaking Analytics Dashboard</h1>
        <a href="{{ route('admin.matchmaking.configurations.index') }}"
           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Configurations
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($configurations as $config)
            @php
                $stats = $analytics[$config->name] ?? [];
                $successRate = $stats['success_rate'] ?? 0;
                $totalMatches = $stats['total_matches'] ?? 0;
                $avgResponseTime = $stats['avg_response_time'] ?? 0;
            @endphp

            <div class="bg-white shadow-md rounded-lg p-6 border-l-4 {{ $config->is_active ? 'border-green-500' : 'border-gray-300' }}">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $config->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $config->scope_description }}</p>
                    </div>
                    @if($config->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Active
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Success Rate</span>
                            <span class="text-lg font-bold {{ $successRate >= 60 ? 'text-green-600' : ($successRate >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($successRate, 1) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $successRate >= 60 ? 'bg-green-500' : ($successRate >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ $successRate }}%"></div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-sm text-gray-600">Total Matches</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($totalMatches) }}</span>
                    </div>

                    @if($avgResponseTime > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Avg Response Time</span>
                            <span class="text-sm font-medium text-gray-700">{{ number_format($avgResponseTime / 60, 1) }}m</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Detailed Breakdown --}}
    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Score Breakdown by Configuration</h2>

        @foreach($configurations as $config)
            @php
                $stats = $analytics[$config->name] ?? [];
                $breakdown = $stats['average_breakdown'] ?? [];
                $totalMatches = $stats['total_matches'] ?? 0;
            @endphp

            @if($totalMatches > 0)
                <div class="mb-6 pb-6 border-b last:border-b-0">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold">{{ $config->name }}</h3>
                        <span class="text-sm text-gray-500">{{ $totalMatches }} matches</span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        @foreach(['skill', 'composition', 'region', 'schedule', 'size', 'language'] as $criterion)
                            @php
                                $score = $breakdown[$criterion] ?? 0;
                                $weight = ($config->weights[$criterion] ?? 0) * 100;
                            @endphp
                            <div class="text-center">
                                <div class="text-sm text-gray-600 mb-1">{{ ucfirst($criterion) }}</div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($score, 1) }}</div>
                                <div class="text-xs text-gray-500">{{ number_format($weight) }}% weight</div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                    <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $score }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if(collect($analytics)->every(fn($stat) => ($stat['total_matches'] ?? 0) === 0))
            <div class="text-center py-8 text-gray-500">
                <p class="text-lg mb-2">No analytics data available yet</p>
                <p class="text-sm">Analytics will appear once matchmaking requests are processed</p>
            </div>
        @endif
    </div>

    {{-- Configuration Comparison --}}
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">Configuration Comparison</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Configuration</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Matches</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Response</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($configurations as $config)
                        @php
                            $stats = $analytics[$config->name] ?? [];
                            $successRate = $stats['success_rate'] ?? 0;
                            $totalMatches = $stats['total_matches'] ?? 0;
                            $avgResponseTime = $stats['avg_response_time'] ?? 0;
                        @endphp
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $config->name }}</div>
                                <div class="text-sm text-gray-500">{{ $config->scope_description }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-900">{{ number_format($totalMatches) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-medium {{ $successRate >= 60 ? 'text-green-600' : ($successRate >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($successRate, 1) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm text-gray-700">
                                    {{ $avgResponseTime > 0 ? number_format($avgResponseTime / 60, 1) . 'm' : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($config->is_active)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
