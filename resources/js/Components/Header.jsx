import React, { useState, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';

const categories = [
  { name: 'صفحه اصلی', slug: 'home', href: '/' },
  {
    name: 'سیاسی',
    slug: 'siyasi',
    subcategories: [
      { name: 'رهبری', slug: 'rahbari' },
      { name: 'دولت', slug: 'dolat' },
      { name: 'مجلس', slug: 'majles' },
      { name: 'سیاست خارجی', slug: 'siyasat-khareji' },
      { name: 'احزاب و تشکل‌ها', slug: 'ahzab' },
      { name: 'امنیت و دفاع', slug: 'amniyat-defa' },
    ],
  },
  {
    name: 'اقتصادی',
    slug: 'eghtesadi',
    subcategories: [
      { name: 'اقتصاد ایران', slug: 'eghtesad-iran' },
      { name: 'اقتصاد جهان', slug: 'eghtesad-jahan' },
      { name: 'بورس و بازار سرمایه', slug: 'bours' },
      { name: 'مسکن و ساختمان', slug: 'maskan' },
      { name: 'انرژی', slug: 'energy' },
      { name: 'کشاورزی و صنعت', slug: 'keshavarzi-sanat' },
    ],
  },
  {
    name: 'اجتماعی',
    slug: 'ejtemaei',
    subcategories: [
      { name: 'حوادث', slug: 'havades' },
      { name: 'بهداشت و درمان', slug: 'behdasht' },
      { name: 'آموزش و پرورش', slug: 'amoozesh' },
      { name: 'گردشگری', slug: 'gardeshgari' },
      { name: 'محیط زیست', slug: 'mahiat-zist' },
      { name: 'قضایی و حقوقی', slug: 'ghazayi' },
    ],
  },
  {
    name: 'بین‌الملل',
    slug: 'beynolmelal',
    subcategories: [
      { name: 'خاورمیانه', slug: 'khavarmianeh' },
      { name: 'آسیا', slug: 'asia' },
      { name: 'اروپا', slug: 'oropa' },
      { name: 'آمریکا', slug: 'amrika' },
      { name: 'بین‌الملل (عمومی)', slug: 'beynolmelal-omoomi' },
    ],
  },
  { name: 'فناوری', slug: 'fanavari', subcategories: [
    { name: 'هوش مصنوعی', slug: 'ai' },
    { name: 'موبایل و تبلت', slug: 'mobile' },
    { name: 'شبکه‌های اجتماعی', slug: 'social' },
    { name: 'گجت و تکنولوژی', slug: 'gadget' },
    { name: 'نرم‌افزار و اپلیکیشن', slug: 'software' },
  ]},
  {
    name: 'ورزشی',
    slug: 'varzeshi',
    subcategories: [
      { name: 'فوتبال ایران', slug: 'footbal-iran' },
      { name: 'فوتبال جهان', slug: 'footbal-jahan' },
      { name: 'کشتی و وزنه‌برداری', slug: 'koshti' },
      { name: 'ورزش‌های رزمی', slug: 'varzesh-ha-razmi' },
      { name: 'نتایج زنده', slug: 'natayej-zende' },
    ],
  },
  { name: 'فرهنگی', slug: 'farhangi', subcategories: [
    { name: 'سینما و تلویزیون', slug: 'sinama' },
    { name: 'موسیقی', slug: 'moosighi' },
    { name: 'ادبیات و کتاب', slug: 'adabiat' },
    { name: 'هنرهای تجسمی', slug: 'honar' },
    { name: 'تئاتر', slug: 'teatr' },
  ]},
  { name: 'علمی', slug: 'elmi', subcategories: [
    { name: 'پزشکی و سلامت', slug: 'pezeshki' },
    { name: 'نجوم و فضا', slug: 'nojoom' },
    { name: 'محیط زیست', slug: 'mahiat' },
    { name: 'اختراعات', slug: 'ekhtera' },
    { name: 'دانشگاه و پژوهش', slug: 'daneshgah' },
  ]},
  { name: 'گالری', slug: 'gallery', subcategories: [
    { name: 'عکس', slug: 'photos', href: '/gallery' },
    { name: 'فیلم', slug: 'videos', href: '/gallery/videos' },
  ]},
  {
    name: 'مناطق',
    slug: 'regions',
    href: '/regions',
    subcategories: [
      { name: 'نقشهٔ شهرستان‌های بوشهر', slug: 'map', href: '/regions' },
    ],
  },
];

export default function Header({ breakingItems = [] }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [openDropdown, setOpenDropdown] = useState(null);
  
  const [pulse, setPulse] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [settings, setSettings] = useState({});

  const breaking = Array.isArray(breakingItems) ? breakingItems : [];

  useEffect(() => {
    fetch('/api/admin/settings').then(r => r.json()).then(setSettings).catch(() => {});
  }, []);

  useEffect(() => {
    const t = setInterval(() => setPulse((p) => !p), 1500);
    return () => clearInterval(t);
  }, []);

  return (
    <header className="relative z-50">
      {/* هدر اصلی */}
      <div className="relative bg-white overflow-hidden h-[120px] lg:h-[200px]">
        <img 
          src="/هدر لیان دید 3.png" 
          alt="هدر لیان دید" 
          className="absolute inset-0 w-full h-full object-cover object-right lg:object-center" 
        />
        <div className="absolute z-20 bottom-3 right-12 lg:bottom-auto lg:right-[150px] lg:top-[170px]">
          <span className="text-[9px] lg:text-[10px] font-bold text-[#1B365D] drop-shadow-[0_1px_2px_rgba(255,255,255,0.8)]" style={{ whiteSpace: 'nowrap' }}>— پروانه انتشار: {settings.license || '۹۶۲۲'} —</span>
        </div>
        <div className="site-container h-full flex items-center justify-between relative z-10">
          <div className="flex items-center gap-3"></div>

          {/* سمت چپ: جستجو و لینک‌ها */}
          <div className="hidden lg:flex flex-col items-end gap-2">
            <form
              onSubmit={(e) => {
                e.preventDefault();
                if (searchQuery.trim()) {
                  router.get(`/search`, { q: searchQuery.trim() });
                }
              }}
              className="relative flex"
            >
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder={settings.searchPlaceholder || "جستجو در اخبار..."}
                className="w-48 pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-[#C9A96E] focus:ring-2 focus:ring-[#C9A96E]/10 transition-all placeholder:text-gray-400"
              />
              <button type="submit" className="absolute left-1 top-1/2 -translate-y-1/2 w-7 h-7 bg-[#1B365D] rounded-md flex items-center justify-center hover:bg-[#2a4a7a] transition-colors">
                <svg className="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </button>
            </form>
            <Link href={settings.aboutLink || '/about'} className="text-[12px] text-[#1B365D] hover:text-[#C9A96E] transition-colors font-bold">{settings.aboutText || 'درباره ما'}</Link>
            <Link href={settings.contactLink || '/contact'} className="text-[12px] text-[#1B365D] hover:text-[#C9A96E] transition-colors font-bold">{settings.contactText || 'تماس با ما'}</Link>
          </div>
        </div>
      </div>

      {/* نوار خبر فوری */}
      {breaking.length > 0 && (
        <div className="bg-[#16294a] border-t border-white/5">
          <div className="site-container flex items-center h-10">
            <div className="flex items-center shrink-0 z-10 pl-3">
              <span className="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] px-2.5 py-1 rounded font-black shadow-lg shadow-red-600/30">
                <span className={`w-1.5 h-1.5 rounded-full bg-white ${pulse ? 'animate-pulse' : 'opacity-50'}`} />
                فوری
              </span>
            </div>
            <div className="w-px h-6 bg-white/10 shrink-0 hidden md:block" />
            <div className="flex-1 min-w-0 overflow-hidden">
              <div className="flex items-center whitespace-nowrap animate-marquee">
                {[0, 1, 2].map((copy) => (
                  <div key={copy} className="flex items-center shrink-0">
                    {breaking.map((news, i) => (
                      <a
                        key={`br-${copy}-${news.id}-${i}`}
                        href={news.link || '#'}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-gray-300 hover:text-white transition-colors font-bold text-xs flex items-center gap-2"
                      >
                        <span className="text-[#C9A96E]">◆</span>
                        <span className="mx-1">{news.title}</span>
                        <span className="text-[#C9A96E]">◆</span>
                      </a>
                    ))}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* نوار منو */}
      <nav className="hidden lg:block bg-gradient-to-l from-[#1B365D] via-[#1e3a64] to-[#1B365D] shadow-md">
        <div className="site-container">
          <ul className="flex items-center gap-1">
            {categories.map((cat) => (
              <li
                key={cat.slug}
                className="relative group"
                onMouseEnter={() => cat.subcategories && setOpenDropdown(cat.slug)}
                onMouseLeave={() => setOpenDropdown(null)}
              >
                <Link
                  href={cat.href ? cat.href : `/category/${cat.slug}`}
                  className="px-5 py-2 text-[13px] font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all flex items-center gap-1.5 border-b-2 border-transparent hover:border-[#C9A96E]"
                >
                  {cat.name}
                  {cat.subcategories && (
                    <svg className={`w-3 h-3 transition-transform duration-200 ${openDropdown === cat.slug ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
                  )}
                </Link>

                {cat.subcategories && openDropdown === cat.slug && (
                  <div className="absolute top-full right-0 w-64 bg-white rounded-b-lg shadow-xl border border-gray-100 z-50 overflow-hidden">
                    <div className="py-3">
                      {cat.subcategories.map((sub) => (
                        <Link
                          key={sub.slug}
                          href={sub.href || `/category/${cat.slug}/${sub.slug}`}
                          className="block px-7 py-3.5 text-[13px] text-gray-600 hover:bg-gradient-to-l hover:from-[#1B365D] hover:to-[#2a4a7a] hover:text-white transition-all border-r-2 border-transparent hover:border-[#C9A96E]"
                        >
                          {sub.name}
                        </Link>
                      ))}
                    </div>
                  </div>
                )}
              </li>
            ))}
          </ul>
        </div>
      </nav>
    </header>
  );
}