<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccessRequestController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index()
    {
        $requests = AccessRequest::with(['document', 'requester'])
            ->latest()
            ->paginate(12);

        return view('access-requests.index', ['requests' => $requests]);
    }

    public function approve(Request $request, AccessRequest $accessRequest)
    {
        $this->authorize('update', $accessRequest->document);

        $accessRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        $this->audit->log('access request approved', $accessRequest->document, ['access_request_id' => $accessRequest->id], $request);

        return back()->with('status', 'Access request approved.');
    }

    public function reject(Request $request, AccessRequest $accessRequest)
    {
        $this->authorize('update', $accessRequest->document);

        $accessRequest->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_at' => now(),
        ]);

        $this->audit->log('access request rejected', $accessRequest->document, ['access_request_id' => $accessRequest->id], $request);

        return back()->with('status', 'Access request rejected.');
    }
}
