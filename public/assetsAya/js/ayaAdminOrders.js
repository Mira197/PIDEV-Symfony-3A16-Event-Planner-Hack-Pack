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
  
    // 🗑️ Delete one
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        Swal.fire({
          title: 'Delete order?',
          text: "This action is irreversible!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then(result => {
          if (result.isConfirmed) {
            fetch(`/admin/orders/delete/${id}`, { method: 'DELETE' })
              .then(res => res.json())
              .then(data => {
                if (data.success) location.reload();
                else alert("❌ Failed to delete");
              });
          }
        });
      });
    });
  
    // 🧹 Delete all
    document.getElementById("deleteAllOrdersBtn").addEventListener("click", () => {
      Swal.fire({
        title: 'Delete ALL orders?',
        text: "This will remove all orders permanently.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete All',
        confirmButtonColor: '#d33'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('/admin/orders/delete-all', { method: 'POST' })
            .then(res => res.json())
            .then(data => data.success ? location.reload() : alert("Failed to delete all"));
        }
      });
    });
  
    // 🔍 Search
    document.getElementById("adminOrderSearch").addEventListener("keyup", function () {
      const value = this.value.toLowerCase();
      document.querySelectorAll("#adminOrdersTable tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
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
      cell.addEventListener('dblclick', function () {
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
        input.addEventListener('blur', function () {
          const value = input.value;
          fetch(`/admin/orders/update-field/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `field=${field}&value=${value}`
          }).then(res => res.json()).then(data => {
            cell.innerHTML = data.success ? value : original;
          });
        });
      });
    });
  });
  