/* This code snippet is creating an Immediately Invoked Function Expression (IIFE) in JavaScript. The
purpose of this IIFE is to encapsulate the functions defined within it and prevent polluting the
global scope. Here's a breakdown of what the code is doing: */
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

/**
 * The function clearFieldError removes error styling and messages from a form field.
 * @param input - The `input` parameter in the `clearFieldError` function is a reference to the input
 * field element that you want to clear the error for.
 */
  function clearFieldError(input) {
    const row = input.closest(".form-row") || input.parentElement;
    input.classList.remove("is-invalid");
    const err = row.querySelector(".field-error");
    if (err) err.remove();
  }

  function isEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
  }

  /**
   * The `enhanceForm` function enhances a form by adding validation rules and error handling for form
   * fields.
   * @param form - The `form` parameter in the `enhanceForm` function is a reference to the HTML form
   * element that you want to enhance with validation rules. This function adds validation logic to the
   * form based on the provided rules.
   * @param rules - The `rules` parameter is an array of objects that define validation rules for form
   * fields. Each object in the array should have the following properties:
   * @returns The `enhanceForm` function is returning `undefined`.
   */
  function enhanceForm(form, rules) {
    if (!form) return;

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
