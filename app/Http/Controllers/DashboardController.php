<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $connections = DatabaseConnection::with([
            'schedules' => fn ($q) => $q->orderBy('frequency_hours'),
        ])->orderBy('alias')->get();

        return view('dashboard.index', compact('connections'));
    }
}
