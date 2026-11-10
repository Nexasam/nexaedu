export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-slate-300 text-[var(--brand-primary)] shadow-sm focus:ring-[var(--brand-primary)] ' +
                className
            }
        />
    );
}
