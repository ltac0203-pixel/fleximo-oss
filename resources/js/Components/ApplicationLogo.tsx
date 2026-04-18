import { SVGAttributes, useId } from "react";

interface ApplicationLogoProps extends SVGAttributes<SVGElement> {
    // ロゴの代替テキスト(アクセシビリティ対応)
    alt?: string;
}

export default function ApplicationLogo({ alt = "Fleximo", ...props }: ApplicationLogoProps) {
    const id = useId();
    const textGradientId = `textGradient-${id}`;
    const bgGradientId = `bgGradient-${id}`;

    return (
        <svg
            {...props}
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            role="img"
            aria-label={alt}
        >
            <title>{alt}</title>

            <defs>
                <linearGradient id={textGradientId} x1="20" y1="20" x2="80" y2="80" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#38bdf8" />
                    <stop offset="50%" stopColor="#818cf8" />
                    <stop offset="100%" stopColor="#c084fc" />
                </linearGradient>
                <linearGradient id={bgGradientId} x1="0" y1="0" x2="100" y2="100" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#0f172a" />
                    <stop offset="100%" stopColor="#1e293b" />
                </linearGradient>
            </defs>

            <rect x="0" y="0" width="100" height="100" rx="22" fill={`url(#${bgGradientId})`} />

            <g transform="translate(5, 5)" filter="drop-shadow(0 4px 4px rgba(0,0,0,0.4))">
                <rect x="25" y="15" width="16" height="60" rx="8" fill={`url(#${textGradientId})`} />
                <rect x="36" y="15" width="40" height="16" rx="8" fill={`url(#${textGradientId})`} />
                <rect x="36" y="38" width="30" height="16" rx="8" fill={`url(#${textGradientId})`} />
                <circle cx="78" cy="46" r="6" fill="#22d3ee" opacity="0.9" />
            </g>

            <path
                d="M0 22 C0 10 10 0 22 0 H78 C90 0 100 10 100 22 V45 C100 45 75 60 50 60 C25 60 0 45 0 45 V22 Z"
                fill="white"
                fillOpacity="0.05"
            />

            <rect x="2" y="2" width="96" height="96" rx="20" stroke="white" strokeOpacity="0.1" strokeWidth="1.5" />
        </svg>
    );
}
