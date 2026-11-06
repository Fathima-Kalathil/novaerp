<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AuditLogs/Index', [
            'logs' => AuditLog::with('user:id,name')->latest('id')->paginate(15),
        ]);
    }
}
