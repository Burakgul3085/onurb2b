<?php

namespace App\Http\Controllers;

use App\Actions\Messages\PostMessage;
use App\Enums\DealerApplicationStatus;
use App\Http\Requests\Messages\ReplyMessageRequest;
use App\Http\Requests\Messages\StoreMessageRequest;
use App\Models\Dealer;
use App\Models\MessageThread;
use App\Models\Order;
use App\Policies\MessageThreadPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MessageThread::class);

        $actor = $request->user();
        $threads = MessageThread::query()
            ->with(['dealer', 'order'])
            ->withMax('messages as last_message_at', 'created_at')
            ->when($actor->dealer_id !== null, fn ($query) => $query->where('dealer_id', $actor->dealer_id))
            ->latest('id')
            ->paginate(15);

        return view('messages.index', [
            'threads' => $threads,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MessageThread::class);

        $order = null;

        if ($request->filled('order')) {
            $order = Order::query()->find($request->integer('order'));
            abort_if($order === null, 404);
            abort_unless($request->user()->can('view', $order), 404);
        }

        $actor = $request->user();
        $dealers = $actor->dealer_id === null
            ? Dealer::query()->where('application_status', DealerApplicationStatus::Approved)->orderBy('company_name')->get()
            : collect();

        return view('messages.create', [
            'dealers' => $dealers,
            'order' => $order,
        ]);
    }

    public function store(StoreMessageRequest $request, PostMessage $postMessage): RedirectResponse
    {
        $actor = $request->user();
        $dealerId = $actor->dealer_id ?? $request->integer('dealer_id');
        $orderId = $request->filled('order_id') ? $request->integer('order_id') : null;

        $thread = $postMessage->open($actor, $dealerId, $orderId, $request->string('subject')->toString(), $request->string('body')->toString());

        return redirect()->route('messages.show', $thread)->with('status', __('Message sent.'));
    }

    public function show(Request $request, MessageThread $thread): View
    {
        $policy = app(MessageThreadPolicy::class);
        abort_unless($policy->view($request->user(), $thread), $policy->deniedStatus($request->user(), $thread));

        $thread->load(['dealer', 'order', 'messages.user']);

        return view('messages.show', [
            'thread' => $thread,
            'canReply' => $request->user()->can('reply', $thread),
        ]);
    }

    public function reply(ReplyMessageRequest $request, MessageThread $thread, PostMessage $postMessage): RedirectResponse
    {
        $postMessage->reply($request->user(), $thread, $request->string('body')->toString());

        return redirect()->route('messages.show', $thread)->with('status', __('Message sent.'));
    }
}
