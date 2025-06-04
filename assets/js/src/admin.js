import Chart from 'chart.js/auto';

class RegistrationSourceAdmin {
    constructor() {
        this.init();
    }
    
    init() {
        this.initCharts();
        this.initBulkActions();
    }
    
    async initCharts() {
        const chartContainer = document.getElementById('registration-source-chart');
        const widgetChartContainer = document.getElementById('registration-source-widget-chart');
        
        if (chartContainer || widgetChartContainer) {
            try {
                const response = await fetch('/wp-json/registration-source/v1/statistics', {
                    credentials: 'same-origin',
                    headers: {
                        'X-WP-Nonce': registrationSourceAdmin.nonce
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch statistics');
                }
                
                const data = await response.json();
                
                if (chartContainer) {
                    this.createChart(chartContainer, data.statistics);
                }
                
                if (widgetChartContainer) {
                    this.createChart(widgetChartContainer, data.statistics, true);
                }
            } catch (error) {
                console.error('Error loading registration statistics:', error);
            }
        }
    }
    
    createChart(container, statistics, isWidget = false) {
        const ctx = container.getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statistics.map(stat => stat.label),
                datasets: [{
                    data: statistics.map(stat => stat.count),
                    backgroundColor: this.generateColors(statistics.length),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: isWidget ? 'bottom' : 'right',
                        labels: {
                            boxWidth: isWidget ? 12 : 16,
                            font: {
                                size: isWidget ? 11 : 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const stat = statistics[context.dataIndex];
                                return `${stat.label}: ${stat.count} (${stat.percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    generateColors(count) {
        const colors = [
            '#2196F3', '#4CAF50', '#FFC107', '#E91E63', '#9C27B0',
            '#00BCD4', '#FF5722', '#795548', '#607D8B', '#3F51B5'
        ];
        
        if (count <= colors.length) {
            return colors.slice(0, count);
        }
        
        const result = [...colors];
        for (let i = colors.length; i < count; i++) {
            const hue = (i * 137.508) % 360;
            result.push(`hsl(${hue}, 70%, 50%)`);
        }
        
        return result;
    }
    
    initBulkActions() {
        const bulkActionSelect = document.getElementById('bulk-action-selector-top');
        const bulkActionButton = document.getElementById('doaction');
        
        if (bulkActionSelect && bulkActionButton) {
            bulkActionButton.addEventListener('click', (e) => {
                if (bulkActionSelect.value === 'delete_registration_source') {
                    if (!confirm(registrationSourceAdmin.strings.confirmBulkDelete)) {
                        e.preventDefault();
                    }
                }
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new RegistrationSourceAdmin();
}); 