import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <main className="min-h-screen flex" dir="rtl">
            <Head title="ورود به پنل مدیریت - لیان دید" />

            {/* Left Panel — Form */}
            <div className="flex-1 flex items-center justify-center px-6 bg-white">
                <div className="w-full max-w-sm">
                    <div className="mb-10">
                        <div className="h-14 w-32 relative mb-8">
                            <img src="/logo.png" alt="لیان دید" className="h-full w-full object-contain object-right" />
                        </div>
                        <h1 className="text-2xl font-black text-gray-900">ورود به پنل مدیریت</h1>
                        <p className="text-sm text-gray-400 mt-2">خوش آمدید</p>
                    </div>

                    {status && (
                        <div className="bg-green-50 text-green-600 px-4 py-3 rounded-xl mb-6 text-sm text-right border border-green-100 flex items-center gap-2">
                            {status}
                        </div>
                    )}

                    {(errors.email || errors.password) && (
                        <div className="bg-red-50 text-red-500 px-4 py-3 rounded-xl mb-6 text-sm text-right border border-red-100 flex items-center gap-2">
                            <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {'ایمیل یا رمز عبور اشتباه است'}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label className="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">ایمیل</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all text-sm"
                                placeholder="email@example.com"
                                autoComplete="username"
                                autoFocus
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-bold mb-2 text-gray-500 uppercase tracking-wider">رمز عبور</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all text-sm"
                                placeholder="رمز عبور"
                                autoComplete="current-password"
                                required
                            />
                        </div>

                        {canResetPassword && (
                            <div className="text-left">
                                <Link href="/forgot-password" className="text-xs text-[#1B365D]/60 hover:text-[#1B365D] transition-colors">رمز عبور را فراموش کرده‌اید؟</Link>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-3.5 bg-[#1B365D] text-white rounded-xl font-bold text-sm hover:bg-[#2E5090] transition-all duration-200 disabled:opacity-50 shadow-lg shadow-[#1B365D]/20 hover:shadow-xl hover:shadow-[#1B365D]/30"
                        >
                            {processing ? (
                                <span className="flex items-center justify-center gap-2">
                                    <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                    در حال ورود...
                                </span>
                            ) : 'ورود'}
                        </button>
                    </form>

                    <p className="text-[11px] text-gray-300 text-center mt-8">لیان دید — پایگاه خبری استان بوشهر</p>
                </div>
            </div>

            {/* Right Panel — Decorative */}
            <div className="hidden lg:flex flex-1 bg-gradient-to-br from-[#1B365D] via-[#2E5090] to-[#1B365D] items-center justify-center relative overflow-hidden">
                <div className="absolute inset-0">
                    <div className="absolute top-1/4 right-1/4 w-96 h-96 bg-[#C9A96E]/10 rounded-full blur-3xl" />
                    <div className="absolute bottom-1/4 left-1/4 w-64 h-64 bg-white/5 rounded-full blur-2xl" />
                </div>
                <div className="relative text-center text-white px-12">
                    <div className="w-20 h-20 mx-auto mb-8 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center border border-white/10">
                        <svg className="w-10 h-10 text-[#C9A96E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <h2 className="text-2xl font-black mb-3">لیان دید</h2>
                    <p className="text-white/50 text-sm leading-relaxed max-w-xs mx-auto">پایگاه خبری تحلیلی استان بوشهر<br />خبر فوری، تحلیل دقیق</p>
                    <div className="flex items-center justify-center gap-2 mt-8">
                        <div className="w-2 h-2 rounded-full bg-[#C9A96E]" />
                        <div className="w-12 h-0.5 bg-white/10 rounded-full" />
                        <div className="w-2 h-2 rounded-full bg-white/20" />
                    </div>
                </div>
            </div>
        </main>
    );
}
