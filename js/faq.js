document.addEventListener("DOMContentLoaded", () => {
  const items = document.querySelectorAll(".accordion-item");
  items.forEach((item) => {
    const header = item.querySelector(".accordion-header");
    header.addEventListener("click", () => toggleItem(item));
  });
});

function toggleItem(item) {
  const content = item.querySelector(".accordion-content");
  const isOpen = item.classList.contains("active");
  document.querySelectorAll(".accordion-item").forEach((el) => {
    el.classList.remove("active");
    const c = el.querySelector(".accordion-content");
    c.style.maxHeight = null;
  });

  if (!isOpen) {
    item.classList.add("active");
    content.style.maxHeight = content.scrollHeight + "px";
  }
}
