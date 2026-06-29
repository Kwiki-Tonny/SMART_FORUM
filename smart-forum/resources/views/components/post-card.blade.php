@props(['post', 'group', 'topic', 'depth' => 0])

<div class="post-card border border-gray-200 rounded-lg p-4 mb-2 bg-white shadow-sm hover:shadow-md transition" 
     data-post-id="{{ $post->id }}" 
     data-depth="{{ $depth }}"
     style="margin-left: {{ $depth * 20 }}px;">
    
    <div class="flex items-start">
        <!-- Avatar -->
        <div class="whatsapp-avatar whatsapp-avatar-sm mr-3 flex-shrink-0" style="background: #075E54;">
            {{ $post->user->name[0] }}
        </div>

        <div class="flex-1 min-w-0">
            <!-- User & Date -->
            <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="font-semibold text-sm text-gray-800">{{ $post->user->name }}</span>
                <span class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</span>
            </div>

            <!-- Content -->
            <div class="mt-1 text-gray-700 text-sm whitespace-pre-wrap leading-relaxed">
                {{ $post->content }}
            </div>

            <!-- ========================================== -->
            <!-- ACTIONS                                     -->
            <!-- ========================================== -->
            <div class="mt-3 flex items-center gap-4 flex-wrap">
                <!-- Like Button -->
                <button class="like-btn flex items-center gap-1 text-gray-400 hover:text-green-600 transition" 
                        data-topic-id="{{ $topic->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span class="like-count text-xs">{{ $topic->likes_count }}</span>
                </button>

                <!-- Reply Button -->
                <button class="reply-btn text-gray-400 hover:text-blue-600 transition text-sm flex items-center gap-1" 
                        data-post-id="{{ $post->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    Reply
                </button>

                <!-- Toggle Replies (only if children exist) -->
                @if($post->children->count() > 0)
                    <button class="toggle-replies-btn text-sm text-[#075E54] hover:underline transition flex items-center gap-1" 
                            data-post-id="{{ $post->id }}">
                        <svg class="w-3 h-3 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <span class="reply-count-text">
                            {{ $post->children->count() }} {{ Str::plural('reply', $post->children->count()) }}
                        </span>
                    </button>
                @endif

                @if($post->is_private)
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">🔒 Private</span>
                @endif
            </div>

            <!-- Inline Reply Form -->
            <div class="reply-form mt-2 hidden" data-post-id="{{ $post->id }}">
                <form method="POST" action="{{ route('posts.store', [$group, $topic]) }}" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $post->id }}">
                    <input type="text" name="content" placeholder="Write a reply..." 
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-1 focus:ring-[#075E54] focus:border-[#075E54]">
                    <button type="submit" class="px-3 py-1.5 bg-[#075E54] text-white text-sm rounded-lg hover:bg-[#128C7E] transition flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                        </svg>
                        Reply
                    </button>
                </form>
            </div>

            <!-- ========================================== -->
            <!-- NESTED CHILDREN – HIDDEN BY DEFAULT        -->
            <!-- ========================================== -->
            @if($post->children->count() > 0)
                <div class="children-container ml-6 mt-3 border-l-2 border-gray-200 pl-4 space-y-3 hidden" 
                     data-post-id="{{ $post->id }}">
                    @foreach($post->children as $child)
                        <x-post-card :post="$child" :group="$group" :topic="$topic" :depth="$depth + 1" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>