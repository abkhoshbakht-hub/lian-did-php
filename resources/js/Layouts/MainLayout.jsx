import React from 'react';
import { Head } from '@inertiajs/react';
import Header from '../Components/Header';
import Footer from '../Components/Footer';

export default function MainLayout({ children, breakingItems = [], title = 'پایگاه خبری تحلیلی استان بوشهر', ogImage = '/og-image.png' }) {
    return (
        <div className="min-h-screen flex flex-col bg-[#ffffff] text-[#1a1a1a]">
            <Head>
                <title>{`${title} | لیان دید`}</title>
                <meta head-key="og:title" property="og:title" content={`${title} | لیان دید`} />
                <meta head-key="og:image" property="og:image" content={ogImage} />
                <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            </Head>

            <Header breakingItems={breakingItems} />

            <main className="flex-grow">
                {children}
            </main>

            <Footer />
        </div>
    );
}