<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with(['user', 'course'])
            ->orderByDesc('issued_at')
            ->paginate(20);

        return view('admin.certificates', compact('certificates'));
    }
}
