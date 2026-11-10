import { ChevronDown, ChevronsUpDown, ChevronUp, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

function valueFor(column, row) {
    if (column.sortValue) {
        return column.sortValue(row);
    }

    if (column.searchValue) {
        return column.searchValue(row);
    }

    return row[column.key];
}

function searchableText(value) {
    if (value === null || value === undefined) {
        return '';
    }

    if (Array.isArray(value)) {
        return value.map(searchableText).join(' ');
    }

    if (typeof value === 'object') {
        return Object.values(value).map(searchableText).join(' ');
    }

    return String(value);
}

export default function DataTable({ columns, rows, emptyLabel = 'No records found.', searchPlaceholder = 'Search table' }) {
    const [query, setQuery] = useState('');
    const [sort, setSort] = useState({ key: columns.find((column) => column.sortable !== false)?.key, direction: 'asc' });
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);

    const filteredRows = useMemo(() => {
        const searchTerms = query.trim().toLowerCase().split(/\s+/).filter(Boolean);

        if (searchTerms.length === 0) {
            return rows;
        }

        return rows.filter((row) => {
            const columnText = columns.map((column) => {
                const value = column.searchValue ? column.searchValue(row) : valueFor(column, row);

                return searchableText(value);
            }).join(' ');
            const haystack = `${columnText} ${searchableText(row)}`.toLowerCase();

            return searchTerms.every((term) => haystack.includes(term));
        });
    }, [columns, query, rows]);

    const sortedRows = useMemo(() => {
        const column = columns.find((item) => item.key === sort.key);

        if (!column || column.sortable === false) {
            return filteredRows;
        }

        return [...filteredRows].sort((first, second) => {
            const firstValue = String(valueFor(column, first) ?? '').toLowerCase();
            const secondValue = String(valueFor(column, second) ?? '').toLowerCase();
            const result = firstValue.localeCompare(secondValue, undefined, { numeric: true, sensitivity: 'base' });

            return sort.direction === 'asc' ? result : -result;
        });
    }, [columns, filteredRows, sort]);

    const totalPages = Math.max(1, Math.ceil(sortedRows.length / perPage));
    const currentPage = Math.min(page, totalPages);
    const pageRows = sortedRows.slice((currentPage - 1) * perPage, currentPage * perPage);
    const start = sortedRows.length === 0 ? 0 : ((currentPage - 1) * perPage) + 1;
    const end = Math.min(currentPage * perPage, sortedRows.length);

    const changeSort = (column) => {
        if (column.sortable === false) {
            return;
        }

        setPage(1);
        setSort((current) => ({
            key: column.key,
            direction: current.key === column.key && current.direction === 'asc' ? 'desc' : 'asc',
        }));
    };

    return (
        <div className="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" strokeWidth={2} />
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setPage(1);
                        }}
                        placeholder={searchPlaceholder}
                        className="block w-full rounded-md border-slate-300 pl-9 pr-9 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                    />
                    {query && (
                        <button type="button" onClick={() => { setQuery(''); setPage(1); }} className="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-red-600" title="Clear search">
                            <X className="h-4 w-4" aria-hidden="true" />
                        </button>
                    )}
                </div>

                <div className="flex items-center gap-2 text-sm text-slate-500">
                    <span>Rows</span>
                    <select
                        value={perPage}
                        onChange={(event) => {
                            setPerPage(Number(event.target.value));
                            setPage(1);
                        }}
                        className="rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                    >
                        {[10, 25, 50].map((count) => <option key={count} value={count}>{count}</option>)}
                    </select>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            {columns.map((column) => {
                                const isSorted = sort.key === column.key;
                                const SortIcon = !isSorted ? ChevronsUpDown : sort.direction === 'asc' ? ChevronUp : ChevronDown;

                                return (
                                    <th key={column.key} className={['px-4 py-3', column.headerClassName].filter(Boolean).join(' ')}>
                                        <button
                                            type="button"
                                            onClick={() => changeSort(column)}
                                            disabled={column.sortable === false}
                                            className={[
                                                'inline-flex items-center gap-2',
                                                column.sortable === false ? 'cursor-default' : 'transition hover:text-slate-900',
                                            ].join(' ')}
                                        >
                                            {column.label}
                                            {column.sortable !== false && <SortIcon className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />}
                                        </button>
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {pageRows.length === 0 && (
                            <tr>
                                <td colSpan={columns.length} className="px-4 py-6 text-center text-sm text-slate-500">
                                    {emptyLabel}
                                </td>
                            </tr>
                        )}
                        {pageRows.map((row) => (
                            <tr key={row.id}>
                                {columns.map((column) => (
                                    <td key={column.key} className={['px-4 py-3', column.className].filter(Boolean).join(' ')}>
                                        {column.render ? column.render(row) : row[column.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    Showing <span className="font-semibold text-slate-700">{start}</span> to <span className="font-semibold text-slate-700">{end}</span> of <span className="font-semibold text-slate-700">{sortedRows.length}</span>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        disabled={currentPage === 1}
                        onClick={() => setPage((value) => Math.max(1, value - 1))}
                        className="rounded-md border border-slate-200 px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span className="font-semibold text-slate-700">{currentPage} / {totalPages}</span>
                    <button
                        type="button"
                        disabled={currentPage === totalPages}
                        onClick={() => setPage((value) => Math.min(totalPages, value + 1))}
                        className="rounded-md border border-slate-200 px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    );
}
