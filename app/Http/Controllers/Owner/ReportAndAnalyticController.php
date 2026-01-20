<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ReportAndAnalyticController extends Controller
{
    public function index()
    {
        return Inertia::render('owner/reports-and-analytics/Index');
    }
}
