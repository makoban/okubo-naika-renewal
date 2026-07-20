document.querySelectorAll("[data-admin-demo]").forEach((form) => {
  const message = form.querySelector(".admin-demo-message");

  form.querySelectorAll("[data-demo-action]").forEach((button) => {
    button.addEventListener("click", () => {
      message.textContent = "構成確認版のため、現在は保存・公開されません。";
    });
  });
});
