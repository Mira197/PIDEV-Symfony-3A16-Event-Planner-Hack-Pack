document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('promoSearch');
    const exportBtn = document.getElementById('exportPromoCsv');

    function getPromoRows() {
        return document.querySelectorAll('#promoCodeTable tbody tr');
    }

    function updatePromoTable(data) {
        const tbody = document.querySelector('#promoCodeTable tbody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="empty-row">No promo codes found.</td></tr>`;
            return;
        }

        data.forEach((code, index) => {
            const expDate = new Date(code.dateExpiration);
            const now = new Date();
            const isExpired = expDate < now;
            const status = isExpired ? 'expired' : 'active';

            const row = document.createElement('tr');
            row.dataset.status = status;
            row.innerHTML = `
                <td class="hidden-column">${index + 1}</td>
                <td><strong>${code.codePromo}</strong></td>
                <td>${code.pourcentage}</td>
                <td>${code.dateExpiration || '-'}</td>
                <td><span class="badge ${status === 'expired' ? 'bg-danger' : 'bg-success'}">
                    ${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
            `;
            tbody.appendChild(row);
        });
    }

    let searchTimeout;
    searchInput.addEventListener("input", function () {
        const query = this.value.trim();
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const url = query === ''
                ? '{{ path("aya_admin_code_promo_search") }}'
                : `{{ path("aya_admin_code_promo_search") }}?q=${encodeURIComponent(query)}`;

            fetch(url, {
                headers: { 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(updatePromoTable)
                .catch(() => Swal.fire('Search error', 'Unable to fetch promo codes.', 'error'));
        }, 300);
    });

    document.querySelectorAll('.status-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            getPromoRows().forEach(row => {
                row.style.display = (filter === '' || row.dataset.status === filter) ? '' : 'none';
            });
        });
    });

    exportBtn.addEventListener('click', () => {
        let csv = 'Code,Discount,Expiration Date,Status\n';
        getPromoRows().forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                csv += `${cells[1].innerText},${cells[2].innerText},${cells[3].innerText},${cells[4].innerText}\n`;
            }
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'promo_codes.csv';
        a.click();
    });
});

function confirmDelete(id, csrfToken) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/promo-codes/delete/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ _token: csrfToken })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', 'Promo code deleted.', 'success');
                        const row = document.querySelector(`button[onclick*="confirmDelete(${id}"]`).closest('tr');
                        if (row) row.remove();
                    } else {
                        Swal.fire('Error', data.message || 'Delete failed.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Unexpected server error.', 'error'));
        }
    });
}
