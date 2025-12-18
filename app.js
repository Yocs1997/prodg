const form = document.getElementById("redirect-form");
const input = document.getElementById("link-input");
const button = document.getElementById("confirm-btn");
const errorText = document.getElementById("link-error");

const STORAGE_KEY = "redirect_link";
const LOADING_DELAY_MS = 800;

function parseHttpUrl(rawValue) {
  const trimmed = rawValue.trim();
  if (!trimmed) return null;

  try {
    const candidate = new URL(trimmed);
    const isHttp = candidate.protocol === "http:" || candidate.protocol === "https:";
    return isHttp ? candidate.toString() : null;
  } catch (error) {
    return null;
  }
}

function updateStateFromInput() {
  const parsedUrl = parseHttpUrl(input.value);
  const isValid = Boolean(parsedUrl);

  button.disabled = !isValid;
  input.setAttribute("aria-invalid", String(!isValid && input.value.trim() !== ""));

  if (!isValid && input.value.trim() !== "") {
    errorText.textContent = "Introduce una URL válida que empiece con http:// o https://";
  } else {
    errorText.textContent = "";
  }

  return parsedUrl;
}

function setLoadingState(isLoading) {
  if (isLoading) {
    button.classList.add("loading");
    button.disabled = true;
  } else {
    button.classList.remove("loading");
  }
}

input.addEventListener("input", () => {
  const parsedUrl = updateStateFromInput();

  if (parsedUrl) {
    localStorage.setItem(STORAGE_KEY, parsedUrl);
  }
});

form.addEventListener("submit", (event) => {
  event.preventDefault();

  const parsedUrl = updateStateFromInput();

  if (!parsedUrl) {
    return;
  }

  setLoadingState(true);
  errorText.textContent = "";
  localStorage.setItem(STORAGE_KEY, parsedUrl);

  window.setTimeout(() => {
    window.location.assign(parsedUrl);
  }, LOADING_DELAY_MS);
});

(function hydrateFromStorage() {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored) {
    input.value = stored;
    updateStateFromInput();
  }
})();
