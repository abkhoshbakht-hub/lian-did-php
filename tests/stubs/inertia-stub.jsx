/**
 * Minimal Inertia stub so public components can be server-rendered outside a browser.
 * We are testing OUR components, not Inertia internals.
 */
import React from 'react';

const page = {
    props: { auth: { user: null }, flash: {} },
    url: '/',
    component: 'Home',
    version: 'test',
};

export function usePage() {
    return page;
}

export function Link({ href, children, className, ...rest }) {
    return React.createElement('a', { href, className, ...rest }, children);
}

export function Head({ children }) {
    return React.createElement(React.Fragment, null, children);
}

export function router() {
    return {};
}

export function useForm() {
    return { data: {}, post() {}, processing: false, errors: {}, setError() {}, reset() {} };
}

export function useInertiaForm() {
    return useForm();
}

export default { usePage, Link, Head, router, useForm };