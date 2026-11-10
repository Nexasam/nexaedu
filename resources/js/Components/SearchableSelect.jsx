import { Check, ChevronDown, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

export default function SearchableSelect({
    id,
    value,
    options,
    placeholder = 'No matching options.',
    searchPlaceholder = 'Search options',
    onChange,
    getOptionLabel = (option) => option.name,
}) {
    const containerRef = useRef(null);
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selectedOption = options.find((option) => String(option.id) === String(value));
    const selectedLabel = selectedOption ? String(getOptionLabel(selectedOption) ?? '') : '';
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

    const clear = () => {
        onChange('');
        setQuery('');
        setIsOpen(false);
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
                    placeholder={selectedLabel || searchPlaceholder}
                    className="block w-full rounded-md border-slate-300 pl-9 pr-16 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                    autoComplete="off"
                    aria-expanded={isOpen}
                    aria-controls={`${id}-options`}
                />
                {value ? (
                    <button type="button" onClick={clear} className="absolute right-8 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-red-600" title="Clear selection">
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                ) : null}
                <button type="button" onClick={() => setIsOpen((current) => !current)} className="absolute right-1 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Show options">
                    <ChevronDown className={['h-4 w-4 transition', isOpen ? 'rotate-180' : ''].join(' ')} aria-hidden="true" />
                </button>
            </div>

            {isOpen && (
                <div id={`${id}-options`} className="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl">
                    {filteredOptions.length === 0 && <div className="px-3 py-3 text-sm text-slate-500">{placeholder}</div>}
                    {filteredOptions.map((option) => {
                        const isSelected = String(value) === String(option.id);

                        return (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => {
                                    onChange(option.id);
                                    setQuery('');
                                    setIsOpen(false);
                                }}
                                className={[
                                    'flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition',
                                    isSelected ? 'bg-[var(--brand-primary-soft)] font-semibold text-[var(--brand-primary)]' : 'text-slate-700 hover:bg-slate-50',
                                ].join(' ')}
                            >
                                <span>{getOptionLabel(option)}</span>
                                {isSelected && <Check className="h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />}
                            </button>
                        );
                    })}
                </div>
            )}

            {selectedOption && (
                <div className="mt-2 inline-flex max-w-full items-center gap-2 rounded-full bg-[var(--brand-primary-soft)] px-3 py-1 text-xs font-semibold text-[var(--brand-primary)] ring-1 ring-[var(--brand-primary-border)]">
                    <span className="truncate">{selectedLabel}</span>
                    <button type="button" onClick={clear} className="grid h-4 w-4 shrink-0 place-items-center rounded-full hover:bg-white/80" title="Clear selection"><X className="h-3 w-3" aria-hidden="true" /></button>
                </div>
            )}
        </div>
    );
}
