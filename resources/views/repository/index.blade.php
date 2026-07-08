<x-app-layout title="Research Repository">
    <div class="repository-layout">
        <section class="repository-main">
            <div class="repo-count">Showing 1-6 of {{ $analytics['total'] }} documents</div>
            <div class="repo-grid">
                @foreach ($documents as $document)
                    <x-repository-document-card :document="$document" />
                @endforeach
            </div>
        </section>

        <aside class="analytics-panel">
            <div class="mini-stat-grid">
                <div><strong>{{ $analytics['total'] }}</strong><span>Total Docs</span></div>
                <div><strong>{{ $analytics['published'] }}</strong><span>Published</span></div>
                <div><strong>{{ $analytics['sdgs_covered'] }}</strong><span>SDGs Covered</span></div>
                <div><strong>{{ $analytics['ai_tagged'] }}</strong><span>AI Tagged</span></div>
            </div>

            <section class="card side-card">
                <h2>DOCS PER SDG (TOP 10)</h2>
                <div class="sdg-bars">
                    @foreach ($analytics['sdg_bars'] as [$sdg, $count, $color])
                        <div class="sdg-bar-row">
                            <span>SDG {{ $sdg }}</span>
                            <div><b style="width: {{ min(100, $count * 5) }}%; background: {{ $color }}"></b></div>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card side-card">
                <h2>BY DOCUMENT TYPE</h2>
                <div class="donut-wrap compact">
                    <div class="donut type-donut"></div>
                    <div class="legend-list">
                        <span><b style="background:#233F7F"></b>Research Study <strong>30</strong></span>
                        <span><b style="background:#7C3AED"></b>Terminal Report <strong>8</strong></span>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
