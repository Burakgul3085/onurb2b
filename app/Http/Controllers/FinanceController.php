<?php

namespace App\Http\Controllers;

use App\Actions\Finance\RecordCollection;
use App\Actions\Finance\RecordOrderReturn;
use App\Actions\Finance\ReverseLedgerEntry;
use App\Enums\DealerApplicationStatus;
use App\Enums\LedgerType;
use App\Enums\PaymentMethod;
use App\Exceptions\FinanceException;
use App\Http\Requests\Finance\RecordCollectionRequest;
use App\Http\Requests\Finance\RecordOrderReturnRequest;
use App\Http\Requests\Finance\ReverseLedgerEntryRequest;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Policies\LedgerEntryPolicy;
use App\Support\Finance\LedgerBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, LedgerBalance $balances): View|RedirectResponse
    {
        $this->authorize('viewAny', LedgerEntry::class);
        $actor = $request->user();

        if ($actor->dealer_id !== null) {
            return redirect()->route('finance.show', $actor->dealer_id);
        }

        $search = (string) $request->string('q');
        $dealers = Dealer::query()
            ->where(function ($query) {
                $query->where('application_status', DealerApplicationStatus::Approved)
                    ->orWhereHas('entries');
            })
            ->when($search !== '', fn ($query) => $query->where('company_name', 'like', '%'.$search.'%'))
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        return view('finance.index', [
            'dealers' => $dealers,
            'balances' => $balances->forDealers($dealers->pluck('id')),
            'search' => $search,
            'dues' => LedgerEntry::query()
                ->with('dealer')
                ->where('type', LedgerType::Sale)
                ->whereDate('due_on', '<=', now()->addDays(7)->toDateString())
                ->whereDoesntHave('reversal')
                ->orderBy('due_on')
                ->limit(20)
                ->get(),
        ]);
    }

    public function show(Request $request, Dealer $dealer, LedgerBalance $balances): View
    {
        $policy = app(LedgerEntryPolicy::class);
        abort_unless($policy->viewDealer($request->user(), $dealer), $policy->deniedStatus($request->user(), $dealer));

        $entries = LedgerEntry::query()
            ->with(['order', 'reversal'])
            ->where('dealer_id', $dealer->id)
            ->latest('document_date')
            ->latest('id')
            ->paginate(20);

        return view('finance.show', [
            'dealer' => $dealer,
            'entries' => $entries,
            'balance' => $balances->forDealer($dealer->id),
            'methods' => PaymentMethod::cases(),
            'canCollect' => $policy->collect($request->user()),
        ]);
    }

    public function collect(RecordCollectionRequest $request, Dealer $dealer, RecordCollection $recordCollection): RedirectResponse
    {
        try {
            $recordCollection->execute(
                $dealer,
                $request->string('amount')->toString(),
                PaymentMethod::from($request->string('method')->toString()),
                $request->date('document_date')->toDateString(),
                $request->string('note')->toString(),
                $request->user(),
            );
        } catch (FinanceException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('finance.show', $dealer)->with('status', __('Collection recorded.'));
    }

    public function reverse(ReverseLedgerEntryRequest $request, LedgerEntry $entry, ReverseLedgerEntry $reverseLedgerEntry): RedirectResponse
    {
        try {
            $reverseLedgerEntry->execute($entry, $request->string('note')->toString(), $request->user());
        } catch (FinanceException $exception) {
            return back()->withErrors(['ledger' => $exception->getMessage()]);
        }

        return redirect()->route('finance.show', $entry->dealer_id)->with('status', __('Entry reversed.'));
    }

    public function storeReturn(RecordOrderReturnRequest $request, Order $order, RecordOrderReturn $recordOrderReturn): RedirectResponse
    {
        $pieces = [];

        foreach ($request->input('pieces', []) as $lineId => $quantity) {
            $pieces[(int) $lineId] = (int) $quantity;
        }

        try {
            $recordOrderReturn->execute(
                $order,
                $pieces,
                $request->date('document_date')->toDateString(),
                $request->string('note')->toString(),
                $request->user(),
            );
        } catch (FinanceException $exception) {
            return back()->withInput()->withErrors(['pieces' => $exception->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', __('Return recorded.'));
    }
}
