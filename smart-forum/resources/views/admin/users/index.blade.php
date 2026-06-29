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
        <a href="{{ route('admin.dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">User Management</span>
    </div>

    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-7xl mx-auto">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Filters & Actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
                    <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}" class="px-3 py-1.5 border rounded-md text-sm">
                    <select name="role" class="px-3 py-1.5 border rounded-md text-sm">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="lecturer" {{ request('role') == 'lecturer' ? 'selected' : '' }}>Lecturer</option>
                        <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
                    </select>
                    <select name="status" class="px-3 py-1.5 border rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="warned_once" {{ request('status') == 'warned_once' ? 'selected' : '' }}>Warned Once</option>
                        <option value="warned_twice" {{ request('status') == 'warned_twice' ? 'selected' : '' }}>Warned Twice</option>
                        <option value="blacklisted" {{ request('status') == 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                    </select>
                    <button type="submit" class="px-4 py-1.5 bg-[#075E54] text-white rounded-md hover:bg-[#128C7E] text-sm">Filter</button>
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-1.5 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm">Reset</a>
                </form>
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">+ Add User</a>
            </div>

            <!-- User Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $user->role === 'admin' ? 'bg-red-100 text-red-700' : ($user->role === 'lecturer' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : ($user->status === 'blacklisted' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ str_replace('_', ' ', ucfirst($user->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($user->is_approved)
                                        <span class="text-green-600">✓</span>
                                    @else
                                        <span class="text-red-600">✗</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-1">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:underline">Edit</a>
                                    @if(!$user->is_approved)
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:underline">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Reject this user?')">Reject</button>
                                        </form>
                                    @endif
                                    @if($user->role !== 'admin')
                                        <form method="POST" action="{{ route('admin.users.promote', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="role" value="admin">
                                            <button type="submit" class="text-red-600 hover:underline">Make Admin</button>
                                        </form>
                                    @endif
                                    @if($user->role !== 'lecturer' && $user->role !== 'admin')
                                        <form method="POST" action="{{ route('admin.users.promote', $user) }}" class="inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="role" value="lecturer">
                                            <button type="submit" class="text-blue-600 hover:underline">Make Lecturer</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this user?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $users->appends(request()->query())->links() }}
                </div>
            </div>

            <!-- Audit Log (latest 10) -->
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-700">Recent Audit Log</h3>
                </div>
                <div class="p-4 max-h-48 overflow-y-auto">
                    @php
                        $logs = \App\Models\AuditLog::with('user')->latest()->limit(10)->get();
                    @endphp
                    @if($logs->count() > 0)
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr><th class="text-left">User</th><th class="text-left">Action</th><th class="text-left">Details</th><th class="text-left">Time</th></tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr>
                                        <td>{{ $log->user?->name ?? 'System' }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ $log->details }}</td>
                                        <td class="text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-400 text-center py-2">No logs yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-whatsapp-layout>