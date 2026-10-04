(function() {
    const rawMap = {
        '❤️': 'tabler:heart-filled',
        '❤': 'tabler:heart-filled',
        '🤍': 'tabler:heart',
        '✨': 'lucide:sparkles',
        '🛡️': 'lucide:shield',
        '🛡': 'lucide:shield',
        '⚠️': 'lucide:triangle-alert',
        '⚠': 'lucide:triangle-alert',
        '✅': 'lucide:circle-check',
        '🔍': 'lucide:search',
        '💡': 'lucide:lightbulb',
        '💉': 'lucide:syringe',
        '🏥': 'lucide:hospital',
        '🔒': 'lucide:lock',
        '📰': 'lucide:newspaper',
        '🏡': 'lucide:home',
        '🐾': 'lucide:paw-print',
        '💖': 'tabler:heart-spark',
        '📝': 'lucide:pencil',
        '🕒': 'lucide:clock',
        '🔔': 'lucide:bell',
        '🚪': 'lucide:log-out',
        '📅': 'lucide:calendar',
        '✉️': 'lucide:mail',
        '✉': 'lucide:mail',
        '📍': 'lucide:map-pin',
        '📞': 'lucide:phone',
        '📘': 'lucide:facebook',
        '📸': 'lucide:instagram',
        '✓': 'lucide:check',
        '✔️': 'lucide:check',
        '💚': 'tabler:heart-filled',
        '❌': 'lucide:circle-x',
        '➕': 'lucide:plus',
        '🗑️': 'lucide:trash-2',
        '🗑': 'lucide:trash-2',
        '💾': 'lucide:save',
        '📄': 'lucide:file-text',
        '💬': 'lucide:message-square',
        '🌸': 'tabler:flower',
        '📊': 'lucide:bar-chart-3',
        '📨': 'lucide:mail-plus',
        '👁️': 'lucide:eye',
        '👁': 'lucide:eye',
        '⚙️': 'lucide:settings',
        '⚙': 'lucide:settings',
        '🚫': 'lucide:ban',
        '◀': 'lucide:chevron-left',
        '👥': 'lucide:users',
        '👤': 'lucide:user',
        '▾': 'lucide:chevron-down'
    };

    const emojiMap = {};
    for (const [key, val] of Object.entries(rawMap)) {
        const cleanKey = key.replace(/\uFE0F/g, '');
        emojiMap[cleanKey] = val;
    }

    const sortedKeys = Object.keys(rawMap).sort((a, b) => b.length - a.length);
    const regexPattern = sortedKeys.map(k => {
        const escaped = k.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
        return escaped.replace(/\\uFE0F/g, '') + '\\uFE0F?';
    }).join('|');
    const emojiRegex = new RegExp(regexPattern, 'g');

    function injectStyles() {
        if (document.getElementById('emoji-replacer-styles')) return;
        const style = document.createElement('style');
        style.id = 'emoji-replacer-styles';
        style.textContent = `
            span.iconify.emoji-icon {
                display: inline-block;
                width: 1em;
                height: 1em;
                vertical-align: middle;
                line-height: 1;
                margin: 0 0.1em;
            }
        `;
        document.head.appendChild(style);
    }

    function replaceEmojisInTextNode(node) {
        if (!node.nodeValue) return;
        const text = node.nodeValue;

        emojiRegex.lastIndex = 0;
        if (!emojiRegex.test(text)) return;

        const parent = node.parentNode;
        if (!parent) return;

        const tag = parent.tagName ? parent.tagName.toLowerCase() : '';
        if (
            tag === 'script' || tag === 'style' || tag === 'textarea' || tag === 'code' ||
            (parent.classList && parent.classList.contains('iconify')) ||
            (parent.closest && parent.closest('.iconify'))
        ) {
            return;
        }

        emojiRegex.lastIndex = 0;
        const fragments = document.createDocumentFragment();
        let lastIndex = 0;
        let match;
        let hasMatch = false;

        while ((match = emojiRegex.exec(text)) !== null) {
            hasMatch = true;
            const matchText = match[0];
            const matchIndex = match.index;

            if (matchIndex > lastIndex) {
                fragments.appendChild(document.createTextNode(text.substring(lastIndex, matchIndex)));
            }

            const cleanKey = matchText.replace(/\uFE0F/g, '');
            const iconName = emojiMap[cleanKey];
            if (iconName) {
                const span = document.createElement('span');
                span.className = 'iconify emoji-icon';
                span.setAttribute('data-icon', iconName);
                fragments.appendChild(span);
            } else {
                fragments.appendChild(document.createTextNode(matchText));
            }

            lastIndex = emojiRegex.lastIndex;
        }

        if (hasMatch) {
            if (lastIndex < text.length) {
                fragments.appendChild(document.createTextNode(text.substring(lastIndex)));
            }
            parent.replaceChild(fragments, node);
        }
    }

    // 只处理元素节点，跳过注释节点、文档片段等没有 classList 属性的节点类型，避免 TypeError
    function walkDOM(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            replaceEmojisInTextNode(node);
            return;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        const tag = node.tagName ? node.tagName.toLowerCase() : '';
        if (tag === 'script' || tag === 'style' || tag === 'textarea' || tag === 'code' || (node.classList && node.classList.contains('iconify'))) {
            return;
        }

        const children = Array.from(node.childNodes);
        for (let i = 0; i < children.length; i++) {
            walkDOM(children[i]);
        }
    }

    function runScan() {
        injectStyles();
        walkDOM(document.body);
        if (window.Iconify && typeof window.Iconify.scan === 'function') {
            window.Iconify.scan();
        }
    }

    function initObserver() {
        const observer = new MutationObserver((mutations) => {
            observer.disconnect();

            let needsScan = false;
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    for (const node of mutation.addedNodes) {
                        walkDOM(node);
                        needsScan = true;
                    }
                } else if (mutation.type === 'characterData') {
                    const node = mutation.target;
                    if (node.nodeType === Node.TEXT_NODE) {
                        replaceEmojisInTextNode(node);
                        needsScan = true;
                    }
                }
            }

            if (needsScan && window.Iconify && typeof window.Iconify.scan === 'function') {
                window.Iconify.scan();
            }

            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            runScan();
            initObserver();
        });
    } else {
        runScan();
        initObserver();
    }
})();