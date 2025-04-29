document.addEventListener('DOMContentLoaded', () => {
  // 🔁 Status change
  document.querySelectorAll('.status-select').forEach(select => {
      select.addEventListener('change', function () {
          const orderId = this.dataset.id;
          const newStatus = this.value;
          fetch(`/admin/orders/update-status/${orderId}`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: `status=${encodeURIComponent(newStatus)}`
          })
              .then(res => res.json())
              .then(data => {
                  if (!data.success) alert("❌ Failed to update status");
              });
      });
  });

  // 🗑️ Supprimer une seule commande sans recharger la page
  document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function () {
          const id = this.dataset.id;
          const row = this.closest('tr');
          const status = row.querySelector('.status-select').value;

          if (status === 'DELIVERED') {
              alert('You cannot delete an order that has already been delivered.');
              return;
          }

          // Effectuer la suppression sans fenêtre de confirmation
          fetch(`/admin/orders/delete/${id}`, { method: 'DELETE' })
              .then(res => res.json())
              .then(data => {
                  if (data.success) {
                      // 💨 Supprimer avec effet fluide
                      row.style.transition = 'opacity 0.4s ease';
                      row.style.opacity = 0;
                      setTimeout(() => row.remove(), 400);

                      // ✅ Notification après suppression
                      alert('Order deleted!');
                  } else {
                      alert('Cannot delete: ' + (data.message || "Something went wrong"));
                  }
              });
      });
  });

  // 🧹 Delete all
  document.getElementById("deleteAllOrdersBtn").addEventListener("click", () => {
      const confirmDelete = confirm("This will remove all orders permanently. Are you sure?");
      if (confirmDelete) {
          fetch('/admin/orders/delete-all', { method: 'POST' })
              .then(res => res.json())
              .then(data => {
                  if (data.success) {
                      // Supprime toutes les lignes du tableau avec effet
                      document.querySelectorAll('#adminOrdersTable tr').forEach(row => {
                          row.style.transition = 'opacity 0.4s ease';
                          row.style.opacity = 0;
                          setTimeout(() => row.remove(), 400);
                      });
                      alert('All orders deleted!');
                  } else {
                      alert('Deletion failed: Unable to delete all orders.');
                  }
              });
      }
  });

  // 🔍 Search
  document.getElementById("adminOrderSearch").addEventListener("input", function () {
      const query = this.value.trim();

      fetch(`/admin/orders/search?q=${encodeURIComponent(query)}`)
          .then(response => response.json())
          .then(data => {
              const tbody = document.getElementById("adminOrdersTable");
              tbody.innerHTML = '';

              if (data.length === 0) {
                  tbody.innerHTML = `<tr><td colspan="6" class="empty-row">No orders found.</td></tr>`;
                  return;
              }

              data.forEach(order => {
                  const row = `
                  <tr data-status="${order.status}">
                      <td class="hidden-column">${order.id}</td>
                      <td><strong>${order.username}</strong></td>
                      <td>
                          <select class="status-select" data-id="${order.id}">
                              ${['PENDING', 'CONFIRMED', 'DELIVERED', 'CANCELLED'].map(status => `
                                  <option value="${status}" ${status === order.status ? 'selected' : ''}>${status}</option>
                              `).join('')}
                          </select>
                      </td>
                      <td>${order.totalPrice} DT</td>
                      <td class="editable" data-id="${order.id}" data-field="orderedAt">${order.orderedAt}</td>
                      <td>
                          <button type="button" class="btn-delete delete-btn" data-id="${order.id}">🗑️</button>
                      </td>
                  </tr>
                  `;
                  tbody.insertAdjacentHTML('beforeend', row);
              });

              // Réinitialise les actions JS après mise à jour du DOM
              attachDeleteHandlers();
              attachStatusUpdateHandlers();
          })
          .catch(error => {
              console.error("Erreur AJAX recherche admin :", error);
          });
  });

  // 🟣 Filter by status
  document.querySelectorAll('.status-filter').forEach(btn => {
      btn.addEventListener('click', function () {
          document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          const filter = this.dataset.filter;
          document.querySelectorAll("#adminOrdersTable tr").forEach(row => {
              row.style.display = !filter || row.dataset.status === filter ? "" : "none";
          });
      });
  });

  // 📥 Export CSV
  document.getElementById("exportAdminCsvBtn").addEventListener("click", () => {
      const rows = document.querySelectorAll(".admin-mui-table tr");
      const csv = Array.from(rows).map(row =>
          Array.from(row.querySelectorAll("th, td"))
              .map(col => `"${col.innerText}"`).join(",")
      ).join("\n");

      const blob = new Blob([csv], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "admin_orders.csv";
      a.click();
      URL.revokeObjectURL(url);
  });

  // ✏️ Inline edit for date
  document.querySelectorAll('.editable').forEach(cell => {
    cell.addEventListener('dblclick', function() {
        const original = this.textContent.trim();
        const id = this.dataset.id;
        const field = this.dataset.field;

        const input = document.createElement('input');
        input.type = 'date';
        input.value = original;
        input.className = 'form-control form-control-sm';

        this.innerHTML = '';
        this.appendChild(input);
        input.focus();

        const handleBlur = function() {
            const value = input.value;
            if (!value) {
                this.innerHTML = original;
                return;
            }

            fetch(`/admin/orders/update-field/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    field: field,
                    value: value
                })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    cell.innerHTML = value;
                } else {
                    // Si des erreurs sont renvoyées, afficher les messages de validation dans la colonne
                    const errorMessages = data.messages || ['Unknown error'];
                    const errorMessage = errorMessages.join(', '); // Joindre tous les messages
                    const errorElement = document.createElement('span');
                    errorElement.style.color = 'red';
                    errorElement.textContent = errorMessage;
                    cell.innerHTML = original; // Restauration de la valeur originale
                    cell.appendChild(errorElement); // Afficher le message d'erreur sous la cellule
                }
            })
            .catch(err => {
                cell.innerHTML = original;
                console.error('Error:', err);
                alert('Failed to update: ' + err.message);
            });

            input.removeEventListener('blur', handleBlur);
        };

        input.addEventListener('blur', handleBlur);
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleBlur();
            }
        });
    });
});

});