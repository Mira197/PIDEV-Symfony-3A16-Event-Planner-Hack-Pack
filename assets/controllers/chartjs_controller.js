import { Controller } from '@hotwired/stimulus';
import { Chart } from 'chart.js/auto';

export default class extends Controller {
    connect() {
        console.log('Chart.js controller connected!', this.element);

        const ctx = this.element.getContext('2d');
        new Chart(ctx, {
            type: 'bar', // ou 'line', 'pie' etc.
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                datasets: [{
                    label: 'Sales',
                    data: [65, 59, 80, 81, 56, 55],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                        'rgba(255, 159, 64, 0.5)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }
}
