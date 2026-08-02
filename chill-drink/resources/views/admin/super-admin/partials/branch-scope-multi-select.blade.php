@php
    $controlId = $controlId ?? 'branch-scope-select';
    $inputName = $inputName ?? 'analytics_branch_ids';
    $branches = $branches ?? collect();
    $selectedIds = collect($selectedIds ?? [])
        ->filter(static fn ($value) => $value !== null && $value !== '' && is_numeric($value))
        ->map(static fn ($value) => (int) $value)
        ->unique()
        ->values();
    $summaryLabel = $summaryLabel ?? 'Tất cả chi nhánh';
    $placeholder = $placeholder ?? 'Tìm chi nhánh...';
    $showChips = $showChips ?? true;
@endphp

<div
    class="sa-branch-scope"
    data-branch-scope
    data-control-id="{{ $controlId }}"
    data-input-name="{{ $inputName }}"
    data-empty-label="Tất cả chi nhánh"
>
    <div class="sa-branch-scope-trigger-wrap">
        <button type="button" class="sa-control sa-branch-scope-trigger" data-branch-scope-trigger aria-expanded="false" aria-controls="{{ $controlId }}-panel">
            <span class="sa-branch-scope-trigger-text" data-branch-scope-trigger-text>{{ $summaryLabel }}</span>
            <span class="sa-branch-scope-trigger-count" data-branch-scope-trigger-count>{{ $selectedIds->count() > 0 ? $selectedIds->count() : '∞' }}</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>

    <div class="sa-branch-scope-panel" id="{{ $controlId }}-panel" data-branch-scope-panel hidden>
        <div class="sa-branch-scope-panel-top">
            <div>
                <div class="sa-branch-scope-panel-title">Chọn chi nhánh</div>
                <div class="sa-branch-scope-panel-note" data-branch-scope-summary>{{ $summaryLabel }}</div>
            </div>
            <button type="button" class="sa-branch-scope-close" data-branch-scope-close aria-label="Đóng"><i class="bi bi-x"></i></button>
        </div>

        <div class="sa-branch-scope-searchbar">
            <i class="bi bi-search"></i>
            <input type="search" class="sa-branch-scope-search" data-branch-scope-search placeholder="{{ $placeholder }}" autocomplete="off">
        </div>

        <div class="sa-branch-scope-actions">
            <button type="button" class="sa-btn sa-btn-soft" data-branch-scope-select-all>Chọn tất cả</button>
            <button type="button" class="sa-btn sa-btn-soft" data-branch-scope-clear>Clear</button>
        </div>

        <div class="sa-branch-scope-list" data-branch-scope-list>
            @forelse($branches as $branch)
                @php
                    $branchId = (int) $branch->id;
                    $branchLabel = trim((string) $branch->name);
                    $branchMeta = trim(implode(' · ', array_filter([
                        filled($branch->code ?? null) ? (string) $branch->code : null,
                        filled($branch->address ?? null) ? (string) $branch->address : null,
                    ])));
                @endphp
                <label class="sa-branch-scope-option" data-branch-scope-option data-branch-scope-search-text="{{ mb_strtolower($branchLabel.' '.$branchMeta) }}">
                    <input type="checkbox" value="{{ $branchId }}" @checked($selectedIds->contains($branchId)) data-branch-scope-checkbox>
                    <span class="sa-branch-scope-option-body">
                        <span class="sa-branch-scope-option-title">{{ $branchLabel }}</span>
                        @if($branchMeta !== '')
                            <span class="sa-branch-scope-option-meta">{{ $branchMeta }}</span>
                        @endif
                    </span>
                </label>
            @empty
                <div class="sa-branch-scope-empty">Không có chi nhánh để chọn.</div>
            @endforelse
            @if($branches->isNotEmpty())
                <div class="sa-branch-scope-empty" data-branch-scope-empty-state hidden>Không tìm thấy chi nhánh phù hợp.</div>
            @endif
        </div>

        <div class="sa-branch-scope-footer">
            <button type="button" class="sa-btn" data-branch-scope-close>Đóng</button>
        </div>
    </div>

    @if($showChips)
        <div class="sa-branch-scope-chips" data-branch-scope-chips>
            @forelse($selectedIds as $selectedId)
                @php
                    $branch = $branches->firstWhere('id', $selectedId);
                @endphp
                <span class="sa-branch-scope-chip" data-branch-scope-chip="{{ $selectedId }}">{{ $branch?->name ?? ('Chi nhánh #'.$selectedId) }}</span>
            @empty
                <span class="sa-branch-scope-chip muted" data-branch-scope-chip="empty">Tất cả chi nhánh</span>
            @endforelse
        </div>
    @endif

    @if($selectedIds->count() > 1)
        <span class="d-none" data-branch-scope-selected-label>Đã chọn {{ $selectedIds->count() }} chi nhánh</span>
    @endif

    <div class="d-none" data-branch-scope-hidden>
        @foreach($selectedIds as $selectedId)
            <input type="hidden" name="{{ $inputName }}[]" value="{{ $selectedId }}">
        @endforeach
    </div>
</div>
