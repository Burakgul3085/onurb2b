<?php

namespace App\Http\Controllers;

use App\Actions\Dealers\ApproveDealer;
use App\Actions\Dealers\CreateDealer;
use App\Actions\Dealers\RejectDealer;
use App\Actions\Dealers\UpdateDealer;
use App\Actions\Dealers\UpdateDealerNotificationEmail;
use App\Enums\DealerApplicationStatus;
use App\Enums\District;
use App\Http\Requests\Dealers\ApproveDealerRequest;
use App\Http\Requests\Dealers\RejectDealerRequest;
use App\Http\Requests\Dealers\StoreDealerRequest;
use App\Http\Requests\Dealers\UpdateDealerNotificationEmailRequest;
use App\Http\Requests\Dealers\UpdateDealerRequest;
use App\Models\Dealer;
use App\Models\PriceList;
use App\Support\Authorization\DealerScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Dealer::class);

        $search = trim((string) $request->string('search'));
        $like = '%'.addcslashes($search, '%_\\').'%';
        $status = DealerApplicationStatus::tryFrom($request->string('status')->toString());

        $dealers = DealerScope::restrict(Dealer::query(), $request->user(), 'id')
            ->when($search !== '', function ($query) use ($like) {
                $query->where(function ($query) use ($like) {
                    $query->where('company_name', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('tax_number', 'like', $like);
                });
            })
            ->when($status !== null, fn ($query) => $query->where('application_status', $status))
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        return view('dealers.index', [
            'dealers' => $dealers,
            'search' => $search,
            'status' => $status?->value ?? '',
            'statuses' => DealerApplicationStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Dealer::class);

        return view('dealers.create', [
            'districts' => District::cases(),
            'priceLists' => PriceList::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDealerRequest $request, CreateDealer $createDealer): RedirectResponse
    {
        $dealer = $createDealer->execute($request->validated());

        return redirect()
            ->route('dealers.show', $dealer)
            ->with('status', __('Dealer created.'));
    }

    public function show(Dealer $dealer): View
    {
        $this->authorize('view', $dealer);

        $dealer->load(['users', 'priceList']);

        return view('dealers.show', [
            'dealer' => $dealer,
        ]);
    }

    public function edit(Dealer $dealer): View
    {
        $this->authorize('update', $dealer);

        return view('dealers.edit', [
            'dealer' => $dealer,
            'districts' => District::cases(),
            'priceLists' => PriceList::query()
                ->where(function ($query) use ($dealer) {
                    $query->where('is_active', true);

                    if ($dealer->price_list_id !== null) {
                        $query->orWhere('id', $dealer->price_list_id);
                    }
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateDealerRequest $request, Dealer $dealer, UpdateDealer $updateDealer): RedirectResponse
    {
        $updateDealer->execute($dealer, $request->validated());

        return redirect()
            ->route('dealers.show', $dealer)
            ->with('status', __('Dealer updated.'));
    }

    public function updateNotificationEmail(UpdateDealerNotificationEmailRequest $request, Dealer $dealer, UpdateDealerNotificationEmail $update): RedirectResponse
    {
        $update->execute($dealer, $request->user(), $request->string('email')->toString());

        return redirect()
            ->route('dealers.show', $dealer)
            ->with('status', __('Notification email saved.'));
    }

    public function approve(ApproveDealerRequest $request, Dealer $dealer, ApproveDealer $approveDealer): RedirectResponse
    {
        $approveDealer->execute($request->user(), $dealer, $request->string('password')->toString());

        return redirect()
            ->route('dealers.show', $dealer)
            ->with('status', __('Dealer approved.'));
    }

    public function reject(RejectDealerRequest $request, Dealer $dealer, RejectDealer $rejectDealer): RedirectResponse
    {
        $rejectDealer->execute($dealer, $request->string('rejection_reason')->toString());

        return redirect()
            ->route('dealers.show', $dealer)
            ->with('status', __('Dealer rejected.'));
    }
}
