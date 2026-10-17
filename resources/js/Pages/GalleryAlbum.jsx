import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';

export default function GalleryAlbum({ album = null, items = [] }) {
    if (!album) return null;

    return (
        <MainLayout title={album.title}>
            <Head title={`${album.title} | لیان دید`} />
            <div className="bg-gray-50 min-h-screen pb-12">
                <div className="site-container pt-6">
                    <Link href="/gallery" className="text-sm text-[#1B365D] hover:text-[#C9A96E] font-bold flex items-center gap-1 mb-3">
                        بازگشت به گالری
                        <svg className="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>

                    <div className="flex items-center gap-3 mb-2">
                        <span className="w-1 h-8 bg-[#C9A96E] rounded-full" />
                        <h1 className="text-2xl font-black text-[#1B365D]">{album.title}</h1>
                    </div>

                    <div className="flex items-center gap-3 text-xs text-gray-400 mb-6 pr-5">
                        {album.category && <span className="font-bold text-[#1B365D]">{album.category}</span>}
                        {album.date && <span>{album.date}</span>}
                        <span>📷 {album.itemCount} تصویر</span>
                    </div>

                    {album.description && (
                        <p className="text-sm text-gray-600 leading-7 mb-6 max-w-3xl">{album.description}</p>
                    )}

                    {items.length === 0 ? (
                        <p className="text-center text-gray-400 py-16">
                            هنوز تصویری در این آلبوم بارگذاری نشده است.
                        </p>
                    ) : (
                        <div className="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
                            {items.map((it) => (
                                <figure
                                    key={it.id}
                                    className="break-inside-avoid mb-4 bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all"
                                >
                                    {it.kind === 'video' ? (
                                        <a href={it.url} target="_blank" rel="noopener noreferrer" className="block relative">
                                            {it.thumbnail ? (
                                                <img src={it.thumbnail} alt={it.title || ''} className="w-full" />
                                            ) : (
                                                <div className={`w-full h-40 bg-gradient-to-br ${album.color}`} />
                                            )}
                                            <span className="absolute inset-0 grid place-items-center text-white text-3xl">
                                                ▶
                                            </span>
                                        </a>
                                    ) : (
                                        <img src={it.url} alt={it.title || album.title} loading="lazy" />
                                    )}
                                    {it.title && (
                                        <figcaption className="p-3 text-xs text-gray-600 leading-5">
                                            {it.title}
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