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

    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('groups.topics', $group) }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Create Quiz</span>
    </div>

    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('quizzes.store', $group) }}" id="quizForm">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Quiz Title</label>
                    <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                    <input type="number" name="duration" required min="1" max="180" value="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                </div>

                <!-- Questions Builder -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Questions</label>
                    <div id="questionsContainer">
                        <div class="question-template border border-gray-200 rounded-lg p-4 mb-3 bg-gray-50">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-sm">Question 1</span>
                                <button type="button" class="remove-question text-red-600 hover:text-red-800 text-sm">Remove</button>
                            </div>
                            <div class="mt-2">
                                <input type="text" name="questions[0][question]" placeholder="Enter question..." required class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                            </div>
                            <div class="mt-2">
                                <select name="questions[0][type]" class="question-type w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="true_false">True / False</option>
                                    <option value="short_answer">Short Answer</option>
                                </select>
                            </div>
                            <div class="options-container mt-2">
                                <label class="text-xs text-gray-600">Options (comma separated)</label>
                                <input type="text" name="questions[0][options]" placeholder="Option A, Option B, Option C, Option D" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                                <label class="text-xs text-gray-600 mt-2 block">Correct Answer</label>
                                <input type="text" name="questions[0][correct_answer]" placeholder="Correct answer" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                                <label class="text-xs text-gray-600 mt-2 block">Marks</label>
                                <input type="number" name="questions[0][marks]" value="1" min="0" step="0.5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54] text-sm">
                            </div>
                        </div>
                    </div>
                    <button type="button" id="addQuestion" class="mt-2 px-4 py-2 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition text-sm">
                        + Add Question
                    </button>
                </div>

                <!-- Target Categories -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Target Student Categories</label>
                    <select name="allowed_categories[]" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                        <option value="active">Active</option>
                        <option value="warned_once">Warned Once</option>
                        <option value="warned_twice">Warned Twice</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Leave empty for all students</p>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Starts At</label>
                        <input type="datetime-local" name="starts_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ends At</label>
                        <input type="datetime-local" name="ends_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="publish" value="1" class="rounded border-gray-300">
                        <span class="ml-2 text-sm text-gray-600">Publish immediately</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition">
                        Create Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let questionCount = 1;

        document.getElementById('addQuestion').addEventListener('click', function() {
            const container = document.getElementById('questionsContainer');
            const template = container.querySelector('.question-template');
            const clone = template.cloneNode(true);
            const index = questionCount++;

            // Update labels and names
            clone.querySelector('.font-medium').textContent = `Question ${index + 1}`;
            clone.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                    input.value = '';
                }
            });
            // Reset select
            clone.querySelector('.question-type').value = 'multiple_choice';

            container.appendChild(clone);
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-question') && document.querySelectorAll('.question-template').length > 1) {
                e.target.closest('.question-template').remove();
            }
        });
    </script>
</x-whatsapp-layout>