import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const chart = new Chart(this.element, JSON.parse(this.element.dataset.chart));
    }
}
