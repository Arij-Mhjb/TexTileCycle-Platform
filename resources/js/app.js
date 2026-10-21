import './bootstrap';

import Alpine from 'alpinejs';
import brandRegistration from './brand-registration';
import QRCode from 'qrcode';
import virtualTryOn from './virtual-try-on';

window.Alpine = Alpine;
Alpine.data('brandRegistration', brandRegistration);
Alpine.data('brandAssistant', ({ endpoint, page }) => ({
    open: false, question: '', loading: false, error: '',
    messages: [{ role: 'assistant', content: 'Bonjour, je suis TexTileGuide. Dites-moi ce que vous souhaitez faire et je vous guiderai étape par étape.', links: [] }],
    askSuggestion(question) { this.question = question; this.send(); },
    async send() {
        const question = this.question.trim();
        if (question.length < 2 || this.loading) return;
        const history = this.messages.slice(-6).map(({ role, content }) => ({ role, content }));
        this.messages.push({ role: 'user', content: question, links: [] });
        this.question = ''; this.loading = true; this.error = '';
        this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; });
        try {
            const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify({ question, page, history }) });
            if (!response.ok) throw new Error(response.status === 429 ? 'Trop de demandes. Réessayez dans une minute.' : 'Assistant indisponible.');
            const data = await response.json();
            this.messages.push({ role: 'assistant', content: data.answer, links: data.links || [] });
        } catch (error) { this.error = error.message; }
        finally { this.loading = false; this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight; }); }
    },
}));
Alpine.data('virtualTryOn', virtualTryOn);

window.mentionComposer = function (searchUrl) {
    return {
        results: [], mentions: [], open: false, controller: null,
        async search() {
            const value = this.$refs.field.value.slice(0, this.$refs.field.selectionStart);
            const match = value.match(/(?:^|\s)@([\p{L}\p{N}._-]{2,})$/u);
            if (!match) return this.close();
            this.controller?.abort(); this.controller = new AbortController();
            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(match[1])}`, { signal: this.controller.signal, headers: { Accept: 'application/json' } });
                this.results = await response.json(); this.open = this.results.length > 0;
            } catch (error) { if (error.name !== 'AbortError') this.close(); }
        },
        select(member) {
            const field = this.$refs.field;
            const before = field.value.slice(0, field.selectionStart).replace(/@[^@\s]*$/, `@${member.name} `);
            const after = field.value.slice(field.selectionEnd);
            if (!this.mentions.some(item => item.id === member.id)) this.mentions.push(member);
            field.value = before + after; field.dispatchEvent(new Event('input', { bubbles: true }));
            this.close(); this.$nextTick(() => { field.focus(); field.setSelectionRange(before.length, before.length); });
        },
        close() { this.open = false; this.results = []; },
    };
};

window.locateCollectionPoints = function () {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(({ coords }) => {
        const url = new URL(window.location.href);
        url.searchParams.set('latitude', coords.latitude);
        url.searchParams.set('longitude', coords.longitude);
        window.location.assign(url.toString());
    });
};

window.qrScanner = function () {
    return {
        scanning: false, error: '', stream: null,
        async start() {
            if (!('BarcodeDetector' in window)) { this.error = 'Scanner caméra indisponible sur ce navigateur. Saisissez le code manuellement.'; return; }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.$refs.video.srcObject = this.stream; await this.$refs.video.play(); this.scanning = true;
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                const tick = async () => {
                    if (!this.scanning) return;
                    const codes = await detector.detect(this.$refs.video);
                    if (codes.length) { this.$refs.token.value = codes[0].rawValue; this.stop(); return; }
                    requestAnimationFrame(tick);
                }; tick();
            } catch (_) { this.error = 'Impossible d’ouvrir la caméra. Autorisez son accès ou saisissez le code.'; }
        },
        stop() { this.scanning = false; this.stream?.getTracks().forEach(track => track.stop()); this.stream = null; },
    };
};

Alpine.start();

document.querySelectorAll('[data-try-on-open]').forEach((button) => button.addEventListener('click', () => window.dispatchEvent(new CustomEvent('open-virtual-try-on'))));

document.querySelectorAll('[data-qr-value]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qrValue, { width: 210, margin: 1, errorCorrectionLevel: 'M' })
        .catch(() => canvas.classList.add('hidden'));
});

const notificationContainer = document.createElement('div');
notificationContainer.className = 'fixed right-4 top-4 z-[100] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-3';
notificationContainer.setAttribute('aria-live', 'polite');
document.body.appendChild(notificationContainer);

window.appNotify = function (message, type = 'success') {
    const colors = type === 'error'
        ? 'border-rose-200 bg-rose-50 text-rose-900'
        : 'border-emerald-200 bg-white text-slate-800';
    const notification = document.createElement('div');
    notification.className = `translate-x-4 rounded-2xl border p-4 opacity-0 shadow-2xl transition duration-200 ${colors}`;
    notification.innerHTML = `<div class="flex items-start gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full ${type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'} font-black text-white">${type === 'error' ? '!' : '✓'}</span><p class="flex-1 text-sm font-bold"></p><button type="button" class="text-lg leading-none text-slate-400" aria-label="Fermer">×</button></div>`;
    notification.querySelector('p').textContent = message;
    const close = () => {
        notification.classList.add('translate-x-4', 'opacity-0');
        setTimeout(() => notification.remove(), 200);
    };
    notification.querySelector('button').addEventListener('click', close);
    notificationContainer.appendChild(notification);
    requestAnimationFrame(() => notification.classList.remove('translate-x-4', 'opacity-0'));
    setTimeout(close, 5000);
};

let confirmationLayer;
function confirmAction(message) {
    if (!confirmationLayer) {
        confirmationLayer = document.createElement('div');
        confirmationLayer.className = 'fixed inset-0 z-[110] hidden place-items-center bg-slate-950/55 p-4 backdrop-blur-sm';
        confirmationLayer.innerHTML = `<div role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-100 text-xl">?</div><h2 id="app-confirm-title" class="mt-4 text-xl font-black">Confirmer l’action</h2><p data-confirm-text class="mt-2 text-sm leading-6 text-slate-500"></p><div class="mt-6 flex justify-end gap-3"><button type="button" data-confirm-cancel class="btn-secondary">Annuler</button><button type="button" data-confirm-accept class="btn-primary">Confirmer</button></div></div>`;
        document.body.appendChild(confirmationLayer);
    }
    confirmationLayer.querySelector('[data-confirm-text]').textContent = message;
    confirmationLayer.classList.remove('hidden');
    confirmationLayer.classList.add('grid');

    return new Promise((resolve) => {
        const finish = (accepted) => {
            confirmationLayer.classList.add('hidden');
            confirmationLayer.classList.remove('grid');
            resolve(accepted);
        };
        confirmationLayer.querySelector('[data-confirm-accept]').onclick = () => finish(true);
        confirmationLayer.querySelector('[data-confirm-cancel]').onclick = () => finish(false);
        confirmationLayer.onclick = (event) => { if (event.target === confirmationLayer) finish(false); };
    });
}

