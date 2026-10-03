import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 8.5A3.5 3.5 0 0 1 10.5 5H20v28H10.5A3.5 3.5 0 0 0 7 36.5v-28Z" fill="currentColor" opacity=".95" />
            <path d="M33 8.5A3.5 3.5 0 0 0 29.5 5H20v28h9.5a3.5 3.5 0 0 1 3.5 3.5v-28Z" fill="currentColor" opacity=".72" />
            <path d="m15 14-4 4 4 4M25 14l4 4-4 4M23 11l-6 14" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
