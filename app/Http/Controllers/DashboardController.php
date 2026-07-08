<?php

namespace App\Http\Controllers;

use App\Models\Document;

class DashboardController extends Controller
{
    public function index()
    {
        $recentDocuments = Document::with(['metadata', 'sdgTags'])
            ->latest('updated_at')
            ->take(3)
            ->get();

        return view('dashboard', [
            'stats' => [
                ['value' => 37, 'label' => 'Total Research', 'icon' => 'file'],
                ['value' => 14, 'label' => 'Draft Research', 'icon' => 'edit'],
                ['value' => 13, 'label' => 'Published', 'icon' => 'check'],
                ['value' => 2, 'label' => 'Pending Requests', 'icon' => 'inbox'],
            ],
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
