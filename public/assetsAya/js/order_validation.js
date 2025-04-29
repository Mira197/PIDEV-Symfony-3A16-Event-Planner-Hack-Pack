window.addEventListener("load", function () {
    const form = document.querySelector("#order_form");
    const addressInput = form.querySelector("#aya_order_exact_address");
    const dateInput = form.querySelector("#aya_order_event_date");
    const paymentSelect = form.querySelector("#aya_order_payment_method");

    function validateForm() {
        let isValid = true;

        // Adresse
        const addressValue = addressInput.value.trim();
        if (addressValue === "" || addressValue.length < 10) {
            addressInput.classList.add("is-invalid");
            isValid = false;
        } else {
            addressInput.classList.remove("is-invalid");
        }

        // Date
        const selectedDate = new Date(dateInput.value);
        const now = new Date();
        if (!dateInput.value || isNaN(selectedDate.getTime()) || selectedDate <= now) {
            dateInput.classList.add("is-invalid");
            isValid = false;
        } else {
            dateInput.classList.remove("is-invalid");
        }

        // Paiement
        if (!paymentSelect.value) {
            paymentSelect.classList.add("is-invalid");
            isValid = false;
        } else {
            paymentSelect.classList.remove("is-invalid");
        }

        return isValid;
    }

    // Validation au moment du submit (ajoute juste la classe rouge si invalide)
    form.addEventListener("submit", function (event) {
        if (!validateForm()) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    // Validation live (juste ajout/retrait classe rouge)
    addressInput.addEventListener("input", () => {
        addressInput.classList.toggle("is-invalid", addressInput.value.trim().length < 10);
    });

    dateInput.addEventListener("change", () => {
        const selectedDate = new Date(dateInput.value);
        const now = new Date();
        dateInput.classList.toggle("is-invalid", !dateInput.value || isNaN(selectedDate.getTime()) || selectedDate <= now);
    });

    paymentSelect.addEventListener("change", () => {
        paymentSelect.classList.toggle("is-invalid", !paymentSelect.value);
    });
});
