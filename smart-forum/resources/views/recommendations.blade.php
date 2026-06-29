<x-whatsapp-layout>
    <x-slot name="sidebar">
        @foreach(auth()->user()->groups as $g)
            <a href="{{ route('groups.topics', $g) }}" 
               class="whatsapp-group-item">
                <div class="whatsapp-avatar whatsapp-avatar-sm" 
                     style="background: {{ $g->color ?? '#075E54' }};">
                    {{ $g->name[0] }}
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="font-medium text-sm">{{ $g->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $g->topics->count() }} topics</div>
                </div>
            </a>
        @endforeach
    </x-slot>

    <!-- ===== HEADER ===== -->
    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Recommendations for You</span>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 overflow-y-auto bg-white px-6 py-4">
        <!-- Affinity breakdown -->
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-600 mb-2">Your interests</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($affinity as $category => $score)
                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs">
                        {{ $category }} ({{ $score }}%)
                    </span>
                @endforeach
            </div>
        </div>

        <!-- Recommended topics -->
        <div>
            <h4 class="text-sm font-medium text-gray-600 mb-3">Suggested topics</h4>
            @if($recommendations->count() > 0)
                <div class="space-y-3">
                    @foreach($recommendations as $topic)
                        <a href="{{ route('topics.show', [$topic->group, $topic]) }}" 
                           class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#075E54] text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                    {{ $topic->creator->name[0] }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-sm">{{ $topic->title }}</span>
                                        @if($topic->ml_category)
                                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                                {{ $topic->ml_category }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $topic->group->name }} · {{ $topic->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-400 py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <p class="mt-2">No recommendations yet.</p>
                    <p class="text-sm">Start engaging with topics to get suggestions.</p>
                </div>
            @endif
        </div>
    </div>
</x-whatsapp-layout>