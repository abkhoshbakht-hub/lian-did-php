import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

/**
 * Generic CRUD screen for the simple admin lists (categories, users,
 * subscribers, comments, regions, media ...).
 */
export default function AdminList({ title, columns = [], rows = [], empty = 'موردی ثبت نشده است.', children }) {
    return (
        <AdminLayout title={title}>
            <Head title={`${title} | پنل لیان دید`} />

            <div className="mb-5 flex items-center justify-between">
                <h1 className="text-lg font-black text-[#1B365D]">{title}</h1>
                <span className="text-xs font-bold text-gray-400">{rows.length} مورد</span>
            </div>

            {children}

            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-sm">
                        <thead className="bg-gray-50 text-gray-500 text-xs">
                            <tr>{columns.map((c) => <th key={c.key} className="p-3 font-bold whitespace-nowrap">{c.label}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {rows.length === 0 && (
                                <tr><td colSpan={columns.length} className="text-center py-12 text-gray-400">{empty}</td></tr>
                            )}
                            {rows.map((row, i) => (
                                <tr key={row.id ?? i} className="hover:bg-gray-50/70">
                                    {columns.map((c) => (
                                        <td key={c.key} className="p-3 text-gray-700 whitespace-nowrap">
                                            {c.render ? c.render(row) : (row[c.key] ?? '—')}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
