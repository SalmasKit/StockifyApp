(function () {
  function initChart() {
    const chartCanvas = document.getElementById('categoryChart');
    if (!chartCanvas) return;

    const labelsRaw = chartCanvas.dataset.labels;
    const valuesRaw = chartCanvas.dataset.values;

    console.log("Chart Labels Raw:", labelsRaw);
    console.log("Chart Values Raw:", valuesRaw);

    let labels = [];
    let values = [];

    try {
      labels = JSON.parse(labelsRaw || '[]');
      values = JSON.parse(valuesRaw || '[]');
    } catch (e) {
      console.error("JSON Parse Error for Chart Data:", e);
    }

    const ctx = chartCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, '#3b82f6');
    gradient.addColorStop(1, '#60a5fa00');

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: gradient,
          hoverBackgroundColor: '#2563eb',
          borderRadius: 12,
          borderSkipped: false,
          barThickness: 28
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#f8fafc', drawTicks: false },
            border: { display: false },
            ticks: { color: '#94a3b8', font: { weight: '800', size: 9 }, padding: 10 }
          },
          x: {
            grid: { display: false },
            ticks: { color: '#475569', font: { weight: '800', size: 10 }, padding: 10 }
          }
        }
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChart);
  } else {
    initChart();
  }
})();
