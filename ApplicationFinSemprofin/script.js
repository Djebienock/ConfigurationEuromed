document.addEventListener("DOMContentLoaded", () => {
    const email = localStorage.getItem("rememberedEmail");
    if (email) {
        document.getElementById("email").value = email;
        document.getElementById("rememberMe").checked = true;
    }
});

document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;
    const remember = document.getElementById("rememberMe").checked;
    const message = document.getElementById("message");

    if (email === "admin@univ.fr" && password === "admin123") {

        if (remember) {
            localStorage.setItem("rememberedEmail", email);
        } else {
            localStorage.removeItem("rememberedEmail");
        }

        message.className = "alert";
        message.style.display = "block";
        message.style.background = "#e0ffe8";
        message.style.color = "#2e7d32";
        message.textContent = "Connexion réussie ✅";

    } else {
        message.className = "alert alert-error";
        message.style.display = "block";
        message.textContent = "Email ou mot de passe incorrect ❌";
    }
});
