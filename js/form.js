const allowedTypes = ["application/pdf", "image/jpeg", "image/png", "image/jpg"];
const maxSize = 10 * 1024 * 1024;

function getError(key) {
  return translations[currentLanguage]?.form?.errors?.[key] || "";
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("apply-form");
  if (!form) return;
  const successBanner = document.getElementById("success-banner");
  const modal = document.getElementById("success-modal");
  const closeModal = document.getElementById("close-modal");

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    clearErrors(form);
    const isValid = validateForm(form);
    if (!isValid) return;

    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => {
      if (value instanceof File) {
        payload[key] = value.name;
      } else {
        payload[key] = value;
      }
    });
    console.log("Submitted data", payload);

    successBanner.textContent = translations[currentLanguage].form.success;
    successBanner.style.display = "block";
    modal.querySelector("h3").textContent = translations[currentLanguage].form.modalTitle;
    modal.querySelector("p").textContent = translations[currentLanguage].form.modalBody;
    modal.classList.add("show");
    form.reset();
  });

  closeModal?.addEventListener("click", () => modal.classList.remove("show"));
});

function clearErrors(form) {
  form.querySelectorAll(".error").forEach((el) => (el.textContent = ""));
}

function validateForm(form) {
  let valid = true;
  const requiredFields = [
    "firstName",
    "lastName",
    "email",
    "phone",
    "street",
    "city",
    "state",
    "zip",
    "year",
    "make",
    "model",
    "vin",
    "license",
    "insurance",
    "vehicleDoc",
  ];

  requiredFields.forEach((name) => {
    const field = form.elements[name];
    if (!field) return;
    if ((field.type === "file" && field.files.length === 0) || !field.value.trim()) {
      showError(field, getError("required"));
      valid = false;
    }
  });

  const email = form.elements["email"]?.value.trim();
  if (email && !/^\S+@\S+\.\S+$/.test(email)) {
    showError(form.elements["email"], getError("email"));
    valid = false;
  }

  const phone = form.elements["phone"]?.value.trim();
  if (phone && !/[0-9\-\s()]{7,}/.test(phone)) {
    showError(form.elements["phone"], getError("phone"));
    valid = false;
  }

  ["license", "insurance", "vehicleDoc"].forEach((name) => {
    const input = form.elements[name];
    const file = input?.files[0];
    if (file) {
      if (!allowedTypes.includes(file.type)) {
        showError(input, getError("fileType"));
        valid = false;
      }
      if (file.size > maxSize) {
        showError(input, getError("fileSize"));
        valid = false;
      }
    }
  });

  return valid;
}

function showError(field, message) {
  const errorEl = field.closest(".field")?.querySelector(".error");
  if (errorEl) errorEl.textContent = message;
}
