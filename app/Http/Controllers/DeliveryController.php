<?php

namespace App\Http\Controllers;

use App\Actions\Deliveries\CancelDeliveryPlan;
use App\Actions\Deliveries\CompleteDelivery;
use App\Actions\Deliveries\DispatchDelivery;
use App\Actions\Deliveries\FailDelivery;
use App\Actions\Deliveries\ScheduleDelivery;
use App\Enums\Role;
use App\Exceptions\DeliveryException;
use App\Http\Requests\Deliveries\CompleteDeliveryRequest;
use App\Http\Requests\Deliveries\DispatchDeliveryRequest;
use App\Http\Requests\Deliveries\FailDeliveryRequest;
use App\Http\Requests\Deliveries\ScheduleDeliveryRequest;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Delivery::class);

        $date = (string) $request->string('date');
        $actor = $request->user();
        $seesAll = $actor->can('seesAll', Delivery::class);

        $deliveries = Delivery::query()
            ->with(['dealer', 'driver', 'order'])
            ->when(! $seesAll, fn ($query) => $query->where('user_id', $actor->id))
            ->when($date !== '', fn ($query) => $query->whereDate('scheduled_on', $date))
            ->orderBy('scheduled_on')
            ->orderBy('sequence')
            ->paginate(15)
            ->withQueryString();

        return view('deliveries.index', [
            'deliveries' => $deliveries,
            'date' => $date,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Delivery::class);

        $order = Order::query()->with(['lines', 'dealer'])->findOrFail($request->integer('order'));
        abort_unless($request->user()->can('view', $order), 404);

        return view('deliveries.create', [
            'order' => $order,
            'drivers' => User::role(Role::Delivery->value)->where('is_active', true)->whereNull('dealer_id')->orderBy('name')->get(),
        ]);
    }

    public function store(ScheduleDeliveryRequest $request, ScheduleDelivery $scheduleDelivery): RedirectResponse
    {
        $order = Order::query()->findOrFail($request->integer('order_id'));

        try {
            $delivery = $scheduleDelivery->execute($order, $request->safe()->only(['user_id', 'sequence', 'scheduled_on', 'scheduled_time']));
        } catch (DeliveryException $exception) {
            return back()->withInput()->withErrors(['delivery' => $exception->getMessage()]);
        }

        return redirect()->route('deliveries.show', $delivery)->with('status', __('Delivery planned.'));
    }

    public function show(Request $request, Delivery $delivery): View
    {
        abort_unless($request->user()?->can('view', $delivery), 404);
        $delivery->load(['lines.orderLine', 'order', 'dealer', 'driver']);

        return view('deliveries.show', [
            'delivery' => $delivery,
            'canUpdate' => $request->user()->can('update', $delivery),
        ]);
    }

    public function dispatch(DispatchDeliveryRequest $request, Delivery $delivery, DispatchDelivery $dispatchDelivery): RedirectResponse
    {
        $shipped = [];

        foreach ($request->input('shipped', []) as $lineId => $pieces) {
            $shipped[(int) $lineId] = (int) $pieces;
        }

        try {
            $dispatchDelivery->execute($delivery, $shipped, $request->user());
        } catch (DeliveryException $exception) {
            return back()->withInput()->withErrors(['shipped' => $exception->getMessage()]);
        }

        return redirect()->route('deliveries.show', $delivery)->with('status', __('Delivery left the warehouse.'));
    }

    public function complete(CompleteDeliveryRequest $request, Delivery $delivery, CompleteDelivery $completeDelivery): RedirectResponse
    {
        $delivered = [];

        foreach ($request->input('delivered', []) as $lineId => $pieces) {
            $delivered[(int) $lineId] = (int) $pieces;
        }

        try {
            $completeDelivery->execute(
                $delivery,
                $delivered,
                $request->safe()->only(['recipient_name', 'note']),
                $request->user(),
                $request->file('proof'),
            );
        } catch (DeliveryException $exception) {
            return back()->withInput()->withErrors(['delivered' => $exception->getMessage()]);
        }

        return redirect()->route('deliveries.show', $delivery)->with('status', __('Delivery completed.'));
    }

    public function fail(FailDeliveryRequest $request, Delivery $delivery, FailDelivery $failDelivery): RedirectResponse
    {
        try {
            $failDelivery->execute($delivery, $request->string('note')->toString(), $request->user(), $request->file('proof'));
        } catch (DeliveryException $exception) {
            return back()->withInput()->withErrors(['note' => $exception->getMessage()]);
        }

        return redirect()->route('deliveries.show', $delivery)->with('status', __('Delivery failed.'));
    }

    public function destroy(Request $request, Delivery $delivery, CancelDeliveryPlan $cancelDeliveryPlan): RedirectResponse
    {
        abort_unless($request->user()?->can('update', $delivery), 403);

        try {
            $orderId = $delivery->order_id;
            $cancelDeliveryPlan->execute($delivery);
        } catch (DeliveryException $exception) {
            return back()->withErrors(['delivery' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $orderId)->with('status', __('Delivery plan removed.'));
    }
}
