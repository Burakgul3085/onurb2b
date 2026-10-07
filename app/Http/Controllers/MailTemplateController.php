<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mail\UpdateMailTemplateRequest;
use App\Models\MailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MailTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MailTemplate::class);

        return view('mail.templates.index', [
            'templates' => MailTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(MailTemplate $template): View
    {
        $this->authorize('update', $template);

        return view('mail.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateMailTemplateRequest $request, MailTemplate $template): RedirectResponse
    {
        $template->update([
            'subject' => $request->string('subject')->toString(),
            'body' => $request->string('body')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('mail-templates.index')->with('status', __('Template saved.'));
    }
}
