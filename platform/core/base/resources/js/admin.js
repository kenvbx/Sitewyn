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

document.readyState !== 'loading' ? initEditors() : document.addEventListener('DOMContentLoaded', initEditors, { once: true });
