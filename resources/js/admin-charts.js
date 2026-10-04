import Chart from 'chart.js/auto';

const source = document.getElementById('admin-chart-data');

if (source) {
    const data = JSON.parse(source.textContent);
    const grid = 'rgba(148, 163, 184, .14)';
    const text = document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#64748b';
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const charts = [];
    const createChart = (canvas, config) => {
        if (!canvas) return;
        if (reducedMotion.matches) config.options.animation = false;
        charts.push(new Chart(canvas, config));
    };
    const tooltip = {
        backgroundColor: '#0f172a',
        titleColor: '#fff',
        bodyColor: '#cbd5e1',
        padding: 12,
        cornerRadius: 12,
        displayColors: true,
    };

    Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.color = text;

    createChart(document.getElementById('returnTrendChart'), {
        type: 'line',
        data: {
            labels: data.returnTrend.map(item => item.label),
            datasets: [
                {
                    label: 'Retours',
                    data: data.returnTrend.map(item => item.count),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, .12)',
                    fill: true,
                    tension: .42,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 3,
                },
                {
                    label: 'Poids (kg)',
                    yAxisID: 'weight',
                    data: data.returnTrend.map(item => item.weight),
                    borderColor: '#38bdf8',
                    backgroundColor: 'transparent',
                    tension: .42,
                    borderWidth: 2,
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1200, easing: 'easeOutQuart' },
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8 } }, tooltip },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { beginAtZero: true, title: { display: true, text: 'Retours' }, grid: { color: grid }, border: { display: false }, ticks: { precision: 0 } },
                weight: { position: 'right', beginAtZero: true, title: { display: true, text: 'Poids (kg)' }, grid: { drawOnChartArea: false }, border: { display: false } },
            },
        },
    });

    createChart(document.getElementById('returnStatusChart'), {
        type: 'doughnut',
        data: {
            labels: data.statusBreakdown.map(item => item.label),
            datasets: [{
                data: data.statusBreakdown.map(item => item.value),
                backgroundColor: ['#f59e0b', '#38bdf8', '#8b5cf6', '#10b981', '#f43f5e'],
                borderWidth: 0,
                hoverOffset: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            animation: { animateRotate: true, duration: 1300 },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18, boxWidth: 8 } }, tooltip },
        },
    });

    createChart(document.getElementById('scoreRadarChart'), {
        type: 'radar',
        data: {
            labels: Object.keys(data.scoreAverages),
            datasets: [{
                label: 'Score moyen',
                data: Object.values(data.scoreAverages),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, .18)',
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1400 },
            scales: { r: { min: 0, max: 100, ticks: { display: false }, grid: { color: grid }, angleLines: { color: grid }, pointLabels: { color: text, font: { size: 11, weight: 600 } } } },
            plugins: { legend: { display: false }, tooltip },
        },
    });

    createChart(document.getElementById('programTypeChart'), {
        type: 'bar',
        data: {
            labels: data.programTypes.map(item => item.label),
            datasets: [{
                label: 'Programmes',
                data: data.programTypes.map(item => item.value),
                backgroundColor: ['#10b981', '#38bdf8', '#8b5cf6', '#f59e0b', '#14b8a6'],
                borderRadius: 10,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1100, easing: 'easeOutBounce' },
            plugins: { legend: { display: false }, tooltip },
            scales: { x: { grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, border: { display: false }, ticks: { precision: 0 } } },
        },
    });

    const updateTheme = () => {
        const color = document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#64748b';
        charts.forEach(chart => {
            chart.options.color = color;
            if (chart.options.plugins.legend) chart.options.plugins.legend.labels.color = color;
            Object.values(chart.options.scales || {}).forEach(scale => {
                if (scale.ticks) scale.ticks.color = color;
                if (scale.title) scale.title.color = color;
                if (scale.pointLabels) scale.pointLabels.color = color;
            });
            chart.update('none');
        });
    };
    updateTheme();
    new MutationObserver(updateTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}
