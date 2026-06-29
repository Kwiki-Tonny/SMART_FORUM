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

    <!-- ===== HEADER ===== -->
    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Group Analytics</span>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-6xl mx-auto">

            @if($groups->isEmpty())
                <div class="text-center text-gray-400 py-12">
                    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="mt-2">No groups available to analyze.</p>
                    <p class="text-sm">Join or create a group to see statistics.</p>
                </div>
            @else
                <!-- Group Selector -->
                <div class="mb-6">
                    <form method="GET" action="{{ route('analytics') }}" class="flex items-center gap-3">
                        <label for="group_id" class="text-sm font-medium text-gray-700">Select Group:</label>
                        <select name="group_id" id="group_id" class="rounded-md border-gray-300 shadow-sm focus:border-[#075E54] focus:ring-[#075E54]">
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" {{ $selectedGroup->id == $g->id ? 'selected' : '' }}>
                                    {{ $g->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3 py-1.5 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition text-sm">
                            Refresh
                        </button>
                    </form>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-[#075E54]">{{ $stats['total_topics'] }}</div>
                        <div class="text-xs text-gray-500">Topics</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-[#075E54]">{{ $stats['total_posts'] }}</div>
                        <div class="text-xs text-gray-500">Posts</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-green-600">{{ $stats['total_likes'] }}</div>
                        <div class="text-xs text-gray-500">Likes</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-blue-600">{{ $stats['total_views'] }}</div>
                        <div class="text-xs text-gray-500">Views</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-purple-600">{{ $stats['total_downloads'] }}</div>
                        <div class="text-xs text-gray-500">Downloads</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-2xl font-bold text-yellow-600">{{ $stats['total_comments'] }}</div>
                        <div class="text-xs text-gray-500">Comments</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Message Volume per Topic (Bar Chart) -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="font-semibold text-gray-700 mb-3">Top Topics by Replies</h3>
                        <canvas id="topicsChart" height="200"></canvas>
                    </div>

                    <!-- Daily Activity (Line Chart) -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="font-semibold text-gray-700 mb-3">Daily Activity (Last 7 Days)</h3>
                        <canvas id="activityChart" height="200"></canvas>
                    </div>
                </div>

                <!-- Engagement by Category (Pie Chart) -->
                @if($categoryData->count() > 0)
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mt-6">
                        <h3 class="font-semibold text-gray-700 mb-3">Topics by Category</h3>
                        <canvas id="categoryChart" height="180"></canvas>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- ===== Chart.js ===== -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($groups->isNotEmpty())

                // ---- Topics Bar Chart ----
                const topicsCtx = document.getElementById('topicsChart').getContext('2d');
                const topicsData = @json($topicsData);
                new Chart(topicsCtx, {
                    type: 'bar',
                    data: {
                        labels: topicsData.map(t => t.title.length > 20 ? t.title.substring(0,20)+'...' : t.title),
                        datasets: [{
                            label: 'Posts',
                            data: topicsData.map(t => t.posts),
                            backgroundColor: 'rgba(7, 94, 84, 0.6)',
                            borderColor: '#075E54',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });

                // ---- Daily Activity Line Chart ----
                const activityCtx = document.getElementById('activityChart').getContext('2d');
                const activityData = @json($dailyActivity);
                const dates = Object.keys(activityData);
                const counts = Object.values(activityData);
                new Chart(activityCtx, {
                    type: 'line',
                    data: {
                        labels: dates.map(d => new Date(d).toLocaleDateString()),
                        datasets: [{
                            label: 'Posts',
                            data: counts,
                            borderColor: '#075E54',
                            backgroundColor: 'rgba(7, 94, 84, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });

                // ---- Category Pie Chart ----
                @if($categoryData->count() > 0)
                    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
                    const categoryData = @json($categoryData);
                    const colors = ['#075E54', '#128C7E', '#25D366', '#34B7F1', '#DCF8C6', '#FFD700', '#FF6B6B', '#845EC2'];
                    new Chart(categoryCtx, {
                        type: 'pie',
                        data: {
                            labels: categoryData.map(c => c.category),
                            datasets: [{
                                data: categoryData.map(c => c.count),
                                backgroundColor: colors.slice(0, categoryData.length)
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                @endif

            @endif
        });
    </script>
</x-whatsapp-layout>