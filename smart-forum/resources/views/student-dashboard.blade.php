<x-whatsapp-layout>
    <x-slot name="sidebar">
        @forelse($groups as $group)
            <a href="{{ route('groups.topics', $group) }}" class="whatsapp-group-item">
                <div class="whatsapp-avatar whatsapp-avatar-sm" style="background: {{ $group->color ?? '#075E54' }};">
                    {{ $group->name[0] }}
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="font-medium text-sm">{{ $group->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $group->topics_count }} topics</div>
                </div>
            </a>
        @empty
            <div class="py-8 text-center text-gray-400">
                <p>No groups yet.</p>
                <p class="text-xs">Discover groups below to join.</p>
            </div>
        @endforelse
    </x-slot>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-4xl mx-auto">

            <!-- Welcome & Status -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Welcome back, {{ $user->name }}!</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">Status:</span>
                        <span class="px-3 py-1 text-xs rounded-full 
                            {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 
                               ($user->status === 'blacklisted' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst(str_replace('_', ' ', $user->status)) }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400">Member since {{ $user->created_at->format('M d, Y') }}</span>
                </div>
            </div>

            <!-- ===== STATS CARDS ===== -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $stats['likes'] }}</div>
                    <div class="text-xs text-gray-500">Likes Given</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $stats['comments'] }}</div>
                    <div class="text-xs text-gray-500">Comments</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $stats['views'] }}</div>
                    <div class="text-xs text-gray-500">Views</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $stats['downloads'] }}</div>
                    <div class="text-xs text-gray-500">Downloads</div>
                </div>
            </div>

            <!-- ===== QUIZ PERFORMANCE (always visible) ===== -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">Quiz Performance</h3>
                @if($quizzesCompleted > 0)
                    <div class="flex items-center gap-6">
                        <div>
                            <span class="text-sm text-gray-500">Completed:</span>
                            <span class="font-semibold">{{ $quizzesCompleted }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Average Score:</span>
                            <span class="font-semibold">{{ number_format($averageScore, 2) }}%</span>
                        </div>
                    </div>
                    @if($quizSubmissions->count() > 0)
                        <div class="mt-3 space-y-1">
                            @foreach($quizSubmissions as $submission)
                                <div class="flex items-center justify-between text-sm">
                                    <span>{{ $submission->quiz->title ?? 'Quiz' }}</span>
                                    <span class="font-medium">{{ $submission->score ?? 'N/A' }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-400">You haven't taken any quizzes yet.</p>
                @endif
            </div>

            <!-- ===== GROUPS (Member of) ===== -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-3">My Groups</h3>
                @if($groups->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($groups as $group)
                            <a href="{{ route('groups.topics', $group) }}" 
                               class="block p-4 bg-white hover:bg-gray-50 rounded-lg border border-gray-200 transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $group->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $group->topics_count }} topics</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ $group->description ?? 'No description' }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white p-6 rounded-lg border border-gray-200 text-center">
                        <p class="text-gray-500">You are not a member of any group yet.</p>
                        <div class="mt-4">
                            <button onclick="document.getElementById('discoverGroups').scrollIntoView({behavior:'smooth'})" 
                                    class="px-4 py-2 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition">
                                Discover Groups
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- ===== DISCOVER GROUPS (All Groups) ===== -->
            <div id="discoverGroups" class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-3">Discover Groups</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($allGroups as $group)
                        @php
                            $isMember = $groups->contains('id', $group->id);
                        @endphp
                        @if(!$isMember)
                            <div class="p-4 bg-white rounded-lg border border-gray-200">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $group->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $group->users_count }} members</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ $group->description ?? 'No description' }}</p>
                                <form method="POST" action="{{ route('groups.request-join', $group) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="text-sm bg-[#075E54] text-white px-3 py-1 rounded hover:bg-[#128C7E] transition">
                                        Request to Join
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if($allGroups->count() == 0)
                    <p class="text-gray-400 text-sm">No groups available to join.</p>
                @endif
            </div>
        </div>
    </div>
</x-whatsapp-layout>