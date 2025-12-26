// Handles live search functionality with filters, spinner, and keyboard navigation
class LiveSearch {
    constructor(inputId, resultsContainerId, endpoint) {
        this.input = document.getElementById(inputId); // search input
        this.resultsContainer = document.getElementById(resultsContainerId); // dropdown container
        this.endpoint = endpoint; // PHP search handler URL
        this.debounceTimer = null; // delay timer for typing
        this.currentIndex = -1; // currently highlighted result
        this.results = []; // stores fetched results
    }

    init() {
        // Trigger search on typing
        this.input.addEventListener('input', () => {
            const query = this.input.value.trim();
            clearTimeout(this.debounceTimer);

            if (query.length >= 1) {
                document.getElementById('live-search-spinner').style.display = 'inline-block';
                this.debounceTimer = setTimeout(() => {
                    this.fetchResults(query);
                }, 300);
            } else {
                this.resultsContainer.innerHTML = '';
                document.getElementById('live-search-spinner').style.display = 'none';
            }
        });

        // Support arrow key navigation + enter to select
        this.input.addEventListener('keydown', (e) => {
            const items = this.resultsContainer.querySelectorAll('.list-group-item-action');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.currentIndex = (this.currentIndex + 1) % items.length;
                this.updateHighlight(items);
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.currentIndex = (this.currentIndex - 1 + items.length) % items.length;
                this.updateHighlight(items);
            }

            if (e.key === 'Enter' && this.currentIndex >= 0) {
                e.preventDefault();
                items[this.currentIndex].click();
            }
        });

        // Hide dropdown if user clicks outside
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.resultsContainer.innerHTML = '';
                document.getElementById('live-search-spinner').style.display = 'none';
            }
        });
    }

    // Fetch results from PHP endpoint using filters
    async fetchResults(query) {
        try {
            const category = document.getElementById('filter-category').value;
            const town = document.getElementById('filter-town').value;

            const url = `${this.endpoint}?q=${encodeURIComponent(query)}&category=${encodeURIComponent(category)}&town=${encodeURIComponent(town)}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: { 'X-CSRF-Token': csrfToken }
            });

            const data = await response.json();
            this.renderResults(data);
        } catch (error) {
            console.error('Fetch error:', error);
            this.resultsContainer.innerHTML = `<div class="list-group-item text-danger">Error loading results</div>`;
        }
    }

    // Render results into the dropdown
    renderResults(data) {
        this.results = data;
        this.currentIndex = -1;
        this.resultsContainer.innerHTML = '';

        if (data.length > 0) {
            data.forEach((item) => {
                const div = document.createElement('a');
                div.href = `login_dashboard.php?search=${encodeURIComponent(item.title)}`;
                div.className = 'list-group-item list-group-item-action';
                div.innerHTML = `
                <h6 class="mb-1">${item.title}</h6>
                <small class="text-muted d-block">${item.category} - ${item.town}</small>
            `;
                this.resultsContainer.appendChild(div);
            });
        } else {
            const div = document.createElement('div');
            div.className = 'list-group-item text-muted';
            div.textContent = 'No results found';
            this.resultsContainer.appendChild(div);
        }
    }

    // Visually highlight result during keyboard nav
    updateHighlight(items) {
        items.forEach(item => item.classList.remove('active'));
        if (this.currentIndex >= 0) {
            items[this.currentIndex].classList.add('active');

            // Scroll the item into view with proper positioning
            items[this.currentIndex].scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'start'
            });
        }
    }
}

// Initialize everything when page is ready
document.addEventListener('DOMContentLoaded', () => {
    const liveSearch = new LiveSearch('live-search', 'search-results', 'live_search_handler.php');
    liveSearch.init();

    // Clear everything when "Clear" is clicked
    document.getElementById('clear-search').addEventListener('click', () => {
        document.getElementById('live-search').value = '';
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-town').value = '';
        document.getElementById('search-results').innerHTML = '';
        document.getElementById('live-search-spinner').style.display = 'none';

        window.location.href = 'login_dashboard.php';
    });
});
