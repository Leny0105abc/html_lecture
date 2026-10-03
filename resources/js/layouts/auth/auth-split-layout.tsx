import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col overflow-hidden bg-slate-950 p-10 text-white lg:flex dark:border-r">
                <div className="absolute -left-32 top-1/4 size-96 rounded-full bg-indigo-600/30 blur-3xl" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <AppLogoIcon className="mr-2 size-8 fill-current text-white" />
                    CodeLab Academy
                </Link>
                <div className="relative z-10 my-auto max-w-lg"><p className="text-sm font-bold uppercase tracking-[.2em] text-amber-300">Learn by doing</p><h2 className="mt-4 text-5xl font-black leading-tight">Turn your ideas into webpages.</h2><p className="mt-5 text-lg leading-8 text-slate-300">Read the lesson, experiment in the code lab, and see every change instantly.</p><div className="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5 font-mono text-sm leading-7 text-slate-300"><span className="text-indigo-300">&lt;h1&gt;</span>Your journey starts here.<span className="text-indigo-300">&lt;/h1&gt;</span></div></div>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[390px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
