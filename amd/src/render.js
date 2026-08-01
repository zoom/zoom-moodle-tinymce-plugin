/**
 * Render saved Zoom Classroom placeholders back into live iframes.
 *
 * @module     tiny_zoomclassroom/render
 */

import Config from 'core/config';

const containerSelector = '.tiny_zoomclassroom-embed';
const previewSelector = '.tiny_zoomclassroom-preview';
const sentinelSelector = 'img.tiny_zoomclassroom-sentinel';
const defaultTitle = 'Embedded Zoom content';
const defaultHeight = 480;

const getPluginPath = (suffix) => `${Config.wwwroot.replace(/\/$/, '')}/lib/editor/tiny/plugins/zoomclassroom/${suffix}`;
const getAllowedViewPath = () => new URL(getPluginPath('view.php')).pathname;
const getAllowedPlaceholderPath = () => new URL(getPluginPath('placeholder.php')).pathname;

const getSafeOpaqueId = (value) => {
    if (typeof value !== 'string') {
        return null;
    }

    const trimmed = value.trim();
    if (!trimmed || !/^[A-Za-z0-9_]+$/.test(trimmed)) {
        return null;
    }

    return trimmed;
};

const buildViewUrlFromReference = (reference) => {
    if (!reference || reference.type !== 'embedid') {
        return null;
    }

    const url = new URL(getPluginPath('view.php'));
    url.searchParams.set('id', reference.value);
    return url.toString();
};

const buildPlaceholderUrlFromReference = (reference) => {
    if (!reference || reference.type !== 'embedid') {
        return null;
    }

    const url = new URL(getPluginPath('placeholder.php'));
    url.searchParams.set('id', reference.value);
    return url.toString();
};

const isElementNode = (value) => Boolean(value) && value.nodeType === Node.ELEMENT_NODE;
const isIframeElement = (value) => isElementNode(value) && value.tagName === 'IFRAME';
const isImageElement = (value) => isElementNode(value) && value.tagName === 'IMG';

const createIframe = (launchUrl, title) => {
    const iframe = document.createElement('iframe');
    iframe.src = launchUrl;
    iframe.title = title || defaultTitle;
    iframe.style.width = '100%';
    iframe.style.minHeight = `${defaultHeight}px`;
    iframe.style.border = '0';
    iframe.setAttribute('allowfullscreen', 'allowfullscreen');
    iframe.setAttribute('data-zoomclassroom-rendered', '1');
    return iframe;
};

const getOrCreatePreview = (container) => {
    let preview = container.querySelector(previewSelector);
    if (!preview) {
        preview = document.createElement('div');
        preview.className = 'tiny_zoomclassroom-preview';
        container.appendChild(preview);
    }

    return preview;
};

const getReferenceFromUrl = (candidateUrl, mode) => {
    try {
        const baseUrl = new URL(Config.wwwroot);
        const parsedUrl = new URL(candidateUrl, window.location.origin);
        if (parsedUrl.origin !== baseUrl.origin) {
            return null;
        }

        if (mode === 'view' && parsedUrl.pathname !== getAllowedViewPath()) {
            return null;
        }

        if (mode === 'placeholder' && parsedUrl.pathname !== getAllowedPlaceholderPath()) {
            return null;
        }

        const embedid = getSafeOpaqueId(parsedUrl.searchParams.get('id') || '');
        if (!embedid) {
            return null;
        }

        return {
            type: 'embedid',
            value: embedid,
        };
    } catch {
        return null;
    }
};

const getReference = (container) => {
    const embedid = getSafeOpaqueId(container.getAttribute('data-embed-id') || '');
    if (embedid) {
        return {
            type: 'embedid',
            value: embedid,
        };
    }

    const sentinel = container.querySelector(sentinelSelector);
    if (isImageElement(sentinel)) {
        return getReferenceFromUrl(sentinel.getAttribute('src') || sentinel.src || '', 'placeholder');
    }

    return null;
};

const ensureSentinel = (container, reference) => {
    const placeholderUrl = buildPlaceholderUrlFromReference(reference);
    if (!placeholderUrl) {
        return null;
    }

    let sentinel = container.querySelector(sentinelSelector);
    if (!sentinel) {
        sentinel = document.createElement('img');
        sentinel.className = 'tiny_zoomclassroom-sentinel';
        sentinel.alt = '';
        sentinel.setAttribute('role', 'presentation');
        sentinel.setAttribute('aria-hidden', 'true');
        sentinel.width = 1;
        sentinel.height = 1;
        sentinel.style.display = 'none';
        container.appendChild(sentinel);
    }

    sentinel.src = placeholderUrl;
    sentinel.style.display = 'none';
    sentinel.setAttribute('aria-hidden', 'true');
    return sentinel;
};

const getPlaceholderTitle = (container, existingFrame = null) =>
    container.getAttribute('data-title') ||
    (isIframeElement(existingFrame) ? existingFrame.title : '') ||
    defaultTitle;

const normalizeContainer = (container, reference, title) => {
    container.setAttribute('data-embed-id', reference.value);

    if (title) {
        container.setAttribute('data-title', title);
    }

    ensureSentinel(container, reference);
};

const hydrateContainer = (container) => {
    if (!isElementNode(container)) {
        return;
    }

    const reference = getReference(container);
    const launchUrl = buildViewUrlFromReference(reference);
    if (!reference || !launchUrl) {
        return;
    }

    const preview = getOrCreatePreview(container);
    const existingFrame = preview.querySelector('iframe[data-zoomclassroom-rendered="1"], iframe');
    const title = getPlaceholderTitle(container, existingFrame);

    normalizeContainer(container, reference, title);

    const currentFrameReference = isIframeElement(existingFrame)
        ? getReferenceFromUrl(existingFrame.getAttribute('src') || existingFrame.src || '', 'view')
        : null;
    if (currentFrameReference && currentFrameReference.value === reference.value) {
        return;
    }

    preview.replaceChildren(createIframe(launchUrl, title));
    container.setAttribute('data-zoomclassroom-hydrated', '1');
};

const dehydrateContainer = (container) => {
    if (!isElementNode(container)) {
        return;
    }

    const reference = getReference(container);
    if (!reference) {
        return;
    }

    const preview = getOrCreatePreview(container);
    const existingFrame = preview.querySelector('iframe[data-zoomclassroom-rendered="1"], iframe');
    const title = getPlaceholderTitle(container, existingFrame);

    normalizeContainer(container, reference, title);
    preview.replaceChildren(document.createTextNode(title));
    container.removeAttribute('data-zoomclassroom-hydrated');
};

const hydrateNode = (node) => {
    if (!node || typeof node !== 'object') {
        return;
    }

    if (isElementNode(node) && node.matches && node.matches(containerSelector)) {
        hydrateContainer(node);
    }

    if (isElementNode(node) && typeof node.querySelectorAll === 'function') {
        node.querySelectorAll(containerSelector).forEach(hydrateContainer);
    }
};

export const downgradePlaceholdersInRoot = (root = document) => {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    root.querySelectorAll(containerSelector).forEach(dehydrateContainer);
};

export const upgradePlaceholdersInRoot = (root = document) => {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    root.querySelectorAll(containerSelector).forEach(hydrateContainer);
};

let observerStarted = false;

export const init = () => {
    const run = () => {
        upgradePlaceholdersInRoot(document);

        if (observerStarted || typeof MutationObserver === 'undefined' || !document.body) {
            return;
        }

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => hydrateNode(node));
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
        observerStarted = true;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
        return;
    }

    run();
};

export default {
    init,
    upgradePlaceholdersInRoot,
    downgradePlaceholdersInRoot,
};
