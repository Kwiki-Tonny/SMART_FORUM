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

    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('groups.topics', $group) }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="font-semibold text-base">{{ $quiz->title }}</span>
        </div>
        <div id="timer" class="text-lg font-bold text-red-600">--:--</div>
    </div>

    <div class="flex-1 overflow-y-auto bg-white px-6 py-4">
        <div class="max-w-2xl mx-auto">
            <form id="quizForm" action="{{ route('quizzes.submit', [$group, $quiz]) }}" method="POST">
                @csrf
                <input type="hidden" name="auto_submit" id="autoSubmit" value="0">

                @foreach($quiz->questions as $index => $question)
                    <div class="mb-6 p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <span class="font-medium text-sm text-gray-500">{{ $index + 1 }}.</span>
                            <div>
                                <p class="font-medium">{{ $question['question'] }}</p>
                                <div class="mt-2 space-y-2">
                                    @if($question['type'] === 'multiple_choice')
                                        @foreach(explode(',', $question['options'] ?? '') as $option)
                                            <label class="flex items-center gap-2">
                                                <input type="radio" name="answers[{{ $index }}]" value="{{ trim($option) }}" class="rounded border-gray-300">
                                                <span class="text-sm">{{ trim($option) }}</span>
                                            </label>
                                        @endforeach
                                    @elseif($question['type'] === 'true_false')
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="answers[{{ $index }}]" value="true" class="rounded border-gray-300">
                                            <span class="text-sm">True</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="answers[{{ $index }}]" value="false" class="rounded border-gray-300">
                                            <span class="text-sm">False</span>
                                        </label>
                                    @else
                                        <input type="text" name="answers[{{ $index }}]" placeholder="Type your answer..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400">Marks: {{ $question['marks'] ?? 1 }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <button type="submit" id="submitBtn" class="w-full px-4 py-2 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition">
                    Submit Quiz
                </button>
            </form>
        </div>
    </div>

   <script>
    document.addEventListener('DOMContentLoaded', function() {
        let duration = {{ $quiz->duration }} * 60; // seconds
        const timerEl = document.getElementById('timer');
        const form = document.getElementById('quizForm');
        const autoSubmit = document.getElementById('autoSubmit');
        let breachCount = 0;
        const MAX_BREACHES = 3;
        let formSubmitted = false;

        // ---- Lockdown: Disable right-click ----
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // ---- Lockdown: Disable copy, cut, paste ----
        document.addEventListener('copy', function(e) { e.preventDefault(); });
        document.addEventListener('cut', function(e) { e.preventDefault(); });
        document.addEventListener('paste', function(e) { e.preventDefault(); });

        // ---- Lockdown: Request fullscreen ----
        function requestFullscreen() {
            const docEl = document.documentElement;
            if (docEl.requestFullscreen) {
                docEl.requestFullscreen().catch(() => {});
            } else if (docEl.webkitRequestFullscreen) {
                docEl.webkitRequestFullscreen();
            } else if (docEl.msRequestFullscreen) {
                docEl.msRequestFullscreen();
            }
        }
        requestFullscreen();

        // ---- Lockdown: Blur detection ----
        function handleBlur() {
            if (duration <= 0 || formSubmitted) return;
            breachCount++;
            if (breachCount >= MAX_BREACHES) {
                autoSubmit.value = '1';
                submitQuiz();
            } else {
                alert(`⚠️ You switched tabs! (${breachCount}/${MAX_BREACHES} warnings). If you switch again, the quiz will be auto-submitted.`);
                window.focus();
                requestFullscreen();
            }
        }

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                handleBlur();
            }
        });

        // ---- Timer ----
        function updateTimer() {
            if (duration <= 0) {
                autoSubmit.value = '1';
                submitQuiz();
                return;
            }
            const mins = Math.floor(duration / 60);
            const secs = duration % 60;
            timerEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            if (duration < 60) timerEl.classList.add('text-red-600', 'animate-pulse');
            duration--;
        }

        updateTimer();
        const interval = setInterval(updateTimer, 1000);

        // ---- AJAX Submit ----
        function submitQuiz() {
            if (formSubmitted) return;
            formSubmitted = true;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong.'));
                    formSubmitted = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Quiz';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit quiz.');
                formSubmitted = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Quiz';
            });
        }

        // ---- Form submit handler ----
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitQuiz();
        });

        // ---- Before unload warning (only if not submitted) ----
        window.addEventListener('beforeunload', function(e) {
            if (!formSubmitted && duration > 0) {
                e.preventDefault();
                e.returnValue = 'You have not submitted the quiz. Are you sure?';
            }
        });
    });
</script>
</x-whatsapp-layout>