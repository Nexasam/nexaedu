import { Check, ChevronDown, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

export default function SearchableMultiSelect({
    id,
    selectedIds,
    options,
    placeholder = 'No matching options.',
    searchPlaceholder = 'Search options',
    onChange,
    getOptionLabel = (option) => option.name,
}) {
    const containerRef = useRef(null);
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState('');
    const normalizedIds = selectedIds.map(String);
    const selectedOptions = options.filter((option) => normalizedIds.includes(String(option.id)));
    const filteredOptions = useMemo(() => {
        const terms = query.trim().toLowerCase().split(/\s+/).filter(Boolean);

        return options.filter((option) => {
            const label = String(getOptionLabel(option) ?? '').toLowerCase();

            return terms.every((term) => label.includes(term));
        });
    }, [getOptionLabel, options, query]);

    useEffect(() => {
        const closeOnOutsideClick = (event) => {
            if (!containerRef.current?.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('pointerdown', closeOnOutsideClick);

        return () => document.removeEventListener('pointerdown', closeOnOutsideClick);
    }, []);

    const toggleOption = (optionId) => {
        if (normalizedIds.includes(String(optionId))) {
            onChange(selectedIds.filter((selectedId) => String(selectedId) !== String(optionId)));
        } else {
            onChange([...selectedIds, optionId]);
        }
    };

    return (
        <div ref={containerRef} className="relative mt-1">
            <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" strokeWidth={2} />
                <input
                    id={id}
                    type="search"
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setIsOpen(true);
                    }}
                    onFocus={() => setIsOpen(true)}
                    onKeyDown={(event) => {
                        if (event.key === 'Escape') {
                            setIsOpen(false);
                        }
                    }}
                    placeholder={selectedOptions.length ? `${selectedOptions.length} selected • ${searchPlaceholder}` : searchPlaceholder}
                    className="block w-full rounded-md border-slate-300 pl-9 pr-10 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                    autoComplete="off"
                    aria-expanded={isOpen}
                    aria-controls={`${id}-options`}
                />
                <button type="button" onClick={() => setIsOpen((current) => !current)} className="absolute right-1 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Show options">
                    <ChevronDown className={['h-4 w-4 transition', isOpen ? 'rotate-180' : ''].join(' ')} aria-hidden="true" />
                </button>
            </div>

            {isOpen && (
                <div id={`${id}-options`} className="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl">
                    <div className="max-h-60 overflow-y-auto py-1">
                        {filteredOptions.length === 0 && <div className="px-3 py-3 text-sm text-slate-500">{placeholder}</div>}
                        {filteredOptions.map((option) => {
                            const isSelected = normalizedIds.includes(String(option.id));

                            return (
                                <button key={option.id} type="button" onClick={() => toggleOption(option.id)} className={['flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition', isSelected ? 'bg-[var(--brand-primary-soft)] font-semibold text-[var(--brand-primary)]' : 'text-slate-700 hover:bg-slate-50'].join(' ')}>
                                    <span>{getOptionLabel(option)}</span>
                                    {isSelected && <Check className="h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />}
                                </button>
                            );
                        })}
                    </div>
                    <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-3 py-2">
                        <button type="button" onClick={() => onChange([])} disabled={selectedIds.length === 0} className="text-xs font-bold text-red-600 disabled:opacity-40">Clear all</button>
                        <button type="button" onClick={() => setIsOpen(false)} className="text-xs font-bold text-slate-600">Done</button>
                    </div>
                </div>
            )}

            {selectedOptions.length > 0 && (
                <div className="mt-2 flex flex-wrap items-center gap-2">
                    {selectedOptions.map((option) => (
                        <span key={option.id} className="inline-flex max-w-full items-center gap-1.5 rounded-full bg-[var(--brand-primary-soft)] px-3 py-1 text-xs font-semibold text-[var(--brand-primary)] ring-1 ring-[var(--brand-primary-border)]">
                            <span className="truncate">{getOptionLabel(option)}</span>
                            <button type="button" onClick={() => toggleOption(option.id)} className="grid h-4 w-4 shrink-0 place-items-center rounded-full hover:bg-white/80" title={`Remove ${getOptionLabel(option)}`}><X className="h-3 w-3" aria-hidden="true" /></button>
                        </span>
                    ))}
                    <button type="button" onClick={() => onChange([])} className="px-2 py-1 text-xs font-bold text-red-600 hover:text-red-700">Clear all</button>
                </div>
            )}
        </div>
    );
}
