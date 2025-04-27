window.addEventListener("load", function () {
    console.log(document.querySelector("#aya_order_exact_address"));
    const addressInput = document.querySelector("#aya_order_exact_address");
    const dateInput = document.querySelector("#aya_order_exact_address");
    const paymentSelect = document.querySelector("#aya_order_exact_address");

    function createOrGetErrorElement(input) {
        // Supprimer les erreurs de Symfony existantes
        const symfonyError = input.parentNode.querySelector('.invalid-feedback');
        if (symfonyError) {
            symfonyError.remove();
        }
    
        let error = input.parentNode.querySelector(".form-error-message");
        if (!error) {
            error = document.createElement("div");
            error.className = "form-error-message text-danger mt-1";
            input.insertAdjacentElement("afterend", error);
        }
        return error;
    }
    

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

    // 🏷️ Validation live pour exact_address
    if (addressInput) {
        const addressError = createOrGetErrorElement(addressInput);
        addressInput.addEventListener("input", () => {
            console.log("Input triggered:", addressInput.value); // ← ajoute ça
            const value = addressInput.value.trim();
        
            if (value === "") {
                setError(addressInput, addressError, "This field is required.");
            } else if (value.length < 10) {
                setError(addressInput, addressError, "This value is too short. It should have 10 characters or more.");
            } else {
                clearError(addressInput, addressError);
            }
        });
        
    }

    // 📅 Validation live date
    if (dateInput) {
        const dateError = createOrGetErrorElement(dateInput);
        dateInput.addEventListener("change", () => {
            const selectedDate = new Date(dateInput.value);
            const now = new Date();

            if (!selectedDate || isNaN(selectedDate.getTime())) {
                setError(dateInput, dateError, "Please select a valid date.");
            } else if (selectedDate <= now) {
                setError(dateInput, dateError, "The event date must be in the future.");
            } else {
                clearError(dateInput, dateError);
            }
        });
    }

    // 💳 Validation live pour payment_method
    if (paymentSelect) {
        const paymentError = createOrGetErrorElement(paymentSelect);
        paymentSelect.addEventListener("change", () => {
            if (!paymentSelect.value) {
                setError(paymentSelect, paymentError, "Please select a payment method.");
            } else {
                clearError(paymentSelect, paymentError);
            }
        });
    }
});
