import React from 'react';
import { Link } from '@inertiajs/react';

// توابع کمکی
const timeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'همین الان';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} دقیقه پیش`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ساعت پیش`;
    return `${Math.floor(diffInSeconds / 86400)} روز پیش`;
};

const getCategoryStyle = (category) => {
    return {
        gradient: 'from-[#1e3a64] to-[#0f2440]',
        bg: 'bg-[#1e3a64]'
    };
};

export default function LatestNews({ items = [] }) {
  if (items.length === 0) {
    return <p className="text-gray-400 text-center py-12 text-sm">خبری موجود نیست</p>;
  }

  return (
    <div className="bg-white rounded-2xl border border-gray-100 overflow-hidden h-full shadow-sm flex flex-col">
      {/* هدر */}
      <div className="bg-gradient-to-l from-[#1B365D] to-[#2a4a7a] px-6 py-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
            <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
          </div>
          <h2 className="text-base font-extrabold text-white">آخرین اخبار</h2>
        </div>
      </div>
      <div className="space-y-3 flex-1 min-h-0 overflow-y-auto p-5 pr-2">
        {items.map((news) => (
          <a
            key={news.id}
            href={news.link}
            target="_blank"
            rel="noopener noreferrer"
            className="group flex items-stretch bg-white rounded-xl border border-gray-100 hover:border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-lg hover:shadow-black/5"
          >
            {/* Thumbnail */}
            <div className="relative w-28 sm:w-40 md:w-52 flex-shrink-0 overflow-hidden bg-gray-100">
              {news.image ? (
                <img src={news.image} alt={news.title} className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
              ) : (
                <div className={`w-full h-full bg-gradient-to-br ${getCategoryStyle(news.category).gradient}`} />
              )}
              <div className="absolute top-3 right-3">
                <span className={`text-[10px] px-2.5 py-1 rounded-md font-bold text-white ${getCategoryStyle(news.category).bg} shadow-md`}>
                  {news.category}
                </span>
              </div>
            </div>

            {/* Content */}
            <div className="flex-1 p-4 sm:p-5 flex flex-col justify-center min-w-0">
              <h3 className="font-extrabold text-[#0a1628] group-hover:text-[#C9A96E] transition-colors duration-300 text-[15px] sm:text-lg leading-[1.9] line-clamp-2 mb-2">
                {news.title}
              </h3>
              {news.description && (
                <p className="text-gray-400 text-xs sm:text-sm line-clamp-1 mb-3 leading-relaxed">
                  {news.description}
                </p>
              )}
              <div className="flex items-center gap-3 text-[11px] text-gray-400">
                <span className="flex items-center gap-1">
                  <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  {timeAgo(news.publishedAt)}
                </span>
                {news.sourceName && news.sourceName !== 'لیان دید' && (
                  <>
                    <span className="w-1 h-1 bg-gray-300 rounded-full" />
                    <span className="font-medium text-gray-500">{news.sourceName}</span>
                  </>
                )}
              </div>
            </div>
          </a>
        ))}
      </div>
      <Link href="/latest" className="block text-center py-4 text-sm font-extrabold text-[#1B365D] hover:text-[#C9A96E] hover:bg-gray-50 border-t border-gray-100 transition-colors">
        مشاهده همه اخبار
      </Link>
    </div>
  );
}