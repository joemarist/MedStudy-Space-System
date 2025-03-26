document.addEventListener("DOMContentLoaded", function () {
    const navLinks = document.querySelectorAll(".link");

    navLinks.forEach(link => {
        link.addEventListener("click", function () {
            navLinks.forEach(l => l.classList.remove("active"));
            
            this.classList.add("active");
        });
    });
});