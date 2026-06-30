@props(['post', 'group', 'topic', 'depth' => 0])

<div class="post-item" data-post-id="{{ $post->id }}" style="margin-left: {{ $depth * 30 }}px;">
    <div class="flex items-start gap-3 py-3 border-b border-gray-100">
        <div class="w-8 h-8 rounded-full bg-[#075E54] text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
            {{ $post->user->name[0] }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="font-semibold text-sm">{{ $post->user->name }}</span>
                <span class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</span>
            </div>
            <div class="mt-1 text-gray-700 text-sm whitespace-pre-wrap">
                {{ $post->content }}
            </div>

            <!-- Actions -->
            <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                <!-- Like -->
                <button class="like-btn hover:text-green-600 transition flex items-center gap-1" data-post-id="{{ $post->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span class="like-count">{{ $post->likes_count }}</span>
                </button>

                <!-- Reply -->
                <button class="reply-btn hover:text-blue-600 transition" data-post-id="{{ $post->id }}">
                    Reply
                </button>

                <!-- Share Button -->
                <button class="share-btn text-gray-400 hover:text-blue-600 transition text-sm flex items-center gap-1" 
                        onclick="copyToClipboard('{{ url()->current() }}?post={{ $post->id }}', this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Share
                </button>

                <!-- Toggle replies -->
                @if($post->children->count() > 0)
                    <button class="toggle-replies-btn hover:text-[#075E54] transition flex items-center gap-1" data-post-id="{{ $post->id }}">
                        <svg class="w-3 h-3 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <span class="reply-count-text">{{ $post->children->count() }} replies</span>
                    </button>
                @endif

                <!-- Private Badge -->
                @if($post->is_private)
                    <span class="text-xs text-yellow-600">🔒 Private</span>
                @endif
            </div>

            <!-- Inline Reply Form (AJAX-enabled with auto-expanding textarea) -->
            <div class="reply-form mt-2 hidden" data-post-id="{{ $post->id }}">
                <form method="POST" action="{{ route('posts.store', [$group, $topic]) }}" class="flex items-start gap-2 reply-form-ajax">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $post->id }}">
                    <textarea name="content" rows="1" placeholder="Write a reply..." 
                              class="reply-textarea flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm outline-none resize-none focus:ring-1 focus:ring-[#075E54] focus:border-[#075E54] overflow-hidden"
                              style="min-height: 38px; max-height: 120px;"></textarea>
                    <button type="submit" class="px-3 py-1.5 bg-[#075E54] text-white text-sm rounded-lg hover:bg-[#128C7E] transition flex-shrink-0">
                        Reply
                    </button>
                </form>
            </div>

            <!-- Children (nested) -->
            @if($post->children->count() > 0)
                <div class="children-container ml-6 mt-2 border-l-2 border-gray-200 pl-4 space-y-1 hidden" data-post-id="{{ $post->id }}">
                    @foreach($post->children as $child)
                        @include('topics._post', ['post' => $child, 'group' => $group, 'topic' => $topic, 'depth' => $depth + 1])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>