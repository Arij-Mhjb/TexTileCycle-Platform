// Graphiques du module Impact ESG (couleur sky).
// Chaque <canvas data-esg-chart='{"type":"bar","labels":[],"datasets":[]}'> est rendu automatiquement.
import Chart from 'chart.js/auto';

const palette = ['#0284c7', '#06b6d4', '#38bdf8', '#0e7490', '#7dd3fc', '#155e75'];
const gradeColors = { A: '#10b981', B: '#84cc16', C: '#facc15', D: '#f97316', E: '#e11d48' };
const dark = document.documentElement.classList.contains('dark');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = dark ? '#cbd5e1' : '#64748b';

document.querySelectorAll('canvas[data-esg-chart]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.esgChart);
    const isRound = ['doughnut', 'pie', 'polarArea'].includes(config.type);

    config.datasets.forEach((dataset, index) => {
        const colors = config.grades
            ? config.labels.map((label) => gradeColors[label] ?? palette[0])
            : (isRound ? palette : palette[index % palette.length]);
        dataset.backgroundColor ??= Array.isArray(colors) ? colors : `${colors}cc`;
        dataset.borderColor ??= config.type === 'line' || config.type === 'radar' ? palette[index % palette.length] : 'transparent';
        dataset.borderWidth ??= config.type === 'line' || config.type === 'radar' ? 3 : 0;
        dataset.borderRadius ??= config.type === 'bar' ? 8 : 0;
        dataset.tension ??= 0.4;
        if (config.type === 'radar') dataset.backgroundColor = `${palette[index % palette.length]}33`;
        if (config.type === 'line') { dataset.fill = true; dataset.backgroundColor = 'rgba(2, 132, 199, .12)'; }
    });

    new Chart(canvas, {
        type: config.type,
        data: { labels: config.labels, datasets: config.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion ? false : undefined,
            indexAxis: config.horizontal ? 'y' : 'x',
            plugins: {
                legend: { display: isRound || config.datasets.length > 1, position: 'bottom' },
                tooltip: { backgroundColor: '#0c4a6e', padding: 12, cornerRadius: 12 },
            },
            scales: isRound ? {} : (config.type === 'radar'
                ? { r: { min: 0, max: 100, grid: { color: 'rgba(148,163,184,.2)' }, angleLines: { color: 'rgba(148,163,184,.2)' }, ticks: { display: false } } }
                : { x: { grid: { display: false }, stacked: !!config.stacked }, y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.14)' }, stacked: !!config.stacked } }),
        },
    });
});
