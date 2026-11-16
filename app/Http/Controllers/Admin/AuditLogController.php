<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $action = trim((string) $request->query('action'));

        return view('admin.audit', [
            'action' => $action,
            'entries' => AuditLog::with('user')
                ->when($action !== '', fn ($q) => $q->where('action', 'like', "{$action}%"))
                ->latest('id')->paginate(30)->withQueryString(),
        ]);
    }
}
