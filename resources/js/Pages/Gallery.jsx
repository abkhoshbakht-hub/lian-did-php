import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function Gallery({ images = [], videos = [] }) {
    return (
        <MainLayout title="گالری تصاویر">
            <Head title="گالری | لیان دید" />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] rounded-2xl px-6 py-5 mb-6 shadow-lg">
                        <h1 className="text-xl font-black text-white">گالری تصاویر</h1>
                        <p className="text-white/70 text-sm mt-1">تصاویر خبرهای لیان دید</p>
                    </div>

                    {images.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">هنوز تصویری بارگذاری نشده است.</p>
                    ) : (
                        <div className="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
                            {images.map((img) => (
                                <figure
                                    key={img.id}
                                    className="break-inside-avoid mb-4 bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all"
                                >
                                    <img src={img.src} alt={img.title || ''} loading="lazy" />
                                    {img.title && (
                                        <figcaption className="p-3 text-xs text-gray-600 leading-5">
                                            {img.title}
                                        </figcaption>
                                    )}
                                </figure>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </MainLayout>
    );
}