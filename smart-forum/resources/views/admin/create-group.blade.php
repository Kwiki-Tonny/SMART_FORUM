<x-whatsapp-layout>
    <x-slot name="sidebar">
        <!-- Sidebar content (same as dashboard) -->
    </x-slot>
    <div class="flex-1 overflow-y-auto bg-white p-6">
        <h2 class="text-2xl font-bold mb-4">Create New Group</h2>
        <form method="POST" action="{{ route('admin.groups.store') }}" class="max-w-md">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Group Name</label>
                <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E]">Create Group</button>
        </form>
    </div>
</x-whatsapp-layout>