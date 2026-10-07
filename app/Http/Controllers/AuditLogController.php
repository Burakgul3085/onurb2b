<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\Audit\ShowAuditLogRequest;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(ShowAuditLogRequest $request): View
    {
        $action = $request->enum('action', AuditAction::class);

        return view('audit.index', [
            'logs' => AuditLog::query()
                ->with('user')
                ->when($action, fn ($query) => $query->where('action', $action))
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'actions' => AuditAction::cases(),
            'selected' => $action?->value,
        ]);
    }
}
