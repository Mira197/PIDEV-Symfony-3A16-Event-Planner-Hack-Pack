window.addEventListener("load", function () {
    const form = document.querySelector("#order_form");
    const addressInput = form.querySelector("#aya_order_exact_address");
    const dateInput = form.querySelector("#aya_order_event_date");
    const paymentSelect = form.querySelector("#aya_order_payment_method");
    console.log("💡 JS chargé !");
    console.log("Adresse input :", document.querySelector("#aya_order_exact_address"));
    console.log("Date input :", document.querySelector("#aya_order_event_date"));
    console.log("Méthode de paiement :", document.querySelector("#aya_order_payment_method"));
    console.log("Adresse modifiée :", addressInput.value);
    console.log("Nouvelle date :", dateInput.value);
    console.log("payment :", paymentSelect.value);

    function createOrGetErrorElement(input) {
        let error = input.parentNode.querySelector(".form-error-message");
        if (!error) {
            error = document.createElement("div");
            error.className = "form-error-message text-danger mt-1";
            input.insertAdjacentElement("afterend", error);
        }
        return error;
    }

    function validateForm() {
        let isValid = true;
        
        // Validation de l'adresse
        const addressError = createOrGetErrorElement(addressInput);
        const addressValue = addressInput.value.trim();
        if (addressValue === "") {
            setError(addressInput, addressError, "This field is required.");
            isValid = false;
        } else if (addressValue.length < 10) {
            setError(addressInput, addressError, "This value is too short. It should have 10 characters or more.");
            isValid = false;
        } else {
            clearError(addressInput, addressError);
        }

        // Validation de la date
        const dateError = createOrGetErrorElement(dateInput);
        const selectedDate = new Date(dateInput.value);
        const now = new Date();
        if (!dateInput.value || isNaN(selectedDate.getTime())) {
            setError(dateInput, dateError, "Please select a valid date.");
            isValid = false;
        } else if (selectedDate <= now) {
            setError(dateInput, dateError, "The event date must be in the future.");
            isValid = false;
        } else {
            clearError(dateInput, dateError);
        }

        // Validation du paiement
        const paymentError = createOrGetErrorElement(paymentSelect);
        if (!paymentSelect.value) {
            setError(paymentSelect, paymentError, "Please select a payment method.");
            isValid = false;
        } else {
            clearError(paymentSelect, paymentError);
        }

        return isValid;
    }

    // Écouteurs d'événements pour la validation en temps réel
    if (addressInput) {
        addressInput.addEventListener("input", () => {
            const error = createOrGetErrorElement(addressInput);
            const value = addressInput.value.trim();
            if (value === "") {
                setError(addressInput, error, "This field is required.");
            } else if (value.length < 10) {
                setError(addressInput, error, "This value is too short. It should have 10 characters or more.");
            } else {
                clearError(addressInput, error);
            }
        });
    }

    if (dateInput) {
        dateInput.addEventListener("change", () => {
            const error = createOrGetErrorElement(dateInput);
            const selectedDate = new Date(dateInput.value);
            const now = new Date();
            if (!dateInput.value || isNaN(selectedDate.getTime())) {
                setError(dateInput, error, "Please select a valid date.");
            } else if (selectedDate <= now) {
                setError(dateInput, error, "The event date must be in the future.");
            } else {
                clearError(dateInput, error);
            }
        });
    }

    if (paymentSelect) {
        paymentSelect.addEventListener("change", () => {
            const error = createOrGetErrorElement(paymentSelect);
            if (!paymentSelect.value) {
                setError(paymentSelect, error, "Please select a payment method.");
            } else {
                clearError(paymentSelect, error);
            }
        });
    }

    // Validation avant soumission
    form.addEventListener("submit", function(event) {
        if (!validateForm()) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
});

function setError(input, errorElem, message) {
    input.classList.add("is-invalid");
    errorElem.textContent = message;
    errorElem.style.display = "block";
}

function clearError(input, errorElem) {
    input.classList.remove("is-invalid");
    errorElem.textContent = "";
    errorElem.style.display = "none";
}