import './bootstrap';

import Alpine from 'alpinejs';
import brandRegistration from './brand-registration';
import QRCode from 'qrcode';

window.Alpine = Alpine;
Alpine.data('brandRegistration', brandRegistration);

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
