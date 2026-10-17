import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function StaticPage({ page }) {
    if (!page) return null;

    return (
        <MainLayout title={page.title}>
            <Head title={`${page.title} | لیان دید`} />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6 max-w-4xl">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">{page.title}</h1>
                    </div>

                    {page.image && (
                        <img
                            src={page.image}
                            alt={page.title}
                            className="w-full rounded-2xl mb-6 object-cover max-h-96"
                        />
                    )}

                    <div
                        className="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm prose max-w-none leading-8 text-gray-700"
                        dangerouslySetInnerHTML={{ __html: page.content }}
                    />
                </div>
            </div>
        </MainLayout>
    );
}