import React from 'react';
import MainLayout from '../Layouts/MainLayout';
import CommentList from '../Components/news/CommentList';
import CommentForm from '../Components/news/CommentForm';

export default function Article({ article }) {
  
  const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('fa-IR', { year: 'numeric', month: 'long', day: 'numeric' });
  };

  if (!article) {
    return (
      <MainLayout>
        <main className="bg-gray-50 min-h-screen">
          <div className="site-container py-8 text-center py-20">
            <h1 className="text-2xl font-bold text-gray-600">خبر یافت نشد</h1>
            <a href="/" className="mt-4 inline-block text-[#1B365D] hover:text-[#C9A96E]">بازگشت به صفحه اصلی</a>
          </div>
        </main>
      </MainLayout>
    );
  }

  return (
    <MainLayout>
      <main className="bg-gray-50 min-h-screen">
        <div className="site-container py-8">
          <article className="max-w-4xl mx-auto">
            
            <script
              type="application/ld+json"
              dangerouslySetInnerHTML={{
                __html: JSON.stringify({
                  '@context': 'https://schema.org',
                  '@type': 'NewsArticle',
                  headline: article.title,
                  description: article.excerpt || article.title,
                  image: article.featuredImage || undefined,
                  datePublished: article.publishedAt,
                  author: { '@type': 'Person', name: article.author?.name || 'لیان دید' },
                  publisher: {
                    '@type': 'Organization',
                    name: 'لیان دید',
                    logo: { '@type': 'ImageObject', url: 'https://liandid.ir/logo.png' },
                  },
                }),
              }}
            />

            <div className="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
              {article.featuredImage && (
                <div className="relative w-full overflow-hidden aspect-[16/9]">
                  <img src={article.featuredImage} alt={article.title} className="absolute inset-0 w-full h-full object-cover" />
                </div>
              )}
              
              <div className="p-6 md:p-8">
                <div className="flex flex-wrap items-center gap-3 mb-4">
                  <span className="text-xs px-3 py-1.5 rounded-full font-bold bg-[#1B365D] text-white">
                    {article.category?.name || 'اخبار'}
                  </span>
                  <span className="text-sm text-gray-500">{formatDate(article.publishedAt)}</span>
                  <span className="text-sm text-gray-500 hidden sm:inline">•</span>
                  <span className="text-sm text-gray-500">{article.viewCount} بازدید</span>
                </div>
                
                <h1 className="text-2xl md:text-3xl font-black text-[#1B365D] mb-6 leading-relaxed">
                  {article.title}
                </h1>
                
                <div className="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                  <div className="w-10 h-10 rounded-full bg-gradient-to-br from-[#1B365D] to-[#2E5090] flex items-center justify-center text-white font-bold">
                    {article.author?.name?.charAt(0) || 'م'}
                  </div>
                  <div>
                    <div className="font-bold text-sm text-gray-800">{article.author?.name || 'نویسنده مهمان'}</div>
                    <div className="text-xs text-gray-500">خبرنگار لیان دید</div>
                  </div>
                </div>
                
                <div className="prose prose-lg max-w-none text-gray-700 leading-loose text-justify" dangerouslySetInnerHTML={{ __html: article.content }}>
                </div>
              </div>
            </div>

            {/* بخش نظرات اضافه شد */}
            <div className="mt-8">
              <CommentList articleId={article.id} />
              <CommentForm articleId={article.id} />
            </div>

          </article>
        </div>
      </main>
    </MainLayout>
  );
}