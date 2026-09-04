import '@tabler/core/dist/js/tabler.min.js';

window.Sitewyn = window.Sitewyn || {};
window.Sitewyn.admin = {
    ui: 'tabler',
};

function initEditors() {
    if (! document.querySelector('[data-admin-editor]')) {
        return;
    }

    import('./admin/editor.js').then((module) => module.initEditors());
}

function initAdmin() {
    initEditors();
}

document.readyState !== 'loading' ? initAdmin() : document.addEventListener('DOMContentLoaded', initAdmin, { once: true });
