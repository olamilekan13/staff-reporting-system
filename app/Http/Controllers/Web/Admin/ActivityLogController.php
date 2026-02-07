<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;

class ActivityLogController extends Controller
{
    public function index()
    {
        return view('admin.activity-logs.index');
    }
}
