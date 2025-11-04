@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Matchmaking Configurations</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.matchmaking.analytics.dashboard') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                View Analytics
            </a>
            <a href="{{ route('admin.matchmaking.configurations.create') }}"
               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Create New Configuration
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Scope
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Weights
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($configurations as $config)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $config->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">{{ $config->description ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $config->scope_description }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-600">
                                Skill: {{ round($config->weights['skill'] * 100) }}% |
                                Comp: {{ round($config->weights['composition'] * 100) }}% |
                                Region: {{ round($config->weights['region'] * 100) }}%
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                @if(!$config->is_active)
                                    <form action="{{ route('admin.matchmaking.configurations.activate', $config) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-green-600 hover:text-green-900">
                                            Activate
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('admin.matchmaking.configurations.edit', $config) }}"
                                   class="text-blue-600 hover:text-blue-900">
                                    Edit
                                </a>

                                @if($config->name !== 'default')
                                    <form action="{{ route('admin.matchmaking.configurations.destroy', $config) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this configuration?')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No configurations found. Create one to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
