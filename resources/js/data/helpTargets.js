/**
 * Single source of truth for where each admin help topic lives.
 * Help.jsx renders it, check-help-links.mjs verifies it against real routes.
 */
export const TARGETS = {
    login: '/admin/change-password',
    logout: '/admin/dashboard',

    logo: '/admin/settings/logo',
    license: '/admin/settings/license',
    about: '/admin/settings/about',
    contact: '/admin/settings/contact',
    search: '/admin/settings/search',
    nav: '/admin/settings/nav',
    social: '/admin/settings/social',
    footer: '/admin/settings/footer',

    hero: '/admin/homepage/hero',
    'hero-side': '/admin/homepage/hero',
    'slot-image': '/admin/image-settings',
    latest: '/admin/homepage/latest',
    'latest-order': '/admin/homepage/latest',
    'latest-approve': '/admin/external-news',
    'english-titles': '/admin/articles',
    analysis: '/admin/homepage/analysis',
    events: '/admin/events',
    'external-news': '/admin/external-news',
    'agency-news-pages': '/admin/external-news',
    'agency-news-approve': '/admin/external-news',

    'create-news': '/admin/articles/new',
    'quick-new': '/admin/articles/new',
    'news-placement': '/admin/homepage/hero',
    'instant-publish': '/admin/articles',
    'news-archive-page': '/admin/articles',
    'news-archive-menu': '/admin/articles',
    'auto-archive': '/admin/articles',
    'edit-news': '/admin/articles',
    'delete-news': '/admin/articles',
    'news-status': '/admin/articles',
    'news-categories': '/admin/categories',
    'news-tags': '/admin/categories',
    'featured-image': '/admin/image-settings',
    seo: '/admin/articles',

    'image-settings': '/admin/image-settings',
    'image-crop': '/admin/image-settings',
    'image-format': '/admin/image-settings',
    'newspaper-tab': '/admin/newspapers',
    frontpages: '/admin/newspapers',
    shortlinks: '/admin/shortlinks',
    gallery: '/admin/gallery',

    'instant-news': '/admin/external-news',
    'custom-news': '/admin/articles/new',
    'archive-custom': '/admin/articles',
    submissions: '/admin/submissions',

    'static-pages': '/admin/static-pages',
    'manager-message': '/admin/shutdown',

    categories: '/admin/categories',
    regions: '/admin/regions',
    comments: '/admin/comments',
    newsletter: '/admin/subscribers',
    users: '/admin/users',
    media: '/admin/media',

    shutdown: '/admin/shutdown',
    backup: '/admin/backup',
    'backup-download': '/admin/backup',
    'backup-telegram': '/admin/backup',
    'backup-settings': '/admin/backup',

    rss: '/admin/rss-sources',
    'rss-sources': '/admin/rss-sources',
    'rss-fetch': '/admin/rss-sources',
    'rss-approve': '/admin/rss-sources',
    'rss-delete': '/admin/rss-sources',

    newspapers: '/admin/newspapers',
    'newspapers-sources': '/admin/newspapers',
    'newspapers-issues': '/admin/newspapers',
    'newspapers-logs': '/admin/newspapers',

    help: '/admin/help',
};

/** Exact key first, then a "key-" prefix match. */
export function targetFor(id) {
    if (TARGETS[id]) return TARGETS[id];
    const key = Object.keys(TARGETS)
        .filter((k) => id.startsWith(k + '-'))
        .sort((a, b) => b.length - a.length)[0];
    return key ? TARGETS[key] : '/admin/help';
}

export default TARGETS;