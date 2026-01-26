@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6">
        {{ $isNew ? 'Create' : 'Edit' }} Matchmaking Configuration
    </h1>

    <form action="{{ $isNew ? route('admin.matchmaking.configurations.store') : route('admin.matchmaking.configurations.update', $configuration) }}"
          method="POST"
          class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        @csrf
        @if(!$isNew) @method('PUT') @endif

        {{-- Name & Description --}}
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                Configuration Name
            </label>
            <input type="text"
                   name="name"
                   id="name"
                   value="{{ old('name', $configuration->name) }}"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('name') border-red-500 @enderror"
                   required>
            @error('name')
                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                Description
            </label>
            <textarea name="description"
                      id="description"
                      rows="3"
                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('description', $configuration->description) }}</textarea>
        </div>

        {{-- Applies To --}}
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="applies_to">
                Applies To
            </label>
            <select name="applies_to"
                    id="applies_to"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700">
                <option value="all" {{ old('applies_to', $configuration->applies_to) === 'all' ? 'selected' : '' }}>
                    All Games & Servers
                </option>
                <option value="game:730" {{ old('applies_to', $configuration->applies_to) === 'game:730' ? 'selected' : '' }}>
                    Counter-Strike 2 Only
                </option>
                <option value="game:548430" {{ old('applies_to', $configuration->applies_to) === 'game:548430' ? 'selected' : '' }}>
                    Deep Rock Galactic Only
                </option>
                <option value="game:493520" {{ old('applies_to', $configuration->applies_to) === 'game:493520' ? 'selected' : '' }}>
                    GTFO Only
                </option>
            </select>
        </div>

        {{-- Weights Section --}}
        <div class="mb-6 border-t pt-6">
            <h2 class="text-xl font-bold mb-4">Criterion Weights (Must sum to 1.0)</h2>

            <div id="weight-sum-indicator" class="mb-4 p-3 rounded text-center font-bold">
                Current Sum: <span id="weight-sum">1.00</span>
            </div>

            @php
                $weights = old('weights', $configuration->weights ?? []);
                $criteriaLabels = [
                    'skill' => ['Skill Level', 'Most important factor for match quality'],
                    'composition' => ['Team Composition', 'Role matching and team needs'],
                    'region' => ['Region', 'Geographic proximity and latency'],
                    'schedule' => ['Schedule', 'Activity time overlap'],
                    'size' => ['Team Size', 'Team fill percentage'],
                    'language' => ['Language', 'Communication compatibility'],
                ];
            @endphp

            @foreach($criteriaLabels as $criterion => [$label, $description])
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        {{ $label }}
                        <span class="text-gray-500 text-xs font-normal">({{ $description }})</span>
                    </label>

                    <div class="flex items-center space-x-4">
                        <input type="range"
                               class="weight-slider flex-1"
                               min="0"
                               max="1"
                               step="0.01"
                               value="{{ $weights[$criterion] ?? 0 }}"
                               data-criterion="{{ $criterion }}">

                        <input type="number"
                               name="weights[{{ $criterion }}]"
                               class="weight-input shadow border rounded w-24 py-2 px-3 text-gray-700"
                               min="0"
                               max="1"
                               step="0.01"
                               value="{{ $weights[$criterion] ?? 0 }}"
                               data-criterion="{{ $criterion }}">

                        <span class="weight-percentage text-lg font-bold w-16 text-right">
                            {{ round(($weights[$criterion] ?? 0) * 100) }}%
                        </span>
                    </div>
                </div>
            @endforeach

            @error('weights')
                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Thresholds Section --}}
        <div class="mb-6 border-t pt-6">
            <h2 class="text-xl font-bold mb-4">Thresholds</h2>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Minimum Compatibility (%)
                </label>
                <input type="number"
                       name="thresholds[min_compatibility]"
                       class="shadow border rounded w-full py-2 px-3 text-gray-700"
                       min="0"
                       max="100"
                       value="{{ old('thresholds.min_compatibility', $configuration->thresholds['min_compatibility'] ?? 50) }}">
                <p class="text-gray-500 text-xs mt-1">Teams with compatibility below this will be filtered out</p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Max Results
                </label>
                <input type="number"
                       name="thresholds[max_results]"
                       class="shadow border rounded w-full py-2 px-3 text-gray-700"
                       min="1"
                       max="50"
                       value="{{ old('thresholds.max_results', $configuration->thresholds['max_results'] ?? 10) }}">
                <p class="text-gray-500 text-xs mt-1">Maximum number of teams to return per matchmaking request</p>
            </div>
        </div>

        {{-- Active Toggle --}}
        <div class="mb-6 border-t pt-6">
            <label class="flex items-center">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       {{ old('is_active', $configuration->is_active) ? 'checked' : '' }}
                       class="mr-2">
                <span class="text-gray-700 text-sm font-bold">Active</span>
            </label>
            <p class="text-gray-500 text-xs mt-1">Only one configuration per scope can be active at a time</p>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <button type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                {{ $isNew ? 'Create' : 'Update' }} Configuration
            </button>

            <a href="{{ route('admin.matchmaking.configurations.index') }}"
               class="text-gray-600 hover:text-gray-800">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sliders = document.querySelectorAll('.weight-slider');
    const inputs = document.querySelectorAll('.weight-input');
    const sumIndicator = document.getElementById('weight-sum');
    const sumContainer = document.getElementById('weight-sum-indicator');

    function updateSum() {
        let sum = 0;
        inputs.forEach(input => {
            sum += parseFloat(input.value) || 0;
        });

        sum = Math.round(sum * 100) / 100;
        sumIndicator.textContent = sum.toFixed(2);

        // Color indicator based on validity
        if (Math.abs(sum - 1.0) < 0.001) {
            sumContainer.className = 'mb-4 p-3 rounded text-center font-bold bg-green-100 text-green-800';
        } else {
            sumContainer.className = 'mb-4 p-3 rounded text-center font-bold bg-red-100 text-red-800';
        }
    }

    // Sync sliders and inputs
    sliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const criterion = this.dataset.criterion;
            const input = document.querySelector(`input.weight-input[data-criterion="${criterion}"]`);
            const percentage = this.parentElement.querySelector('.weight-percentage');

            input.value = this.value;
            percentage.textContent = Math.round(this.value * 100) + '%';

            updateSum();
        });
    });

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const criterion = this.dataset.criterion;
            const slider = document.querySelector(`input.weight-slider[data-criterion="${criterion}"]`);
            const percentage = this.parentElement.querySelector('.weight-percentage');

            slider.value = this.value;
            percentage.textContent = Math.round(this.value * 100) + '%';

            updateSum();
        });
    });

    updateSum();
});
</script>
@endsection
