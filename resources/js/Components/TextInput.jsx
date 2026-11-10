import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 focus:border-[var(--brand-primary)] focus:bg-white focus:ring-[var(--brand-primary)] ' +
                className
            }
            ref={localRef}
        />
    );
});
