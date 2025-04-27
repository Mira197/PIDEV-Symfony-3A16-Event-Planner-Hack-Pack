document.addEventListener("DOMContentLoaded", function () {
    const addAdminBtn = document.getElementById("openAddAdminModal");

    if (addAdminBtn) {
        addAdminBtn.addEventListener("click", function (e) {
            e.preventDefault();

            fetch("/admin/register", {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById("adminModalContainer").innerHTML = html;
                const modalEl = document.getElementById('adminRegisterModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                const form = document.getElementById('admin-register-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const formData = new FormData(form);

                        fetch("/admin/register", {
                            method: "POST",
                            body: formData,
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        })
                        .then(async res => {
                            const contentType = res.headers.get("content-type");

                            if (contentType && contentType.includes("application/json")) {
                                const data = await res.json();
                                if (data.success) {
                                    modal.hide();

                                    // ✅ ➜ actualise la page actuelle sans rediriger
                                    window.location.reload();
                                }
                            } else {
                                const html = await res.text();
                                document.getElementById("adminModalContainer").innerHTML = html;
                                const newModal = new bootstrap.Modal(document.getElementById('adminRegisterModal'));
                                newModal.show();
                            }
                        });
                    });
                }
            });
        });
    }
});
