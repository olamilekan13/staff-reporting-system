<?php

namespace App\Http\Controllers\Web\Hod;

use App\Http\Controllers\Controller;

class DepartmentController extends Controller
{
    public function show()
    {
        return view('hod.department');
    }
}
