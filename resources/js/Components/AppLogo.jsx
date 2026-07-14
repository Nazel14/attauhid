import { useId } from 'react';

export default function AppLogo({ className = 'h-9 w-9' }) {
    const uid = useId();
    const gid = `logo-grad-${uid}`;

    return (
        <svg
            viewBox="-3 -3 106 99"
            className={className}
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id={gid} x1="50" y1="0" x2="50" y2="93" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#2BAFE6" />
                    <stop offset="0.55" stopColor="#2ADB6B" />
                    <stop offset="1" stopColor="#22F03C" />
                </linearGradient>
            </defs>
            <path
                d="M37.5 0 H62.5 L100 93 H58.5 L50 53 L41.5 93 H0 Z"
                fill={`url(#${gid})`}
                stroke={`url(#${gid})`}
                strokeWidth="4"
                strokeLinejoin="round"
            />
        </svg>
    );
}