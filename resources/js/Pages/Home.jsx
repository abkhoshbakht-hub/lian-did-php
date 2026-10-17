import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import HeroSection from '../Components/news/HeroSection';
import LatestNews from '../Components/news/LatestNews';
import RssNewsFeed from '../Components/news/RssNewsFeed';
import AnalysisSection from '../Components/news/AnalysisSection';
import SubmitBanner from '../Components/home/SubmitBanner';

const PLACEHOLDER = {
    id: 'placeholder',
    title: 'هنوز خبری ثبت نشده است',
    description: 'به‌زودی خبرهای تازه اینجا نمایش داده می‌شود.',
    image: '/og-image.png',
    sourceName: 'لیان دید',
    category: 'اخبار',
    publishedAt: null,
    link: '#',
};

export default function Home({ home = {}, articles = [], categories = [] }) {
    const heroMain = home.heroMain || null;
    const heroSide1 = home.heroSide1 || null;
    const heroSide2 = home.heroSide2 || null;
    const latestItems = Array.isArray(home.latest) ? home.latest : [];
    const analysisItems = Array.isArray(home.analysis) ? home.analysis : [];

    const side = (item, fallbackCategory) =>
        item || { ...PLACEHOLDER, category: fallbackCategory, image: null };

    const hero1 = side(heroSide1, 'سیاسی');
    const hero2 = side(heroSide2, 'اقتصادی');

    return (
        <MainLayout breakingItems={home.breaking || []}>
            <main className="bg-gray-50 min-h-screen pb-10">
                <div className="site-container pt-4">
                    <HeroSection
                        main={heroMain || PLACEHOLDER}
                        side1={hero1}
                        side2={hero2}
                    />

                    <div className="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 min-h-[400px] lg:h-[600px]">
                            <LatestNews items={latestItems} />
                        </div>
                        <div className="min-h-[400px] lg:h-[600px]">
                            <RssNewsFeed />
                        </div>
                    </div>

                    <AnalysisSection items={analysisItems} />

                    <SubmitBanner />
                </div>
            </main>
        </MainLayout>
    );
}