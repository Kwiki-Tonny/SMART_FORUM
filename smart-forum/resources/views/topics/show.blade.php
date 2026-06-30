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
            </a>
        @endforeach
    </x-slot>

    <!-- ===== HEADER ===== -->
    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <!-- Back to Topics -->
            <a href="{{ route('groups.topics', $group) }}" 
               class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200"
               title="Back to Topics">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            <div class="w-8 h-8 rounded-full bg-[#075E54] text-white flex items-center justify-center text-sm font-semibold">
                {{ $group->name[0] }}
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-base truncate max-w-[200px]">{{ $topic->title }}</span>
                    @if($topic->ml_category)
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                            {{ $topic->ml_category }}
                        </span>
                    @endif
                </div>
                <div class="text-xs text-gray-400">
                    {{ $topic->posts->whereNull('parent_id')->count() }} replies
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('topics.export', [$group, $topic]) }}" 
               class="text-gray-400 hover:text-[#075E54] transition" 
               title="Export to PDF">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- ===== THREAD ===== -->
    <div class="flex-1 overflow-y-auto bg-white px-6 py-4" id="messagesContainer">
        <!-- Topic as main post (no like button) -->
        <div class="border-b border-gray-200 pb-4 mb-2">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-[#075E54] text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                    {{ $topic->creator->name[0] }}
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm">{{ $topic->creator->name }}</span>
                        <span class="text-xs text-gray-400">{{ $topic->created_at->diffForHumans() }}</span>
                        @if($topic->ml_category)
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                {{ $topic->ml_category }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 text-gray-700 text-sm whitespace-pre-wrap">
                        {{ $topic->body }}
                    </div>
                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                        <button onclick="document.getElementById('replyInput').focus()" class="hover:text-blue-600 transition">
                            Add a reply
                        </button>
                        <!-- Share Button -->
                        <button class="share-btn text-gray-400 hover:text-blue-600 transition text-sm flex items-center gap-1" 
                                onclick="copyToClipboard('{{ url()->current() }}', this)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            Share
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top-level replies -->
        @php
            $topLevelPosts = $topic->posts()->whereNull('parent_id')->with('children', 'user')->oldest()->get();
        @endphp

        @forelse($topLevelPosts as $post)
            @include('topics._post', ['post' => $post, 'group' => $group, 'topic' => $topic, 'depth' => 0])
        @empty
            <div class="text-center text-gray-400 py-12">
                <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="font-normal text-base mt-2">No replies yet</h3>
                <p class="text-sm text-gray-300">Be the first to comment!</p>
            </div>
        @endforelse

        <!-- Typing indicator -->
        <div id="typingIndicator" class="text-gray-400 text-sm py-2" style="display: none;">
            Someone is typing...
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg text-sm z-50 transition-opacity duration-300 opacity-0 pointer-events-none">
        Link copied to clipboard!
    </div>

    <!-- ===== INPUT AREA (with auto-expanding textarea) ===== -->
    <div class="bg-white border-t border-gray-200 px-5 py-3">
        <form method="POST" action="{{ route('posts.store', [$group, $topic]) }}" class="flex-1 flex flex-col gap-2 ajax-reply-form">
            @csrf
            <input type="hidden" name="parent_id" value="">

            <div class="flex items-start gap-2">
                <button type="button" class="text-gray-400 text-xl hover:text-gray-600 transition mt-1">😊</button>
                <button type="button" class="text-gray-400 text-xl hover:text-gray-600 transition mt-1">📎</button>
                <textarea name="content" id="replyInput" rows="1" placeholder="Type your reply..."
                          class="flex-1 px-4 py-2 border border-gray-300 rounded-full text-sm outline-none resize-none focus:border-[#075E54] overflow-hidden"
                          style="min-height: 42px; max-height: 150px;" data-min-height="42px"></textarea>
                <button type="submit" class="px-4 py-2 bg-[#075E54] text-white rounded-full hover:bg-[#128C7E] transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                    </svg>
                </button>
            </div>

            <!-- Private post options -->
            <div class="flex flex-wrap items-center gap-4 mt-1">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_private" value="1" id="privateCheckbox" class="rounded border-gray-300">
                    <span class="ml-2 text-sm text-gray-600">Make this reply private</span>
                </label>

                <div id="allowedUsersContainer" style="display: none;" class="flex-1 min-w-[200px]">
                    <label for="allowed_users" class="block text-xs font-medium text-gray-700">Allow specific users to view</label>
                    <select name="allowed_users[]" id="allowed_users" multiple
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                        @foreach($groupMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple users.</p>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // =============================================
        // SHARE & PERMALINK FUNCTIONS
        // =============================================
        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.remove('opacity-0', 'pointer-events-none');
                toast.classList.add('opacity-100');
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'pointer-events-none');
                }, 1000);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }

        // Scroll to post if ?post= query param exists
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post');
        if (postId) {
            setTimeout(() => {
                const postElement = document.querySelector(`.post-item[data-post-id="${postId}"]`);
                if (postElement) {
                    postElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    postElement.classList.add('bg-yellow-50', 'border-l-4', 'border-yellow-400');
                }
            }, 500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // ===== Toggle allowed users dropdown =====
            const privateCheckbox = document.getElementById('privateCheckbox');
            const allowedContainer = document.getElementById('allowedUsersContainer');

            if (privateCheckbox && allowedContainer) {
                privateCheckbox.addEventListener('change', function() {
                    allowedContainer.style.display = this.checked ? 'block' : 'none';
                });
            }

            // ===== Auto-resize textarea (main) =====
            const replyInput = document.getElementById('replyInput');
            if (replyInput) {
                replyInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 150) + 'px';
                });
            }

            // ===== Auto-resize for dynamically added inline reply textareas =====
            function bindAutoResize() {
                document.querySelectorAll('.reply-textarea').forEach(textarea => {
                    textarea.removeEventListener('input', handleResize);
                    textarea.addEventListener('input', handleResize);
                });
            }

            function handleResize() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            }

            // ===== Existing real‑time & interaction logic =====
            const container = document.getElementById('messagesContainer');
            const typingIndicator = document.getElementById('typingIndicator');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Scroll to bottom
            if (container) container.scrollTop = container.scrollHeight;

            // Enter to submit (will be handled by AJAX)
            if (replyInput) {
                replyInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        // Trigger submit on the closest form
                        this.closest('form').dispatchEvent(new Event('submit', { bubbles: true }));
                    }
                });
            }

            // ----- PER-POST LIKE BUTTON (using post-id) -----
            function handleLikeClick(e) {
                e.preventDefault();
                const btn = this;
                const postId = btn.dataset.postId;
                const countSpan = btn.querySelector('.like-count');

                if (!postId) return;

                fetch(`/posts/${postId}/like`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    countSpan.textContent = data.count;
                    if (data.liked) {
                        btn.classList.add('text-green-600');
                        btn.classList.remove('text-gray-500');
                    } else {
                        btn.classList.remove('text-green-600');
                        btn.classList.add('text-gray-500');
                    }
                })
                .catch(error => console.error('Like error:', error));
            }

            function bindLikeEvents() {
                document.querySelectorAll('.like-btn').forEach(btn => {
                    btn.removeEventListener('click', handleLikeClick);
                    btn.addEventListener('click', handleLikeClick);
                });
            }

            // ----- REPLY BUTTON -----
            function handleReplyClick(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                if (!postId) return;
                const form = document.querySelector(`.reply-form[data-post-id="${postId}"]`);
                if (form) {
                    form.classList.toggle('hidden');
                    if (!form.classList.contains('hidden')) {
                        form.querySelector('textarea[name="content"]')?.focus();
                    }
                }
            }

            function bindReplyEvents() {
                document.querySelectorAll('.reply-btn').forEach(btn => {
                    btn.removeEventListener('click', handleReplyClick);
                    btn.addEventListener('click', handleReplyClick);
                });
                // Enter on reply inputs (handled via form submit)
                document.querySelectorAll('.reply-form textarea[name="content"]').forEach(textarea => {
                    textarea.removeEventListener('keydown', handleReplyInputKeydown);
                    textarea.addEventListener('keydown', handleReplyInputKeydown);
                });
            }

            function handleReplyInputKeydown(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.closest('form').dispatchEvent(new Event('submit', { bubbles: true }));
                }
            }

            // ----- TOGGLE REPLIES -----
            function handleToggleClick(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                if (!postId) return;
                const container = document.querySelector(`.children-container[data-post-id="${postId}"]`);
                const icon = this.querySelector('svg');
                const text = this.querySelector('.reply-count-text');

                if (container) {
                    const isHidden = container.classList.contains('hidden');
                    if (isHidden) {
                        container.classList.remove('hidden');
                        if (icon) icon.style.transform = 'rotate(180deg)';
                        if (text) text.textContent = 'Hide replies';
                    } else {
                        container.classList.add('hidden');
                        if (icon) icon.style.transform = 'rotate(0deg)';
                        if (text) {
                            const count = container.querySelectorAll('.post-item').length;
                            text.textContent = `${count} ${count === 1 ? 'reply' : 'replies'}`;
                        }
                    }
                }
            }

            function bindToggleEvents() {
                document.querySelectorAll('.toggle-replies-btn').forEach(btn => {
                    btn.removeEventListener('click', handleToggleClick);
                    btn.addEventListener('click', handleToggleClick);
                });
            }

            // ----- TYPING INDICATOR -----
            let typingTimeout;
            document.querySelectorAll('textarea[name="content"]').forEach(field => {
                field.addEventListener('input', function() {
                    if (!typingIndicator) return;
                    clearTimeout(typingTimeout);
                    typingIndicator.style.display = 'block';
                    typingTimeout = setTimeout(() => {
                        typingIndicator.style.display = 'none';
                    }, 1500);
                });
            });

            // ----- INITIAL BIND -----
            bindLikeEvents();
            bindReplyEvents();
            bindToggleEvents();
            bindAutoResize();

            // =============================================
            // AJAX FORM SUBMISSION (REAL-TIME WITHOUT RELOAD)
            // =============================================
            function handleAjaxFormSubmit(e) {
                e.preventDefault();
                const form = this;
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Disable button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Sending...';

                const formData = new FormData(form);
                const action = form.action;

                // Toast helper
                function showToast(message, type = 'success') {
                    const toast = document.getElementById('toast');
                    if (!toast) return;
                    toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded-lg shadow-lg text-sm z-50 transition-opacity duration-300 ' +
                        (type === 'error' ? 'bg-red-600 text-white' : 'bg-green-600 text-white');
                    toast.textContent = message;
                    toast.classList.remove('opacity-0', 'pointer-events-none');
                    toast.classList.add('opacity-100');
                    setTimeout(() => {
                        toast.classList.add('opacity-0', 'pointer-events-none');
                    }, 1000);
                }

                fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Server returned non-JSON response. Status: ' + response.status);
                    }
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Clear the input field (works for both input and textarea)
                        const inputField = form.querySelector('[name="content"]');
                        if (inputField) {
                            inputField.value = '';
                            // Reset height for textarea
                            if (inputField.tagName === 'TEXTAREA') {
                                inputField.style.height = 'auto';
                                const minHeight = inputField.dataset.minHeight || '42px';
                                inputField.style.height = minHeight;
                            }
                        }
                        showToast('Reply posted!', 'success');
                        console.log('✅ Reply posted:', data.post);
                    } else {
                        // success: false
                        const msg = data.message || 'Something went wrong.';
                        showToast(msg, 'error');
                    }
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    const msg = error.message || 'Failed to post reply. Please try again.';
                    showToast(msg, 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            }

            // Apply AJAX to all reply forms (main form and inline forms)
            document.querySelectorAll('.ajax-reply-form, .reply-form-ajax').forEach(form => {
                form.removeEventListener('submit', handleAjaxFormSubmit);
                form.addEventListener('submit', handleAjaxFormSubmit);
            });

            // Also attach to any form with a parent_id input that is not already covered
            document.querySelectorAll('form[action*="posts.store"]').forEach(form => {
                if (!form.classList.contains('ajax-reply-form') && !form.classList.contains('reply-form-ajax')) {
                    form.removeEventListener('submit', handleAjaxFormSubmit);
                    form.addEventListener('submit', handleAjaxFormSubmit);
                }
            });

            // ----- REAL-TIME (Echo) -----
            const groupId = {{ $group->id }};
            const topicId = {{ $topic->id }};

            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel(`group.${groupId}`)
                    .listen('post.created', (e) => {
                        if (e.topic_id !== topicId) return;

                        const post = e.post;
                        const parentId = post.parent_id || null;

                        if (typingIndicator) typingIndicator.style.display = 'none';

                        // Build the HTML for a new post (top-level or nested)
                        const buildPostHtml = (post, depth = 0) => {
                            const isTop = parentId === null;
                            const privateBadge = post.is_private ? `<span class="text-xs text-yellow-600">🔒 Private</span>` : '';
                            return `
                                <div class="post-item" data-post-id="${post.id}" style="margin-left: ${depth * 30}px;">
                                    <div class="flex items-start gap-3 py-3 border-b border-gray-100">
                                        <div class="w-8 h-8 rounded-full bg-[#075E54] text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                                            ${post.user.name[0]}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-sm">${post.user.name}</span>
                                                <span class="text-xs text-gray-400">Just now</span>
                                            </div>
                                            <div class="mt-1 text-gray-700 text-sm whitespace-pre-wrap">
                                                ${post.content}
                                            </div>
                                            <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                                                <button class="like-btn hover:text-green-600 transition flex items-center gap-1" data-post-id="${post.id}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                                    </svg>
                                                    <span class="like-count">0</span>
                                                </button>
                                                <button class="reply-btn hover:text-blue-600 transition" data-post-id="${post.id}">Reply</button>
                                                ${privateBadge}
                                            </div>
                                            <div class="reply-form mt-2 hidden" data-post-id="${post.id}">
                                                <form method="POST" action="{{ route('posts.store', [$group, $topic]) }}" class="flex gap-2 reply-form-ajax">
                                                    @csrf
                                                    <input type="hidden" name="parent_id" value="${post.id}">
                                                    <textarea name="content" rows="1" placeholder="Write a reply..." 
                                                              class="reply-textarea flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm outline-none resize-none focus:ring-1 focus:ring-[#075E54] focus:border-[#075E54] overflow-hidden"
                                                              style="min-height: 38px; max-height: 120px;"></textarea>
                                                    <button type="submit" class="px-3 py-1.5 bg-[#075E54] text-white text-sm rounded-lg">Reply</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        };

                        // For top-level post
                        if (parentId === null) {
                            const noReplies = container.querySelector('.text-center.text-gray-400.py-12');
                            if (noReplies) noReplies.remove();

                            const html = buildPostHtml(post, 0);
                            container.insertAdjacentHTML('beforeend', html);
                            bindLikeEvents();
                            bindReplyEvents();
                            bindAutoResize(); // auto-resize for the new textarea
                            // Attach AJAX to the newly added form
                            const newForm = container.querySelector('.post-item:last-child .reply-form-ajax');
                            if (newForm) {
                                newForm.removeEventListener('submit', handleAjaxFormSubmit);
                                newForm.addEventListener('submit', handleAjaxFormSubmit);
                            }
                            container.scrollTop = container.scrollHeight;
                        } else {
                            // Nested reply – find parent and append
                            const parentElement = document.querySelector(`.post-item[data-post-id="${parentId}"]`);
                            if (parentElement) {
                                let childrenContainer = parentElement.querySelector('.children-container');
                                if (!childrenContainer) {
                                    childrenContainer = document.createElement('div');
                                    childrenContainer.className = 'children-container ml-6 mt-2 border-l-2 border-gray-200 pl-4 space-y-1 hidden';
                                    childrenContainer.dataset.postId = parentId;
                                    parentElement.querySelector('.flex-1').appendChild(childrenContainer);
                                }

                                // Ensure toggle button exists
                                let toggleBtn = parentElement.querySelector('.toggle-replies-btn');
                                if (!toggleBtn) {
                                    const actionsDiv = parentElement.querySelector('.flex-1 .flex.items-center.gap-4');
                                    if (actionsDiv) {
                                        const btnHtml = `
                                            <button class="toggle-replies-btn hover:text-[#075E54] transition flex items-center gap-1" data-post-id="${parentId}">
                                                <svg class="w-3 h-3 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="reply-count-text">1 reply</span>
                                            </button>
                                        `;
                                        actionsDiv.insertAdjacentHTML('beforeend', btnHtml);
                                        bindToggleEvents();
                                    }
                                } else {
                                    const childCount = childrenContainer.querySelectorAll('.post-item').length + 1;
                                    const textSpan = toggleBtn.querySelector('.reply-count-text');
                                    if (textSpan) textSpan.textContent = `${childCount} ${childCount === 1 ? 'reply' : 'replies'}`;
                                }

                                const depth = parseInt(parentElement.style.marginLeft) / 30 + 1 || 1;
                                const html = buildPostHtml(post, depth);
                                childrenContainer.insertAdjacentHTML('beforeend', html);

                                // Attach AJAX to the new form
                                const newForm = childrenContainer.querySelector('.post-item:last-child .reply-form-ajax');
                                if (newForm) {
                                    newForm.removeEventListener('submit', handleAjaxFormSubmit);
                                    newForm.addEventListener('submit', handleAjaxFormSubmit);
                                }
                                bindAutoResize();
                                bindReplyEvents();
                                bindToggleEvents();

                                // If container is not hidden, scroll to new reply
                                if (!childrenContainer.classList.contains('hidden')) {
                                    const newReply = childrenContainer.querySelector('.post-item:last-child');
                                    if (newReply) newReply.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            }
                        }
                    });
            }

            // Close reply forms on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.reply-form:not(.hidden)').forEach(form => {
                        form.classList.add('hidden');
                    });
                }
            });

            console.log('LinkedIn-style thread loaded successfully!');
        });
    </script>
    @endpush
</x-whatsapp-layout>