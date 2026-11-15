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
              <div className="absolute inset-0 bg-gradient-to-t from-[#0a1628]/90 via-[#0a1628]/25 to-transparent pointer-events-none" />
              <div className="absolute bottom-0 right-0 left-0 p-4 sm:p-6 lg:p-8 z-20">
                <div className="max-w-xl lg:max-w-2xl text-right">
                  <div className="w-12 h-1 bg-[#C9A96E] rounded-full mb-3 sm:mb-4" />
                  <h1 className="text-base sm:text-lg md:text-xl lg:text-2xl font-extrabold text-white mb-2.5 sm:mb-3 leading-[1.8] line-clamp-2 group-hover:text-[#C9A96E] transition-colors duration-500 drop-shadow">
                    {main.title}
                  </h1>
                  {main.description && (
                    <p className="text-white/70 text-[11px] sm:text-xs md:text-sm line-clamp-1 mb-3 leading-relaxed drop-shadow">
                      {main.description}
                    </p>
                  )}
                  <div className="flex items-center gap-3 text-[10px] sm:text-xs text-white/60">
                    <span className="flex items-center gap-1.5">
                      <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {timeAgo(main.publishedAt)}
                    </span>
                    <span className="w-1 h-1 bg-[#C9A96E] rounded-full" />
                    <span className="bg-white/10 backdrop-blur-sm px-2.5 py-1 rounded-full font-medium">{main.sourceName}</span>
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
                <div className="absolute inset-0 bg-gradient-to-t from-[#0a1628] via-[#0a1628]/30 to-transparent" />
                {news.category && news.category !== 'بوشهر' && (
                  <div className="absolute top-4 right-4 z-20">
                    <span className={`inline-block text-[11px] px-3 py-1.5 rounded-full font-bold text-white ${getCategoryStyle(news.category).bg} shadow-lg`}>
                      {news.category}
                    </span>
                  </div>
                )}
                <div className="absolute bottom-0 right-0 left-0 p-4 z-20">
                  <h3 className="text-sm md:text-base font-extrabold text-white group-hover:text-[#C9A96E] transition-colors duration-300 line-clamp-2 leading-[1.7] drop-shadow">
                    {news.title}
                  </h3>
                  <div className="flex items-center gap-2 mt-1.5 text-[10px] md:text-xs text-white/50">
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