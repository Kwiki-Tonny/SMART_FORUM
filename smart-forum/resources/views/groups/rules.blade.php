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

    <!-- Rules Content -->
    <div class="whatsapp-chat-header bg-[#EDEDED] px-5 py-3 border-b border-gray-300">
        <div class="flex items-center gap-3">
            <div class="whatsapp-avatar bg-[#075E54]">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <div class="font-semibold text-base">Group Rules</div>
                <div class="text-xs text-gray-400">Please read before joining {{ $group->name }}</div>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-8 bg-[#E5DDD5]">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-8">
            <div class="space-y-4 text-gray-700">
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">1.</span>
                    <p>Be respectful to all members at all times.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">2.</span>
                    <p>No spam, self-promotion, or irrelevant content.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">3.</span>
                    <p>Stay on topic for each discussion thread.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">4.</span>
                    <p>Use appropriate and professional language.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">5.</span>
                    <p>Report any violations to the group administrator.</p>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-[#075E54] font-bold text-lg">6.</span>
                    <p>Academic honesty is expected in all discussions.</p>
                </div>
            </div>

            <div class="mt-8 flex gap-4 justify-end border-t border-gray-100 pt-6">
                <form method="POST" action="{{ route('groups.accept-rules', $group) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" name="action" value="decline"
                            class="px-6 py-2.5 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition">
                        Decline
                    </button>
                    <button type="submit" name="action" value="agree"
                            class="px-6 py-2.5 border-none rounded-lg bg-[#075E54] text-white hover:bg-[#128C7E] transition">
                        I Agree & Join
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-whatsapp-layout>