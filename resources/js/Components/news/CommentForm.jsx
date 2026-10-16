import React, { useState } from 'react';

export default function CommentForm({ articleId }) {
  const [formData, setFormData] = useState({
    userName: '',
    userEmail: '',
    content: '',
  });
  const [status, setStatus] = useState('idle'); // 'idle' | 'loading' | 'success' | 'error'
  const [message, setMessage] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    
    if (!formData.userName || !formData.content) {
      setStatus('error');
      setMessage('نام و متن نظر الزامی است');
      return;
    }

    setStatus('loading');

    // شبیه‌سازی ارسال نظر به سرور تا زمان اتصال به دیتابیس
    setTimeout(() => {
      setStatus('success');
      setMessage('نظر شما با موفقیت ثبت شد و پس از تایید مدیر نمایش داده می‌شود.');
      setFormData({ userName: '', userEmail: '', content: '' });
    }, 1000);
  };

  return (
    <div className="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm mt-8">
      <h3 className="text-lg font-bold mb-4 flex items-center gap-2">
        <svg className="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        ثبت نظر
      </h3>

      {status === 'success' && (
        <div className="bg-green-50 text-green-700 p-4 rounded-xl mb-4 text-sm font-bold border border-green-200">
          ✅ {message}
        </div>
      )}

      {status === 'error' && message && (
        <div className="bg-red-50 text-red-700 p-4 rounded-xl mb-4 text-sm font-bold border border-red-200">
          ❌ {message}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-bold mb-2 text-gray-700">نام شما *</label>
            <input
              type="text"
              value={formData.userName}
              onChange={(e) => setFormData({ ...formData, userName: e.target.value })}
              className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-[#1B365D] focus:ring-1 focus:ring-[#1B365D] text-sm transition-all"
              placeholder="نام خود را وارد کنید"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-bold mb-2 text-gray-700">ایمیل (اختیاری)</label>
            <input
              type="email"
              value={formData.userEmail}
              onChange={(e) => setFormData({ ...formData, userEmail: e.target.value })}
              className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-[#1B365D] focus:ring-1 focus:ring-[#1B365D] text-sm transition-all"
              placeholder="ایمیل خود را وارد کنید"
            />
          </div>
        </div>
        <div>
          <label className="block text-sm font-bold mb-2 text-gray-700">متن نظر *</label>
          <textarea
            value={formData.content}
            onChange={(e) => setFormData({ ...formData, content: e.target.value })}
            rows={4}
            className="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:border-[#1B365D] focus:ring-1 focus:ring-[#1B365D] text-sm transition-all resize-none"
            placeholder="نظر خود را بنویسید..."
            required
          />
        </div>
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
          <p className="text-xs text-gray-500 bg-gray-50 px-3 py-2 rounded-lg w-full sm:w-auto">
            💡 نظر شما پس از تایید مدیر سایت نمایش داده خواهد شد.
          </p>
          <button
            type="submit"
            disabled={status === 'loading'}
            className="w-full sm:w-auto px-8 py-3 bg-[#1B365D] text-white rounded-xl font-bold text-sm hover:bg-[#2E5090] transition-colors disabled:opacity-50 shadow-md"
          >
            {status === 'loading' ? 'در حال ارسال...' : 'ارسال نظر'}
          </button>
        </div>
      </form>
    </div>
  );
}