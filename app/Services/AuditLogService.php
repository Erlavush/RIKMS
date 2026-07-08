<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(string $action, ?Document $document = null, array $details = [], ?Request $request = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'document_id' => $document?->id,
            'action' => $action,
            'details' => $details ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
