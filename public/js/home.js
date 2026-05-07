(() => {
    const payload = window.candidateTrendData;
    const chartEl = document.getElementById('candidateTrendChart');

    if (!payload || !chartEl || typeof Chart === 'undefined') {
        return;
    }

    const context = chartEl.getContext('2d');

    new Chart(context, {
        type: 'line',
        data: {
            labels: payload.labels,
            datasets: [
                {
                    label: 'Candidates Added',
                    data: payload.values,
                    borderColor: '#17a05d',
                    backgroundColor: 'rgba(23, 160, 93, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                    grid: {
                        color: 'rgba(16, 45, 77, 0.08)',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });

    const chartColors = ['#17a05d', '#3268a8', '#b7791f', '#b73a3a'];
    const makeDoughnut = (elementId, dataset) => {
        const element = document.getElementById(elementId);

        if (!element || !dataset) {
            return;
        }

        new Chart(element.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: dataset.labels,
                datasets: [
                    {
                        data: dataset.values,
                        backgroundColor: chartColors,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            usePointStyle: true,
                        },
                    },
                },
            },
        });
    };

    makeDoughnut('clientStatusChart', payload.client);
    makeDoughnut('interviewStatusChart', payload.interview);
})();
