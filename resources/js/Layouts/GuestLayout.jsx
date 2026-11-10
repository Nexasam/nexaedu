import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const authImages = [
    {
        src: 'https://static1.squarespace.com/static/5d1248941ad53b00019bd5b2/5f046265e6defd42d7186cc9/684fde40eec4153f2cff4a93/1750859741618/children%2Bnigeria.jpg?format=1500w',
        position: 'center',
    },
    {
        src: 'https://cdn.businessday.ng/2015/08/school-children.jpg',
        position: 'center 35%',
    },
];

export default function GuestLayout({ children }) {
    const [activeImage, setActiveImage] = useState(0);

    useEffect(() => {
        const interval = window.setInterval(() => {
            setActiveImage((currentImage) => (currentImage + 1) % authImages.length);
        }, 5000);

        return () => window.clearInterval(interval);
    }, []);

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <div className="grid min-h-screen lg:grid-cols-2">
                <section className="relative hidden overflow-hidden bg-slate-900 lg:block">
                    {authImages.map((image, index) => (
                        <div
                            key={image.src}
                            className={`absolute inset-0 bg-cover transition-opacity duration-1000 ${
                                index === activeImage ? 'opacity-100' : 'opacity-0'
                            }`}
                            style={{
                                backgroundImage: `url(${image.src})`,
                                backgroundPosition: image.position,
                            }}
                        />
                    ))}
                    <div className="absolute inset-0 bg-slate-950/80" />
                    <div className="absolute inset-0 bg-gradient-to-b from-slate-950/55 via-slate-950/30 to-slate-950/90" />

                    <div className="relative z-10 flex min-h-screen flex-col justify-between px-8 py-8">
                    <Link href="/" className="flex items-center gap-3">
                        <ApplicationLogo className="h-10 w-10 text-emerald-400" />
                        <div>
                            <div className="text-lg font-bold">SchoolOS</div>
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">
                                School management
                            </div>
                        </div>
                    </Link>

                    <div className="space-y-8">
                        <div className="flex gap-2">
                            {authImages.map((image, index) => (
                                <button
                                    key={image.src}
                                    type="button"
                                    aria-label={`Show school image ${index + 1}`}
                                    className={`h-1.5 rounded-full transition-all ${
                                        index === activeImage ? 'w-10 bg-emerald-300' : 'w-5 bg-white/35'
                                    }`}
                                    onClick={() => setActiveImage(index)}
                                />
                            ))}
                        </div>

                        <div className="rounded-lg border border-white/10 bg-slate-950/45 p-6 shadow-2xl shadow-black/30 backdrop-blur-sm">
                            <div className="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-300">
                                Admin portal
                            </div>
                            <h1 className="mt-4 text-3xl font-bold tracking-tight text-white">
                                Manage admissions, attendance, fees, and classes from one desk.
                            </h1>
                            <div className="mt-8 grid gap-3 text-sm">
                                {['Students', 'Fees', 'Attendance'].map((item) => (
                                    <div key={item} className="rounded-md border border-white/10 bg-white/10 p-4">
                                        <div className="font-semibold text-white">{item}</div>
                                        <div className="mt-1 text-xs text-slate-300">Live school records</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="text-sm text-slate-400">
                        Built for daily school operations.
                    </div>
                    </div>
                </section>

                <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 text-slate-900">
                    <div className="w-full max-w-md">
                        <Link href="/" className="mb-8 flex items-center gap-3 lg:hidden">
                            <ApplicationLogo className="h-10 w-10 text-emerald-600" />
                            <div>
                                <div className="text-lg font-bold text-slate-950">SchoolOS</div>
                                <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">
                                    School management
                                </div>
                            </div>
                        </Link>

                        <div className="rounded-xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/70">
                            {children}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
