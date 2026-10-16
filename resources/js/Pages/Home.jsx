import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import HeroSection from '../Components/news/HeroSection';
import LatestNews from '../Components/news/LatestNews';
import RssNewsFeed from '../Components/news/RssNewsFeed';
import AnalysisSection from '../Components/news/AnalysisSection';
import SubmitBanner from '../Components/home/SubmitBanner';

export default function Home({ articles = [], categories = [] }) {
    
    // تفکیک هوشمند اخبار دیتابیس برای بخش‌های مختلف سایت
    const mainArticle = articles.find(a => a.placement === 'hero' || a.isFeatured) || articles[0] || {
        id: '1', title: 'هنوز خبری ثبت نشده است', excerpt: 'لطفاً از طریق دیتابیس خبر اضافه کنید.', featuredImage: '/og-image.png', publishedAt: new Date().toISOString(), category: { name: 'عمومی', slug: 'general' }
    };

    const sideArticles = articles.filter(a => a.id !== mainArticle.id);
    const side1 = sideArticles[0] || mainArticle;
    const side2 = sideArticles[1] || mainArticle;

    // آخرین اخبار برای لیست
    const latestItems = articles.map(a => ({
        id: a.id,
        title: a.title,
        description: a.excerpt,
        category: a.category?.name || 'اخبار',
        sourceName: a.author?.name || 'لیان دید',
        publishedAt: a.publishedAt,
        image: a.featuredImage,
        link: `/news/${a.slug}`
    }));

    // بخش تحلیل (اخبار تحلیلی یا دسته‌بندی خاص)
    const analysisItems = articles.filter(a => a.category?.slug === 'analysis' || a.placement === 'analysis').length > 0
        ? articles.filter(a => a.category?.slug === 'analysis' || a.placement === 'analysis').map(a => ({
            id: a.id,
            title: a.title,
            description: a.excerpt,
            category: a.category?.name || 'تحلیل',
            sourceName: a.author?.name || 'تحریریه',
            publishedAt: a.publishedAt,
            link: `/news/${a.slug}`,
            image: a.featuredImage
        }))
        : latestItems; // اگر خبر تحلیلی نبود، از لیست عمومی استفاده می‌کند

    return (
        <MainLayout>
            <main className="bg-gray-50 min-h-screen pb-10">
                <div className="site-container pt-4">
                    
                    <HeroSection 
                        main={{
                            id: mainArticle.id,
                            title: mainArticle.title,
                            image: mainArticle.featuredImage || '/og-image.png',
                            description: mainArticle.excerpt,
                            publishedAt: mainArticle.publishedAt,
                            sourceName: mainArticle.author?.name || 'لیان دید',
                            category: mainArticle.category?.name || 'ویژه',
                            link: `/news/${mainArticle.slug}`
                        }} 
                        side1={{
                            id: side1.id,
                            title: side1.title,
                            link: `/news/${side1.slug}`,
                            sourceName: side1.author?.name || 'لیان دید',
                            category: side1.category?.name || 'سیاسی',
                            publishedAt: side1.publishedAt,
                            image: side1.featuredImage
                        }} 
                        side2={{
                            id: side2.id,
                            title: side2.title,
                            link: `/news/${side2.slug}`,
                            sourceName: side2.author?.name || 'لیان دید',
                            category: side2.category?.name || 'ورزشی',
                            publishedAt: side2.publishedAt,
                            image: side2.featuredImage
                        }} 
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

                    {/* بنر ارسال خبر */}
                    <SubmitBanner />

                </div>
            </main>
        </MainLayout>
    );
}