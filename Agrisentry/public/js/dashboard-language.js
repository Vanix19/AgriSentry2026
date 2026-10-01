// Shared by all web pages, including content rendered after API and sensor updates.
(() => {
    const languages = {en: 'English', ceb: 'Cebuano', fil: 'Filipino', es: 'Español (Spanish)'};
    const normalize = text => String(text).replace(/\s+/g, ' ').trim().toLowerCase();
    const entries = new Map(), templates = [];
    const escapeRegex = value => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    for (const row of (window.agrisentryTranslationRows || '').trim().split('\n')) {
        const [en, ceb, fil, es] = row.split('|');
        if (!en || !ceb || !fil || !es) continue;
        const entry = {en, ceb, fil, es};
        entries.set(normalize(en), entry);
        if (en.includes('{')) {
            const keys = [...en.matchAll(/\{(\w+)\}/g)].map(match => match[1]);
            templates.push({pattern: new RegExp('^' + en.split(/\{\w+\}/).map(escapeRegex).join('(.+?)') + '$', 'i'), keys, entry});
        }
    }
    let language = 'en';
    try { language = localStorage.getItem('agrisentry-language') || 'en'; } catch (_) {}
    if (!languages[language]) language = 'en';
    const renderedSources = new Map();
    function translate(source, depth = 0) {
        const text = String(source ?? '');
        if (language === 'en' || depth > 5 || !text.trim()) return text;
        const trimmed = text.trim(), entry = entries.get(normalize(trimmed));
        let output;
        if (entry) output = entry[language];
        else for (const template of templates) {
            const match = trimmed.match(template.pattern);
            if (!match) continue;
            output = template.entry[language].replace(/\{(\w+)\}/g, (_, key) => {
                const value = match[template.keys.indexOf(key) + 1];
                return ['field', 'unit', 'status', 'period'].includes(key) ? translate(value, depth + 1) : value;
            });
            break;
        }
        if (output === undefined) {
            const parts = trimmed.split(/(\s*[—–·•:→←›‹📊🗑+⚠]+\s*|(?<=[.!?])\s+(?=[A-Z])|\s+[(/]|[)])/u);
            output = parts.length > 1 ? parts.map(part => translate(part, depth + 1)).join('') : trimmed;
        }
        return text.slice(0, text.indexOf(trimmed)) + output + text.slice(text.indexOf(trimmed) + trimmed.length);
    }
    window.dashboardText = source => {
        const output = translate(source);
        if (output !== source) renderedSources.set(output, String(source));
        return output;
    };
    window.agrisentryLanguage = () => language;
    const textRecords = new WeakMap(), attributeRecords = new WeakMap();
    window.agrisentryOriginalText = element => element ? [...element.childNodes].map(node => textRecords.get(node)?.source ?? node.textContent).join('').trim() : '';
    const excluded = 'script,style,noscript,textarea,code,pre,[translate="no"],[data-no-translate],.goat-tile-name,.goat-name,#profile-name,.animal-cell b,.assigned-goat,.avatar,.brand-name,#ai-photo-name,.chat-message.user .chat-text';
    function translateNode(node) {
        const parent = node.parentElement;
        if (!parent || parent.closest(excluded) || !node.textContent.trim()) return;
        const current = node.textContent;
        let record = textRecords.get(node);
        if (!record || current !== record.output) record = {source: renderedSources.get(current) || current};
        record.output = translate(record.source);
        textRecords.set(node, record);
        if (current !== record.output) node.textContent = record.output;
    }
    const attributes = ['placeholder', 'title', 'aria-label', 'alt', 'label'];
    function translateElement(element) {
        if (element.closest(excluded)) return;
        // Freeze implicit option values before changing their visible labels.
        if (element.tagName === 'OPTION' && !element.hasAttribute('value')) element.value = element.textContent;
        let records = attributeRecords.get(element);
        if (!records) { records = {}; attributeRecords.set(element, records); }
        for (const attribute of attributes) {
            if (!element.hasAttribute(attribute)) continue;
            const current = element.getAttribute(attribute);
            let record = records[attribute];
            if (!record || current !== record.output) record = {source: current};
            record.output = translate(record.source);
            records[attribute] = record;
            if (current !== record.output) element.setAttribute(attribute, record.output);
        }
    }
    function apply(root = document.body) {
        if (!root) return;
        if (root.nodeType === Node.TEXT_NODE) { translateNode(root); return; }
        if (root.nodeType !== Node.ELEMENT_NODE && root !== document) return;
        if (root.nodeType === Node.ELEMENT_NODE) translateElement(root);
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeType === Node.ELEMENT_NODE) translateElement(node);
            else translateNode(node);
        }
    }
    const observer = new MutationObserver(changes => {
        observer.disconnect();
        for (const change of changes) {
            if (change.type === 'childList') change.addedNodes.forEach(node => apply(node));
            else if (change.type === 'characterData') translateNode(change.target);
            else translateElement(change.target);
        }
        observe();
    });
    function observe() {
        observer.observe(document.documentElement, {subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: attributes});
    }
    window.changeDashboardLanguage = selected => {
        language = languages[selected] ? selected : 'en';
        try { localStorage.setItem('agrisentry-language', language); } catch (_) {}
        document.documentElement.lang = language;
        observer.disconnect();
        document.querySelectorAll('[data-language-select],#dashboard-language').forEach(select => { select.value = language; });
        apply(document.body);
        apply(document.querySelector('title'));
        document.dispatchEvent(new CustomEvent('agrisentry:language', {detail: {language}}));
        // Chart and screen listeners can rebuild their labels during the event.
        apply(document.body);
        observe();
    };
    for (const name of ['confirm', 'alert']) {
        const original = window[name].bind(window);
        window[name] = message => original(translate(message));
    }
    function addLanguageSelector() {
        if (document.getElementById('dashboard-language')) return;
        const container = document.createElement('div');
        container.className = 'system-language-control';
        const label = document.createElement('label');
        label.htmlFor = 'system-language'; label.textContent = 'Language';
        const select = document.createElement('select');
        select.id = 'system-language'; select.dataset.languageSelect = '';
        for (const [value, label] of Object.entries(languages)) select.add(new Option(label, value));
        select.addEventListener('change', () => window.changeDashboardLanguage(select.value));
        container.append(label, select);
        const sidebar = document.querySelector('.sidebar-foot'), nav = document.querySelector('.account-nav');
        if (sidebar) sidebar.prepend(container);
        else if (nav) nav.prepend(container);
        else { container.classList.add('system-language-floating'); document.body.prepend(container); }
    }
    document.documentElement.lang = language;
    document.addEventListener('invalid', event => {
        const input = event.target;
        if (!input.setCustomValidity) return;
        input.setCustomValidity('');
        if (language === 'en') return;
        const message = input.validity.valueMissing ? 'Please fill out this field.'
            : input.validity.typeMismatch && input.type === 'email' ? 'Please enter a valid email address.'
            : 'Please enter a valid value.';
        input.setCustomValidity(translate(message));
    }, true);
    document.addEventListener('input', event => event.target.setCustomValidity?.(''));
    document.addEventListener('DOMContentLoaded', () => {
        addLanguageSelector(); window.changeDashboardLanguage(language);
    });
    window.addEventListener('storage', event => {
        if (event.key === 'agrisentry-language') window.changeDashboardLanguage(event.newValue);
    });
})();
