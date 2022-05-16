// privacyIDEA - allow WebAuthn and OTP on one page
document.addEventListener("DOMContentLoaded", () => {
  ["otp", "submitButton"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.classList.remove("hidden");
    }
  });

  const piLoginForm = document.getElementById("piLoginForm");
  if (piLoginForm) {
    piLoginForm.addEventListener("submit", () => {
      document.getElementById("mode").value = "otp";
    });
  }
});
