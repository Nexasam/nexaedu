export default function PrimaryButton({
    type = 'submit',
    className = '',
    disabled,
    children,
    style = {},
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            style={{
                backgroundColor: 'var(--brand-primary, #059669)',
                ...style,
            }}
            className={
                `inline-flex items-center justify-center rounded-lg border border-transparent px-5 py-3 text-sm font-bold text-white shadow-sm transition duration-150 ease-in-out hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)] focus:ring-offset-2 active:brightness-90 disabled:cursor-not-allowed disabled:opacity-50 ${
                    disabled ? 'opacity-50' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
