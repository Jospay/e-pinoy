<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportAndAnalyticController extends Controller
{
     public function index()
    {
        return Inertia::render('manager/reports-and-analytics/Index');
    }
}
