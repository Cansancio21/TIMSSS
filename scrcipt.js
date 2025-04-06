document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".container");
    const registerBtn = document.querySelector(".register-btn");
    const loginBtn = document.querySelector(".login-btn");

    registerBtn.addEventListener("click", () => {
        container.classList.add("active");
    });

    loginBtn.addEventListener("click", () => {
        container.classList.remove("active");
    });

    // Assuming you have a submit button in your form
    document.querySelector(".btn").addEventListener("click", function (event) {
        event.preventDefault(); // Prevents default form submission
        window.location.href = "table.php"; // Redirects to table.php
    });
});


function updateSelectColor(select) {
    if (select.value !== "") {
        select.classList.add("filled"); // Add color when selected
    } else {
        select.classList.remove("filled"); // Remove color when unselected
    }
}

// Add event listeners to select fields
document.querySelectorAll(".input-box select").forEach(select => {
    select.addEventListener("change", function() {
        updateSelectColor(this);
    });
});

// Check pre-selected values (in case of form reload)
document.querySelectorAll(".input-box select").forEach(select => {
    updateSelectColor(select);
});


