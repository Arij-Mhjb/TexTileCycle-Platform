import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

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
