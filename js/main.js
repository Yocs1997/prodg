document.addEventListener("DOMContentLoaded", () => {
  const mobileToggle = document.getElementById("hamburger");
  const mobileMenu = document.getElementById("mobile-menu");
  const langButtons = document.querySelectorAll(".lang-toggle button");

  const saved = localStorage.getItem("tagExpressLang");
  if (saved && saved !== currentLanguage) {
    setLanguage(saved);
  } else {
    applyTranslations();
    updateMeta();
    updateLanguageButtons();
  }

  langButtons.forEach((btn) => {
    btn.addEventListener("click", () => setLanguage(btn.dataset.lang));
  });

  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener("click", () => {
      const expanded = mobileMenu.getAttribute("data-open") === "true";
      mobileMenu.setAttribute("data-open", !expanded);
      mobileMenu.style.display = expanded ? "none" : "flex";
    });
  }

  highlightActiveNav();
});

function highlightActiveNav() {
  const page = document.body.dataset.page;
  document.querySelectorAll(".nav-links a, .mobile-menu a").forEach((link) => {
    if (link.dataset.page === page) {
      link.classList.add("active");
    }
  });
}
