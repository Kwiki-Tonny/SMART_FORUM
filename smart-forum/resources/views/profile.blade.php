<x-whatsapp-layout>
    <x-slot name="sidebar">
        @foreach(auth()->user()->groups as $g)
            <a href="{{ route('groups.topics', $g) }}" class="whatsapp-group-item">
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
        <span class="text-lg font-semibold">Profile</span>
    </div>

    <!-- ===== PROFILE CONTENT ===== -->
    <div class="flex-1 overflow-y-auto bg-white px-6 py-4">
        <div class="max-w-3xl mx-auto">
            <!-- User Card -->
            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-[#075E54] text-white flex items-center justify-center text-2xl font-semibold">
                        {{ $user->name[0] }}
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold">{{ $user->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                                {{ ucfirst($user->role) }}
                            </span>
                            <span class="text-xs {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} px-2 py-0.5 rounded-full">
                                {{ ucfirst(str_replace('_', ' ', $user->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Joined:</span>
                        <span class="font-medium">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Last active:</span>
                        <span class="font-medium">{{ $user->last_communicated_at ? $user->last_communicated_at->diffForHumans() : 'Never' }}</span>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-4 gap-2 text-center">
                    <div class="bg-white rounded-lg p-2 shadow-sm">
                        <div class="text-lg font-semibold text-[#075E54]">{{ $user->total_likes_given }}</div>
                        <div class="text-xs text-gray-500">Likes</div>
                    </div>
                    <div class="bg-white rounded-lg p-2 shadow-sm">
                        <div class="text-lg font-semibold text-[#075E54]">{{ $user->total_comments }}</div>
                        <div class="text-xs text-gray-500">Comments</div>
                    </div>
                    <div class="bg-white rounded-lg p-2 shadow-sm">
                        <div class="text-lg font-semibold text-[#075E54]">{{ $user->total_views }}</div>
                        <div class="text-xs text-gray-500">Views</div>
                    </div>
                    <div class="bg-white rounded-lg p-2 shadow-sm">
                        <div class="text-lg font-semibold text-[#075E54]">{{ $user->total_downloads }}</div>
                        <div class="text-xs text-gray-500">Downloads</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div>
                <h4 class="font-medium text-gray-700 mb-3">Recent Activity</h4>
                @if($interactions->count() > 0)
                    <div class="space-y-2">
                        @foreach($interactions as $interaction)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ucfirst($interaction->action_type) }}
                                    </span>
                                    <span class="text-sm text-gray-500">on</span>
                                    <a href="{{ route('topics.show', [$interaction->topic->group_id ?? 0, $interaction->topic_id]) }}" 
                                       class="text-sm text-[#075E54] hover:underline">
                                        {{ $interaction->topic->title ?? 'Unknown topic' }}
                                    </a>
                                </div>
                                <span class="text-xs text-gray-400">{{ $interaction->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-sm">No activity yet. Start engaging with topics!</p>
                @endif
            </div>
        </div>
    </div>
</x-whatsapp-layout>