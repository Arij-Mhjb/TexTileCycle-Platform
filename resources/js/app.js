import './bootstrap';

import Alpine from 'alpinejs';
import brandRegistration from './brand-registration';
import QRCode from 'qrcode';

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

document.querySelectorAll('[data-qr-value]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qrValue, { width: 210, margin: 1, errorCorrectionLevel: 'M' })
        .catch(() => canvas.classList.add('hidden'));
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
