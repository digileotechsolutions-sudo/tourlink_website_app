<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminDashboardMetrics;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(AdminDashboardMetrics $metrics): View
    {
        return view('admin.reports.index', $metrics->build());
    }
}
