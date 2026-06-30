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

    <!-- Header -->
    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Quizzes</span>
        @if((auth()->user()->isLecturer() || auth()->user()->isAdmin()) && isset($firstGroup) && $firstGroup)
            <a href="{{ route('quizzes.create', ['group' => $firstGroup]) }}" 
               class="ml-auto text-sm bg-[#075E54] text-white px-3 py-1 rounded hover:bg-[#128C7E] transition">
                + New Quiz
            </a>
        @endif
    </div>

    <!-- Quiz List -->
    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-3xl mx-auto">
            @if($quizStatuses->count() > 0)
                <div class="space-y-4">
                    @foreach($quizStatuses as $item)
                        @php
                            $quiz = $item['quiz'];
                            $submitted = $item['submitted'];
                            $submission = $item['submission'];
                        @endphp
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $quiz->title }}</h3>
                                    <p class="text-sm text-gray-500">
                                        {{ $quiz->group->name }} · {{ $quiz->duration }} min
                                        @if($quiz->ends_at)
                                            · Due: {{ $quiz->ends_at->format('M d, Y g:i A') }}
                                        @endif
                                    </p>
                                    @if($quiz->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ str()->limit($quiz->description, 100) }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if(auth()->user()->isAdmin() || auth()->user()->isLecturer())
                                        <span class="text-xs text-gray-500">{{ $quiz->submissions_count ?? 0 }} submissions</span>
                                        <span class="text-xs text-gray-500">Avg: {{ number_format($quiz->avg_score ?? 0, 2) }}%</span>
                                        <a href="{{ route('quizzes.results', [$quiz->group, $quiz]) }}" 
                                           class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                                            View Results
                                        </a>
                                    @else
                                        @if($submitted)
                                            <a href="{{ route('quizzes.results', [$quiz->group, $quiz]) }}" 
                                               class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                                                View Results
                                            </a>
                                        @else
                                            <a href="{{ route('quizzes.take', [$quiz->group, $quiz]) }}" 
                                               class="px-3 py-1 bg-[#075E54] text-white text-sm rounded hover:bg-[#128C7E] transition">
                                                Take Quiz
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-400 py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="font-normal text-base mt-2">
                        @if(auth()->user()->isAdmin() || auth()->user()->isLecturer())
                            You haven't created any quizzes yet.
                        @else
                            No quizzes available for you at the moment.
                        @endif
                    </h3>
                    @if(auth()->user()->isLecturer() || auth()->user()->isAdmin())
                        <p class="text-sm text-gray-300">Create a quiz by clicking the "New Quiz" button above.</p>
                    @else
                        <p class="text-sm text-gray-300">Check back later or join more groups.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-whatsapp-layout>