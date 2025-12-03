
// Smooth Scroll for navigation links
document.querySelectorAll('nav a[href^="#"]').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
            target.scrollIntoView({ behavior: "smooth" });
        }
    });
});

// Smooth scroll for "Shop Now" button
const ctaButton = document.querySelector(".cta-btn");
if (ctaButton) {
    ctaButton.addEventListener("click", (e) => {
        const productsSection = document.getElementById("products");
        if (productsSection) {
            productsSection.scrollIntoView({ behavior: "smooth" });
        }
    });
}

// Highlight nav link when scrolling
window.addEventListener("scroll", () => {
    let sections = document.querySelectorAll("section");
    let scrollPos = document.documentElement.scrollTop || document.body.scrollTop;

    sections.forEach(section => {
        if (scrollPos >= section.offsetTop - 200 &&
            scrollPos < section.offsetTop + section.offsetHeight) {

            document.querySelectorAll("nav a").forEach(a => a.classList.remove("active"));
            let activeLink = document.querySelector(`nav a[href="#${section.id}"]`);
            if (activeLink) activeLink.classList.add("active");
        }
    });
});

//  login validation
const loginForm = document.querySelector(".login-container form");
if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
        const email = document.getElementById("email");
        const password = document.getElementById("password");

        if (!email.value || !password.value) {
            e.preventDefault();
            alert("Please fill in both fields.");
        }
    });
}

// Add-to-Cart confirmation popup
document.querySelectorAll('form button[name="add_to_cart"]').forEach(btn => {
    btn.addEventListener("click", function () {
        alert("Item added to cart!");
    });
});

// Prevent double submissions 
document.querySelectorAll("form").forEach(form => {
    form.addEventListener("submit", () => {
        const btn = form.querySelector("button[type='submit']");
        if (btn) {
            btn.disabled = true;
            btn.innerText = "Processing...";
        }
    });
});