<x-whatsapp-layout>
    <x-slot name="sidebar">
        @foreach(auth()->user()->groups as $g)
            <a href="{{ route('groups.topics', $g) }}" 
               class="whatsapp-group-item {{ $g->id == $group->id ? 'active' : '' }}">
                <div class="whatsapp-avatar whatsapp-avatar-sm" 
                     style="background: {{ $g->color ?? '#075E54' }};">
                    {{ $g->name[0] }}
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="font-medium text-sm">{{ $g->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $g->topics->count() }} topics</div>
                </div>
                @if($g->id == $group->id)
                    <div class="text-[10px] text-green-600 font-medium">● Active</div>
                @endif
            </a>
        @endforeach
    </x-slot>

    <!-- Chat Header -->
    <div class="whatsapp-chat-header bg-[#EDEDED] px-5 py-3 border-b border-gray-300 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <!-- Back Button -->
            <a href="{{ route('dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="whatsapp-avatar" style="background: {{ $group->color ?? '#075E54' }};">
                {{ $group->name[0] }}
            </div>
            <div>
                <div class="font-semibold text-base">{{ $group->name }}</div>
                <div class="text-xs text-gray-400">
                    {{ $topics->count() }} topics · {{ $topics->sum('posts_count') }} messages
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <!-- Quiz Creation (Lecturers & Admins only) -->
            @if(auth()->user()->isLecturer() || auth()->user()->isAdmin())
                <a href="{{ route('quizzes.create', $group) }}" 
                   class="text-[#075E54] font-semibold text-sm hover:underline">
                    + New Quiz
                </a>
            @endif
            <!-- Topic Creation (Everyone) -->
            <button onclick="document.getElementById('createTopicModal').showModal()" 
                    class="text-[#075E54] font-semibold text-sm hover:underline">
                + New Topic
            </button>
        </div>
    </div>

    <!-- My Quizzes (Lecturers/Admins only) -->
    @if((auth()->user()->isLecturer() || auth()->user()->isAdmin()) && isset($myQuizzes) && $myQuizzes->count() > 0)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">📋 My Quizzes</h3>
            <div class="space-y-3">
                @foreach($myQuizzes as $quiz)
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-sm">{{ $quiz->title }}</span>
                                <span class="text-xs text-gray-400 ml-2">({{ $quiz->duration }} min)</span>
                                @if($quiz->ends_at)
                                    <span class="text-xs text-gray-400 ml-2">Due: {{ $quiz->ends_at->format('M d, Y g:i A') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ $quiz->submissions_count }} submissions</span>
                                <span class="text-xs text-gray-500">Avg: {{ number_format($quiz->avg_score, 2) }}%</span>
                                <a href="{{ route('quizzes.results', [$group, $quiz]) }}" 
                                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                    View Results
                                </a>
                            </div>
                        </div>
                        @if($quiz->description)
                            <p class="text-sm text-gray-500 mt-1">{{ str()->limit($quiz->description, 100) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Available Quizzes Section -->
    @if(isset($quizStatuses) && $quizStatuses->count() > 0)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-600 mb-2">📝 Available Quizzes</h3>
            <div class="space-y-3">
                @foreach($quizStatuses as $item)
                    @php
                        $quiz = $item['quiz'];
                        $submitted = $item['submitted'];
                        $submission = $item['submission'];
                    @endphp
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-sm">{{ $quiz->title }}</span>
                                <span class="text-xs text-gray-400 ml-2">({{ $quiz->duration }} min)</span>
                                @if($quiz->ends_at)
                                    <span class="text-xs text-gray-400 ml-2">Due: {{ $quiz->ends_at->format('M d, Y g:i A') }}</span>
                                @endif
                            </div>
                            @if($submitted)
                                <a href="{{ route('quizzes.results', [$group, $quiz]) }}" 
                                class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                    View Results
                                </a>
                            @else
                                <a href="{{ route('quizzes.take', [$group, $quiz]) }}" 
                                class="text-sm bg-[#075E54] text-white px-3 py-1 rounded hover:bg-[#128C7E] transition">
                                    Take Quiz
                                </a>
                            @endif
                        </div>
                        @if($quiz->description)
                            <p class="text-sm text-gray-500 mt-1">{{ str()->limit($quiz->description, 100) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Topics as Messages -->
    <div class="whatsapp-messages flex-1 overflow-y-auto px-8 py-4 space-y-2" id="topicList">
        @forelse($topics as $topic)
            <a href="{{ route('topics.show', [$group, $topic]) }}" 
               class="message received block w-full max-w-full no-underline">
                <div class="message-bubble w-full cursor-pointer">
                    <!-- Topic Title + Category Tag -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-[#075E54] text-base">
                            {{ $topic->title }}
                        </span>
                        @if($topic->ml_category)
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                {{ $topic->ml_category }}
                            </span>
                        @endif
                        @if($topic->is_private)
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">🔒 Private</span>
                        @endif
                    </div>
                    
                    <div class="text-gray-600 text-sm mt-1">
                        {{ str()->limit($topic->body, 120) }}
                    </div>
                    
                    <div class="message-meta">
                        <span>{{ $topic->creator->name }}</span>
                        <span>·</span>
                        <span>{{ $topic->created_at->diffForHumans() }}</span>
                        <span>·</span>
                        <span>{{ $topic->posts_count }} replies</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center text-gray-400 py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="font-normal text-base mt-2">No topics yet</h3>
                <p class="text-sm text-gray-300">Start the first discussion in this group!</p>
            </div>
        @endforelse
    </div>

    <!-- Create Topic Modal -->
    <dialog id="createTopicModal" class="whatsapp-modal">
        <div class="p-6">
            <h3 class="text-xl font-semibold mb-4">Create New Topic</h3>
            <form method="POST" action="{{ route('topics.store', $group) }}">
                @csrf
                <input type="text" name="title" placeholder="Topic Title"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg mb-3 text-sm outline-none focus:border-[#075E54]">
                <textarea name="body" rows="4" placeholder="Write your topic content..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg mb-4 text-sm outline-none resize-y focus:border-[#075E54]"></textarea>

                <!-- Privacy Controls -->
                <div class="mb-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_private" value="1" id="topicPrivateCheckbox" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-600">Make this topic private</span>
                    </label>
                </div>

                <div id="topicAllowedUsersContainer" style="display: none;" class="mb-4">
                    <label for="topic_allowed_users" class="block text-xs font-medium text-gray-700">Allow specific users to view</label>
                    <select name="allowed_users[]" id="topic_allowed_users" multiple
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                        @foreach($groupMembers ?? [] as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple users.</p>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('createTopicModal').close()"
                            class="px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border-none rounded-lg bg-[#075E54] text-white hover:bg-[#128C7E] transition">
                        Post Topic
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        document.getElementById('createTopicModal').addEventListener('click', function(e) {
            if (e.target === this) this.close();
        });

        // Toggle allowed users dropdown based on private checkbox
        const privateCheckbox = document.getElementById('topicPrivateCheckbox');
        const allowedContainer = document.getElementById('topicAllowedUsersContainer');
        if (privateCheckbox && allowedContainer) {
            privateCheckbox.addEventListener('change', function() {
                allowedContainer.style.display = this.checked ? 'block' : 'none';
            });
        }
    </script>
</x-whatsapp-layout>