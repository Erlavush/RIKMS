<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function placeholder(string $page)
    {
        $titles = [
            'archive' => 'Archive',
            'analytics' => 'Analytics',
            'notifications' => 'Notifications',
            'agency-profile' => 'Agency Profile',
            'settings' => 'Settings',
        ];

        abort_unless(isset($titles[$page]), 404);

        return view('placeholder', [
            'title' => $titles[$page],
            'message' => 'This module is prepared for the next prototype iteration.',
        ]);
    }
}
