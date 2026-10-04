

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(inputEl, message) {
    clearFieldError(inputEl);
    inputEl.style.borderColor = '#ff3b30';
    const errorMsg = document.createElement('div');
    errorMsg.className = 'field-error-msg';
    errorMsg.style.color = '#ff3b30';
    errorMsg.style.fontSize = '0.85rem';
    errorMsg.style.marginTop = '6px';
    errorMsg.innerText = message;
    inputEl.insertAdjacentElement('afterend', errorMsg);
}

function clearFieldError(inputEl) {
    inputEl.style.borderColor = '';
    const existing = inputEl.parentNode.querySelector('.field-error-msg');
    if (existing) existing.remove();
}

function attachEmailValidation(formSelector, emailInputId) {
    const form = document.querySelector(formSelector);
    const emailInput = document.getElementById(emailInputId);
    if (!form || !emailInput) return;

    form.addEventListener('submit', function(e) {
        if (!isValidEmail(emailInput.value.trim())) {
            e.preventDefault();
            showFieldError(emailInput, 'Please enter a valid email address (e.g. name@example.com).');
            emailInput.focus();
        }
    });

    emailInput.addEventListener('blur', function() {
        clearFieldError(emailInput);
        if (emailInput.value.trim() !== '' && !isValidEmail(emailInput.value.trim())) {
            showFieldError(emailInput, 'Please enter a valid email address (e.g. name@example.com).');
        }
    });
}


function isValidPhone(phone) {
    const cleaned = phone.replace(/[\s\-()]/g, '');
    const phoneRegex = /^\+?\d{7,15}$/;
    return phoneRegex.test(cleaned);
}

function attachPhoneValidation(formSelector, phoneInputId) {
    const form = document.querySelector(formSelector);
    const phoneInput = document.getElementById(phoneInputId);
    if (!form || !phoneInput) return;

    form.addEventListener('submit', function(e) {
        if (!isValidPhone(phoneInput.value.trim())) {
            e.preventDefault();
            showFieldError(phoneInput, 'Please enter a valid phone number (7-15 digits, e.g. 012-3456789).');
            phoneInput.focus();
        }
    });

    phoneInput.addEventListener('blur', function() {
        clearFieldError(phoneInput);
        if (phoneInput.value.trim() !== '' && !isValidPhone(phoneInput.value.trim())) {
            showFieldError(phoneInput, 'Please enter a valid phone number (7-15 digits, e.g. 012-3456789).');
        }
    });
}


function isValidAddress(address) {
    const trimmed = address.trim();
    return trimmed.length >= 10;
}

function attachAddressValidation(formSelector, addressInputId) {
    const form = document.querySelector(formSelector);
    const addressInput = document.getElementById(addressInputId);
    if (!form || !addressInput) return;

    form.addEventListener('submit', function(e) {
        if (!isValidAddress(addressInput.value)) {
            e.preventDefault();
            showFieldError(addressInput, 'Please enter a complete address (at least 10 characters).');
            addressInput.focus();
        }
    });

    addressInput.addEventListener('blur', function() {
        clearFieldError(addressInput);
        if (addressInput.value.trim() !== '' && !isValidAddress(addressInput.value)) {
            showFieldError(addressInput, 'Please enter a complete address (at least 10 characters).');
        }
    });
}
