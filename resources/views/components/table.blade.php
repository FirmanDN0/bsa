@php
    $tableId = $tableId ?? 'table';
    $title = $title ?? 'Data';
    $addLabel = $addLabel ?? 'Tambah Data';
    $searchPlaceholder = $searchPlaceholder ?? 'Cari data...';
    $columns = $columns ?? [];
@endphp

<section class="panel data-panel" data-table-panel="{{ $tableId }}">
    <h2 class="panel-title">{{ $title }}</h2>

    <div class="toolbar-row">
        <label class="search-input-wrap">
            <span class="search-icon">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"/><path d="M16 16L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <input type="text" data-table-search="{{ $tableId }}" placeholder="{{ $searchPlaceholder }}">
        </label>

        <div class="toolbar-actions">
            <select class="filter-select" data-table-filter="{{ $tableId }}" aria-label="Filter {{ $title }}"></select>
            <button class="ghost-btn" type="button" data-table-reset="{{ $tableId }}">Reset</button>
            <button class="ghost-btn" type="button" data-table-template="{{ $tableId }}">Template</button>
            <button class="ghost-btn" type="button" data-table-export="{{ $tableId }}">Export Excel</button>
            <button class="ghost-btn" type="button" data-table-export-pdf="{{ $tableId }}">Export PDF</button>
            <button class="ghost-btn" type="button" data-table-import="{{ $tableId }}">Import</button>
        </div>
    </div>

    <div class="table-wrap">
        <table class="bsa-table">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        @php
                            $key = $column['key'] ?? '';
                            $label = $column['label'] ?? '';
                            $sortable = $column['sortable'] ?? true;
                            $class = $column['class'] ?? '';
                        @endphp
                        <th
                            class="{{ $class }} {{ $sortable ? 'is-sortable' : '' }}"
                            @if ($sortable)
                                data-sort-table="{{ $tableId }}"
                                data-sort-key="{{ $key }}"
                            @endif
                        >
                            <span>{{ $label }}</span>
                            @if ($sortable)
                                <i class="sort-caret" aria-hidden="true"></i>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="{{ $tableId }}TableBody"></tbody>
        </table>
    </div>

    <div class="table-footer-row">
        <p class="table-meta" id="{{ $tableId }}DataInfo">Menampilkan 0 data</p>
        <div class="pagination-controls">
            <button class="mini-btn" type="button" data-page-nav="prev" data-page-table="{{ $tableId }}">Prev</button>
            <span class="page-indicator" id="{{ $tableId }}PageInfo">1 / 1</span>
            <button class="mini-btn" type="button" data-page-nav="next" data-page-table="{{ $tableId }}">Next</button>
        </div>
    </div>

    <button class="primary-action" type="button" data-open-entity-modal="{{ $tableId }}" data-mode="add">+ {{ $addLabel }}</button>
</section>