<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Reports') }}</h2>
            @if ($query->type)
                <p class="mt-1 text-sm text-ink-muted">{{ $query->type->hint() }}</p>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <form method="GET" action="{{ route('reports.index') }}" class="card grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="type" :value="__('Report')" />
                <select id="type" name="type" class="field mt-1" required>
                    <option value="">{{ __('Report') }}</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected($query->type === $type)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="period" :value="__('Date')" />
                <select id="period" name="period" class="field mt-1">
                    @foreach ($periods as $period)
                        <option value="{{ $period->value }}" @selected($query->period === $period)>{{ $period->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="from" :value="__('Start date')" />
                <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$query->from" />
            </div>
            <div>
                <x-input-label for="to" :value="__('End date')" />
                <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$query->to" />
            </div>
            <div>
                <x-input-label for="dealer_id" :value="__('Dealer')" />
                <select id="dealer_id" name="dealer_id" class="field mt-1">
                    <option value="">{{ __('Dealer') }}</option>
                    @foreach ($dealers as $dealer)
                        <option value="{{ $dealer->id }}" @selected($query->dealerId === $dealer->id)>{{ $dealer->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="product_id" :value="__('Product')" />
                <select id="product_id" name="product_id" class="field mt-1">
                    <option value="">{{ __('Product') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($query->productId === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="category_id" :value="__('Category')" />
                <select id="category_id" name="category_id" class="field mt-1">
                    <option value="">{{ __('Category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($query->categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="brand_id" :value="__('Brand')" />
                <select id="brand_id" name="brand_id" class="field mt-1">
                    <option value="">{{ __('Brand') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($query->brandId === $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="field mt-1">
                    <option value="">{{ __('Status') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($query->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-primary-button>{{ __('Show report') }}</x-primary-button>
            </div>
            <x-input-error class="sm:col-span-2 lg:col-span-4" :messages="$errors->all()" />
        </form>

        @if ($tooLarge)
            <div class="card space-y-3 p-6 text-sm">
                <p>{{ __('This report is large. Generate it from the queue.') }}</p>
                <div class="flex gap-4">
                    <a class="link" href="{{ route('reports.index', array_merge($query->parameters(), ['format' => 'xlsx'])) }}">Excel</a>
                    <a class="link" href="{{ route('reports.index', array_merge($query->parameters(), ['format' => 'pdf'])) }}">PDF</a>
                </div>
            </div>
        @endif

        @if ($result)
            <div class="card space-y-4 p-6">
                <div class="flex gap-4 text-sm">
                    <a class="link" href="{{ route('reports.index', array_merge($query->parameters(), ['format' => 'xlsx'])) }}">Excel</a>
                    <a class="link" href="{{ route('reports.index', array_merge($query->parameters(), ['format' => 'pdf'])) }}">PDF</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                @foreach ($result->columns as $column)
                                    <th>{{ $column['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    @foreach ($result->columns as $column)
                                        <td @class(['text-right' => in_array($column['key'], $result->moneyKeys, true)])>{{ $row[$column['key']] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($result->columns) }}" class="text-ink-muted">{{ __('No report rows.') }}</td>
                                </tr>
                            @endforelse
                            @if ($result->totals !== [])
                                <tr>
                                    @foreach ($result->columns as $index => $column)
                                        <td @class(['text-right font-semibold' => in_array($column['key'], $result->moneyKeys, true), 'font-semibold' => $index === 0])>
                                            @if ($index === 0 && ! array_key_exists($column['key'], $result->totals))
                                                Toplam
                                            @else
                                                {{ $result->totals[$column['key']] ?? '' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div>{{ $paginator->links() }}</div>
            </div>
        @endif

        <div class="card overflow-x-auto p-6">
            <h3 class="mb-4 font-semibold text-ink">{{ __('Queued reports') }}</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Report') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exports as $export)
                        <tr>
                            <td>{{ $export->type->label() }} · {{ strtoupper($export->format->value) }}</td>
                            <td><x-badge :tone="$export->status->tone()">{{ $export->status->label() }}</x-badge></td>
                            <td>{{ $export->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                @if ($export->status === \App\Enums\ReportExportStatus::Ready)
                                    <a class="link" href="{{ route('reports.exports.show', $export) }}">{{ __('Download report') }}</a>
                                @elseif ($export->error)
                                    <span class="text-ink-muted">{{ $export->error }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-ink-muted">{{ __('No report rows.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
