import React from 'react';
import ExtImg from './ExtImg';

// توابع کمکی برای محاسبه زمان و رنگ دسته‌بندی‌ها
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

export default function HeroSection({ main, side1, side2 }) {
  return (
    <section className="mb-10">
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {/* Main Hero */}
        <div className="lg:col-span-8">
          {main ? (
            <a href={main.link} target="_blank" rel="noopener noreferrer" className="group block relative rounded-2xl overflow-hidden h-[280px] sm:h-[360px] lg:h-[420px] bg-[#0a1628]">
              {main.image ? (
                <ExtImg
                  src={main.image}
                  alt={main.title}
                  className="absolute inset-0 w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105"
                />
              ) : (
                <div className={`absolute inset-0 bg-gradient-to-br ${getCategoryStyle(main.category).gradient}`} />
              )}
              <div className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-[#0a1628]/95 via-[#0a1628]/50 to-transparent pointer-events-none" />
              <div className="absolute bottom-0 right-0 left-0 p-4 sm:p-5 z-20">
                <div className="text-right">
                  <div className="w-8 h-[3px] bg-[#C9A96E] rounded-full mb-2.5" />
                  <h1 className="text-lg md:text-xl lg:text-[1.55rem] font-bold text-white mb-2 leading-[1.7] line-clamp-2 group-hover:text-[#E7CF9F] transition-colors duration-500 drop-shadow-[0_2px_12px_rgba(0,0,0,0.75)]">
                    {main.title}
                  </h1>
                  <div className="flex items-center gap-2.5 text-[10px] sm:text-[11px] text-white/85">
                    <span className="flex items-center gap-1.5">
                      <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {timeAgo(main.publishedAt)}
                    </span>
                    <span className="w-1 h-1 bg-[#C9A96E] rounded-full" />
                    <span className="bg-[#C9A96E]/15 border border-[#C9A96E]/30 text-[#E7CF9F] px-2.5 py-0.5 rounded-full font-medium">{main.sourceName}</span>
                  </div>
                </div>
              </div>
            </a>
          ) : (
            <div className="rounded-2xl h-[280px] sm:h-[360px] lg:h-[420px] bg-gradient-to-br from-[#0a1628] to-[#1B365D] animate-pulse" />
          )}
        </div>

        {/* Side News */}
        <div className="lg:col-span-4 flex flex-col gap-4">
          {[side1, side2].map((news, idx) => (
            news ? (
              <a key={news.id} href={news.link} target="_blank" rel="noopener noreferrer" className="group flex-1 block relative rounded-2xl overflow-hidden bg-[#0a1628] min-h-[200px]">
                {news.image ? (
                  <ExtImg
                    src={news.image}
                    alt={news.title}
                    className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                  />
                ) : (
                  <div className={`absolute inset-0 bg-gradient-to-br ${getCategoryStyle(news.category).gradient}`} />
                )}
                <div className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-[#0a1628]/95 via-[#0a1628]/45 to-transparent" />
                <div className="absolute bottom-0 right-0 left-0 p-3.5 z-20">
                  <h3 className="text-sm md:text-[15px] font-bold text-white group-hover:text-[#E7CF9F] transition-colors duration-300 line-clamp-2 leading-[1.6] drop-shadow-[0_1px_8px_rgba(0,0,0,0.75)]">
                    {news.title}
                  </h3>
                  <div className="flex items-center gap-2 mt-1 text-[10px] md:text-[11px] text-white/80">
                    <span>{news.sourceName}</span>
                    <span className="w-1 h-1 bg-white/30 rounded-full" />
                    <span>{timeAgo(news.publishedAt)}</span>
                  </div>
                </div>
              </a>
            ) : (
              <div key={idx} className="flex-1 rounded-2xl bg-gray-200 animate-pulse min-h-[200px]" />
            )
          ))}
        </div>
      </div>
    </section>
  );
}