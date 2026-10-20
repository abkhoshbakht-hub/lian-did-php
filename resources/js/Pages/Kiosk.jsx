import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import FrontpageGallery from '../Components/news/FrontpageGallery';

function toPersianDate(date) {
  const d = new Date(date);
  const gy = d.getFullYear();
  const gm = d.getMonth() + 1;
  const gd = d.getDate();

  const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
  const gy2 = gm > 2 ? gy + 1 : gy;

  let days =
    355666 +
    365 * gy +
    Math.floor((gy2 + 3) / 4) -
    Math.floor((gy2 + 99) / 100) +
    Math.floor((gy2 + 399) / 400) +
    gd +
    g_d_m[gm - 1];

  let jy = -1595 + 33 * Math.floor(days / 12053);
  days %= 12053;
  jy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) {
    jy += Math.floor((days - 1) / 365);
    days = (days - 1) % 365;
  }

  let jm;
  let jd;
  if (days < 186) {
    jm = 1 + Math.floor(days / 31);
    jd = 1 + (days % 31);
  } else {
    jm = 7 + Math.floor((days - 186) / 30);
    jd = 1 + ((days - 186) % 30);
  }

  return `${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`;
}

export default function Kiosk({ latest, items = [] }) {
  return (
    <MainLayout title="پیشخوان دیوار روزنامه‌ها">
      <Head title="پیشخوان دیوار روزنامه‌ها | لیان دید" />
      <main className="bg-gray-50 min-h-screen">
        <div className="site-container py-8">
          <div className="text-center mb-8">
            <h1 className="text-2xl md:text-3xl font-black text-[#1B365D]">پیشخوان دیوار روزنامه‌ها</h1>
            {latest && (
              <p className="text-gray-500 mt-2">{toPersianDate(new Date(latest + 'T00:00:00'))}</p>
            )}
          </div>
          <FrontpageGallery items={items} />
        </div>
      </main>
    </MainLayout>
  );
}
