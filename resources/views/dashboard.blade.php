<x-app-layout title="Dashboard">
    <x-page-header
        title="Agency Research Dashboard"
        subtitle="Manage and publish research studies contributed by your institution."
    />

    <div class="header-actions">
        <a class="btn-primary" href="{{ route('upload.new') }}">+ Upload New Research</a>
        <a class="btn-secondary" href="{{ route('repository') }}"><x-icon name="archive" /> Manage Research</a>
        <a class="btn-secondary" href="{{ route('access-requests.index') }}"><x-icon name="inbox" /> View Access Requests</a>
    </div>

    <section class="stats-grid">
        @foreach ($stats as $stat)
            <x-stat-card :value="$stat['value']" :label="$stat['label']" :icon="$stat['icon']" :tone="$loop->index === 1 ? 'orange' : ($loop->index === 2 ? 'green' : 'blue')" />
        @endforeach
    </section>

    <section class="dashboard-charts">
        <x-chart-card title="Research by Year">
            <div class="bar-chart">
                <div class="bar-item"><div class="bar small"></div><span>2024</span></div>
                <div class="bar-item"><div class="bar medium"></div><span>2025</span></div>
                <div class="bar-item"><div class="bar tall"></div><span>2026</span></div>
            </div>
        </x-chart-card>

        <x-chart-card title="Research by Category">
            <div class="donut-wrap">
                <div class="donut category-donut"></div>
                <div class="legend-row">
                    <span><b style="background:#233F7F"></b>Uncategorized</span>
                    <span><b style="background:#16A34A"></b>Terminal Report</span>
                    <span><b style="background:#F97316"></b>Sustainable Energy</span>
                    <span><b style="background:#93A4B8"></b>Digital Economy</span>
                </div>
            </div>
        </x-chart-card>
    </section>

    <section class="card recent-card">
        <div class="card-title-row">
            <h2>Recent Research Uploads</h2>
            <a href="{{ route('repository') }}">View All -></a>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Authors</th>
                        <th>Year</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentDocuments as $document)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($document->title, 54) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(implode(', ', $document->metadata?->authors ?? []), 58) }}</td>
                            <td>{{ $document->year }}</td>
                            <td>{{ $document->category }}</td>
                            <td><x-badge tone="yellow">{{ $document->statusLabel() }}</x-badge></td>
                            <td>{{ $document->updated_at->format('Y-m-d') }}</td>
                            <td class="table-actions">
                                <a href="{{ route('documents.show', $document) }}" aria-label="View"><x-icon name="eye" /></a>
                                <a href="{{ route('upload.step', [$document, 3]) }}" aria-label="Edit"><x-icon name="edit" /></a>
                                <form method="POST" action="{{ route('documents.destroy', $document) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Archive"><x-icon name="trash" /></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
