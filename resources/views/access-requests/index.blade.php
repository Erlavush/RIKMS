<x-app-layout title="Access Requests">
    <x-page-header title="Access Requests" subtitle="Review repository document access requests managed by your agency." />

    <section class="card recent-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Requester</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $accessRequest)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($accessRequest->document?->title, 58) }}</td>
                            <td>{{ $accessRequest->requester_name }}</td>
                            <td>{{ $accessRequest->requester_email }}</td>
                            <td><x-badge :tone="$accessRequest->status === 'pending' ? 'yellow' : ($accessRequest->status === 'approved' ? 'green' : 'red')">{{ ucfirst($accessRequest->status) }}</x-badge></td>
                            <td>{{ $accessRequest->created_at->format('Y-m-d') }}</td>
                            <td class="inline-actions">
                                <form method="POST" action="{{ route('access-requests.approve', $accessRequest) }}">
                                    @csrf
                                    <button class="btn-mini green" type="submit">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('access-requests.reject', $accessRequest) }}">
                                    @csrf
                                    <button class="btn-mini red" type="submit">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </section>
</x-app-layout>
