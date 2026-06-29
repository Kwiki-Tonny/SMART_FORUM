<x-whatsapp-layout>
    <x-slot name="sidebar">
    @forelse($sidebarGroups as $group)
        <a href="{{ route('groups.topics', $group) }}" class="whatsapp-group-item">
            <div class="whatsapp-avatar whatsapp-avatar-sm" 
                 style="background: {{ $group->color ?? '#075E54' }};">
                {{ $group->name[0] }}
            </div>
            <div class="flex-1 ml-3 min-w-0">
                <div class="font-medium text-sm">{{ $group->name }}</div>
                <div class="text-xs text-gray-400 truncate">
                    {{ $group->description ?? 'Click to join discussion' }}
                </div>
            </div>
            <div class="text-xs text-gray-400">
                {{ $group->topics_count ?? 0 }} topics
            </div>
        </a>
    @empty
        <div class="py-8 text-center text-gray-400">
            <p>No groups available.</p>
            <p class="text-xs">Contact an administrator to create one.</p>
        </div>
    @endforelse
</x-slot>

    <!-- ===== MAIN CONTENT: All Groups ===== -->
    <div class="flex-1 overflow-y-auto bg-white px-6 py-4" id="dashboardContent">
        <div class="max-w-4xl mx-auto">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">All Groups</h3>

            @if($allGroups->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($allGroups as $group)
                        @php
                            $isMember = in_array($group->id, $userGroupIds);
                        @endphp
                        <div class="block p-5 bg-gray-50 hover:bg-gray-100 rounded-lg border border-gray-200 transition">
                            <h4 class="text-md font-semibold text-gray-800">{{ $group->name }}</h4>
                            <p class="text-sm text-gray-600 mt-1">{{ $group->description ?? 'No description' }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $group->users_count }} members</span>

                                @if($isMember)
                                    <a href="{{ route('groups.topics', $group) }}" 
                                       class="text-sm text-[#075E54] font-medium hover:underline">
                                        Open Group →
                                    </a>
                                @else
                                    <form method="POST" action="{{ route('groups.request-join', $group) }}">
                                        @csrf
                                        <button type="submit" 
                                                class="text-sm bg-[#075E54] text-white px-4 py-1.5 rounded-full hover:bg-[#128C7E] transition">
                                            Request to Join
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-400 py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <p class="mt-2">No groups available yet.</p>
                    <p class="text-sm">Contact an administrator to create one.</p>
                </div>
            @endif
        </div>
    </div>
</x-whatsapp-layout>