function validationMessage(field) {
    const label = field.closest('label')?.querySelector('span')?.textContent?.replace('*', '').trim() || 'Ce champ';
    if (field.validity.valueMissing) return `${label} est obligatoire.`;
    if (field.validity.typeMismatch && field.type === 'email') return 'Saisissez une adresse e-mail valide.';
    if (field.validity.tooShort) return `${label} doit contenir au moins ${field.minLength} caractères.`;
    if (field.validity.tooLong) return `${label} ne doit pas dépasser ${field.maxLength} caractères.`;
    if (field.validity.rangeUnderflow) return `${label} doit être supérieur ou égal à ${field.min}.`;
    if (field.validity.rangeOverflow) return `${label} doit être inférieur ou égal à ${field.max}.`;
    if (field.validity.stepMismatch || field.validity.badInput) return `${label} contient une valeur incorrecte.`;
    if (field.validity.patternMismatch) return `${label} ne respecte pas le format attendu.`;
    return field.dataset.validationMessage || `${label} n’est pas valide.`;
}

function showFieldError(field) {
    field.setAttribute('aria-invalid', 'true');
    field.classList.add('!border-rose-500', '!ring-rose-200');
    let message = field.parentElement?.querySelector(':scope > .js-field-error');
    if (!message) {
        message = document.createElement('p');
        message.className = 'js-field-error mt-1 text-sm font-semibold text-rose-600';
        field.insertAdjacentElement('afterend', message);
    }
    message.textContent = validationMessage(field);
}

function clearFieldError(field) {
    field.removeAttribute('aria-invalid');
    field.classList.remove('!border-rose-500', '!ring-rose-200');
    field.parentElement?.querySelector(':scope > .js-field-error')?.remove();
}

document.addEventListener('invalid', (event) => {
    event.preventDefault();
    showFieldError(event.target);
}, true);

document.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement || event.target instanceof HTMLSelectElement) {
        if (event.target.validity.valid) clearFieldError(event.target);
    }
}, true);

document.addEventListener('change', (event) => {
    const field = event.target;
    if (!(field instanceof HTMLInputElement) || field.type !== 'file' || !field.files?.length) return;
    const file = field.files[0];
    const acceptedTypes = (field.accept || '').split(',').map((type) => type.trim()).filter(Boolean);
    const tooLarge = field.dataset.maxBytes && file.size > Number(field.dataset.maxBytes);
    const wrongType = acceptedTypes.length && !acceptedTypes.includes(file.type);
    field.setCustomValidity(tooLarge || wrongType ? (field.dataset.validationMessage || 'Le fichier sélectionné n’est pas valide.') : '');
    if (field.validity.valid) clearFieldError(field); else showFieldError(field);
}, true);

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const submitter = event.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
    const form = submitter?.form;
    if (form && !form.checkValidity()) {
        event.preventDefault();
        form.querySelector(':invalid')?.focus();
        window.appNotify('Vérifiez les champs indiqués avant de continuer.', 'error');
    }
}, true);

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirmApproved === 'true') {
        delete form.dataset.confirmApproved;
        return;
    }

    const inlineConfirmation = form.getAttribute('onsubmit')?.match(/confirm\((['"])(.*?)\1\)/);
    const message = form.dataset.confirm || inlineConfirmation?.[2];
    if (!message) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const submitter = event.submitter;
    if (await confirmAction(message)) {
        form.dataset.confirmApproved = 'true';
        if (inlineConfirmation) form.removeAttribute('onsubmit');
        form.requestSubmit(submitter || undefined);
    }
}, true);

document.querySelectorAll('[data-flash-message]').forEach((element) => {
    window.appNotify(element.textContent.trim(), element.dataset.flashType || 'success');
});

if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
    });
}, { threshold: 0.14, rootMargin: '0px 0px -35px' });

document.querySelectorAll('.reveal-on-scroll').forEach((element, index) => {
    element.classList.add('is-pending');
    element.style.transitionDelay = `${Math.min(index % 4, 3) * 90}ms`;
    revealObserver.observe(element);
});
}
