/**
 * Swissturn Tool Management System
 * Chart Configuration and Utilities
 *
 * This file contains chart-related functions with built-in limits
 * to prevent charts from becoming infinitely long
 */

// Maximum data points in any chart (from constants.php CHART_MAX_DATA_POINTS)
const MAX_CHART_DATA_POINTS = 30;

/**
 * Create a line chart with data point limiting
 */
function createLineChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    // Limit data points to prevent infinite charts
    const limitedData = limitChartData(data);

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    };

    return new Chart(ctx, {
        type: 'line',
        data: limitedData,
        options: { ...defaultOptions, ...options }
    });
}

/**
 * Create a bar chart with data point limiting
 */
function createBarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    // Limit data points
    const limitedData = limitChartData(data);

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    };

    return new Chart(ctx, {
        type: 'bar',
        data: limitedData,
        options: { ...defaultOptions, ...options }
    });
}

/**
 * Create a pie/doughnut chart with data point limiting
 */
function createPieChart(canvasId, data, options = {}, type = 'doughnut') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    // Limit data points
    const limitedData = limitChartData(data);

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    };

    return new Chart(ctx, {
        type: type,
        data: limitedData,
        options: { ...defaultOptions, ...options }
    });
}

/**
 * Limit chart data to MAX_CHART_DATA_POINTS
 * This prevents charts from becoming infinitely long
 */
function limitChartData(data) {
    if (!data || !data.labels) return data;

    // If data is within limits, return as is
    if (data.labels.length <= MAX_CHART_DATA_POINTS) {
        return data;
    }

    // Otherwise, slice to last MAX_CHART_DATA_POINTS
    const limitedData = { ...data };
    limitedData.labels = data.labels.slice(-MAX_CHART_DATA_POINTS);

    if (data.datasets) {
        limitedData.datasets = data.datasets.map(dataset => ({
            ...dataset,
            data: dataset.data.slice(-MAX_CHART_DATA_POINTS)
        }));
    }

    return limitedData;
}

/**
 * Destroy chart instance if exists
 */
function destroyChart(chartInstance) {
    if (chartInstance && typeof chartInstance.destroy === 'function') {
        chartInstance.destroy();
    }
}

/**
 * Update chart with new data (with limiting)
 */
function updateChart(chartInstance, newData) {
    if (!chartInstance) return;

    const limitedData = limitChartData(newData);

    chartInstance.data.labels = limitedData.labels;
    chartInstance.data.datasets = limitedData.datasets;
    chartInstance.update();
}

/**
 * Get color palette for charts
 */
function getChartColors(count) {
    const colors = [
        'rgb(59, 130, 246)',   // blue
        'rgb(34, 197, 94)',    // green
        'rgb(251, 191, 36)',   // yellow
        'rgb(239, 68, 68)',    // red
        'rgb(168, 85, 247)',   // purple
        'rgb(236, 72, 153)',   // pink
        'rgb(20, 184, 166)',   // teal
        'rgb(249, 115, 22)',   // orange
        'rgb(6, 182, 212)',    // cyan
        'rgb(139, 92, 246)'    // violet
    ];

    // Repeat colors if needed
    return Array.from({ length: count }, (_, i) => colors[i % colors.length]);
}

/**
 * Get background colors with opacity
 */
function getBackgroundColors(count, opacity = 0.5) {
    return getChartColors(count).map(color =>
        color.replace('rgb', 'rgba').replace(')', `, ${opacity})`)
    );
}

// Export chart utilities
window.chartUtils = {
    createLineChart,
    createBarChart,
    createPieChart,
    limitChartData,
    destroyChart,
    updateChart,
    getChartColors,
    getBackgroundColors,
    MAX_CHART_DATA_POINTS
};
