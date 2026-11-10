export default function ApplicationLogo(props) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect width="48" height="48" rx="10" fill="currentColor" />
            <path
                d="M12 17.4L24 11L36 17.4L24 23.8L12 17.4Z"
                fill="white"
                fillOpacity="0.95"
            />
            <path
                d="M16 22.2V29.1C16 31.4 19.6 34 24 34C28.4 34 32 31.4 32 29.1V22.2L24 26.5L16 22.2Z"
                fill="white"
                fillOpacity="0.82"
            />
            <path
                d="M36 18V28"
                stroke="white"
                strokeLinecap="round"
                strokeWidth="2"
            />
            <path
                d="M36 28.5C37.1 28.5 38 29.4 38 30.5V32H34V30.5C34 29.4 34.9 28.5 36 28.5Z"
                fill="white"
            />
        </svg>
    );
}
