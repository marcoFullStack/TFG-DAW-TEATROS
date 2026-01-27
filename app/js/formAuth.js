// js/formAuth.js
(function () {
  function setFieldError(input, msg) {
    const row = input.closest(".form-row") || input.parentElement;
    input.classList.add("is-invalid");

    let err = row.querySelector(".field-error");
    if (!err) {
      err = document.createElement("div");
      err.className = "field-error";
      row.appendChild(err);
    }
    err.textContent = msg;
  }

  function clearFieldError(input) {
    const row = input.closest(".form-row") || input.parentElement;
    input.classList.remove("is-invalid");
    const err = row.querySelector(".field-error");
    if (err) err.remove();
  }

  function isEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  function enhanceForm(form, rules) {
    if (!form) return;

    // Si el servidor devolvió .notice (errores PHP), le damos “mejor UX”
    const notice = document.querySelector(".notice");
    if (notice) {
      notice.scrollIntoView({ behavior: "smooth", block: "center" });
      notice.classList.add("notice-shake");
      setTimeout(() => notice.classList.remove("notice-shake"), 500);
    }

    // Limpia error al escribir
    form.querySelectorAll("input,select,textarea").forEach((el) => {
      el.addEventListener("input", () => clearFieldError(el));
      el.addEventListener("change", () => clearFieldError(el));
    });

    form.addEventListener("submit", (e) => {
      let ok = true;

      for (const r of rules) {
        const el = form.querySelector(r.selector);
        if (!el) continue;
        clearFieldError(el);

        const value = (el.value || "").trim();

        if (r.required && value === "") {
          setFieldError(el, r.message || "Campo obligatorio");
          ok = false;
          continue;
        }
        if (r.email && value !== "" && !isEmail(value)) {
          setFieldError(el, r.message || "Email no válido");
          ok = false;
          continue;
        }
        if (r.minLength && value.length < r.minLength) {
          setFieldError(el, r.message || `Mínimo ${r.minLength} caracteres`);
          ok = false;
          continue;
        }
      }

      if (!ok) e.preventDefault();
    });
  }

  // Exponemos helper global
  window.AuthForms = { enhanceForm };
})();
