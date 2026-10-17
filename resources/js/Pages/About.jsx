import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

const TILE = 'bg-white rounded-2xl border border-gray-100 p-6 shadow-sm';

export default function About({ settings = {}, page = null }) {
    return (
        <MainLayout title="درباره ما">
            <Head title="درباره ما | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6 max-w-4xl">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">
                            {page?.title || settings.aboutTitle || 'درباره ما'}
                        </h1>
                    </div>

                    {page?.content ? (
                        <div
                            className={`${TILE} prose max-w-none leading-8 text-gray-700`}
                            dangerouslySetInnerHTML={{ __html: page.content }}
                        />
                    ) : (
                        <div className={TILE}>
                            <p className="leading-8 text-gray-700">
                                {settings.footerDescription ||
                                    'پایگاه خبری تحلیلی لیان دید، مرجع خبرهای لحظه‌ای استان بوشهر و ایران.'}
                            </p>
                            <p className="leading-8 text-gray-700 mt-4">
                                پروانه انتشار:{' '}
                                <span className="font-bold text-[#1B365D]">
                                    {settings.license || '۹۶۲۲۷'}
                                </span>
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}