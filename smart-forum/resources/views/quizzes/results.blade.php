<x-whatsapp-layout>
    <x-slot name="sidebar">
        @foreach(auth()->user()->groups as $g)
            <a href="{{ route('groups.topics', $g) }}" class="whatsapp-group-item">
                <div class="whatsapp-avatar whatsapp-avatar-sm" style="background: {{ $g->color ?? '#075E54' }};">
                    {{ $g->name[0] }}
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="font-medium text-sm">{{ $g->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $g->topics->count() }} topics</div>
                </div>
            </a>
        @endforeach
    </x-slot>

    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('groups.topics', $group) }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Quiz Results</span>
    </div>

    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-3xl mx-auto">

            @if($isCreator ?? false)
                <!-- ===== LECTURER/ADMIN VIEW ===== -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-xl font-semibold">Overall Results: {{ $quiz->title }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div>
                            <div class="text-sm text-gray-500">Total Submissions</div>
                            <div class="text-2xl font-bold text-[#075E54]">{{ $allSubmissions->count() }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Average Score</div>
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($average, 2) }}%</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Platform Average</div>
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($platformAverage, 2) }}%</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Max Score</div>
                            <div class="text-2xl font-bold text-gray-700">{{ $maxScore }}</div>
                        </div>
                    </div>

                    @if($allSubmissions->count() > 0)
                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-700 mb-2">Student Scores</h4>
                            <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left">Student</th>
                                            <th class="px-4 py-2 text-left">Score</th>
                                            <th class="px-4 py-2 text-left">Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allSubmissions as $sub)
                                            <tr class="border-t border-gray-100">
                                                <td class="px-4 py-2">{{ $sub->user->name }}</td>
                                                <td class="px-4 py-2">{{ number_format($sub->score ?? 0, 2) }}%</td>
                                                <td class="px-4 py-2">{{ $sub->submitted_at->diffForHumans() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

            @else
                <!-- ===== STUDENT VIEW ===== -->
                @if($submission && $submission->is_auto_submitted)
                    <div class="mb-4 p-4 bg-yellow-100 text-yellow-700 rounded-lg">
                        ⏱️ This quiz was auto-submitted when time expired.
                    </div>
                @endif

                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                    <h2 class="text-xl font-semibold">{{ $quiz->title }}</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div>
                            <div class="text-sm text-gray-500">Your Score</div>
                            <div class="text-2xl font-bold text-[#075E54]">{{ number_format($submission->score ?? 0, 2) }}%</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Class Average</div>
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($average, 2) }}%</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Platform Average</div>
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($platformAverage, 2) }}%</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Max Score</div>
                            <div class="text-2xl font-bold text-gray-700">{{ $maxScore }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ===== HISTOGRAM (always shown) ===== -->
            @if(count($histogram) > 0)
                @php
                    $maxCount = max(array_column($histogram, 'count')) ?: 1;
                @endphp
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="font-semibold text-gray-700 mb-3">Score Distribution</h3>
                    @if($maxCount > 0)
                        <div class="flex items-end h-48 gap-2">
                            @foreach($histogram as $bin)
                                @php
                                    $height = ($bin['count'] / $maxCount) * 100;
                                @endphp
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="w-full bg-[#075E54] rounded-t" style="height: {{ max($height, 4) }}%; min-height: 4px;"></div>
                                    <span class="text-xs text-gray-500 mt-1">{{ $bin['range'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $bin['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-sm">No submissions yet.</p>
                    @endif
                </div>
            @endif

            <div class="mt-6 text-center">
                <a href="{{ route('groups.topics', $group) }}" class="text-sm text-[#075E54] hover:underline">
                    ← Back to topics
                </a>
            </div>
        </div>
    </div>
</x-whatsapp-layout>