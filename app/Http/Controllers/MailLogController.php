<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\View\View;

class MailLogController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MailLog::class);

        return view('mail.logs.index', [
            'logs' => MailLog::query()->with('template')->latest('id')->paginate(20),
        ]);
    }
}
