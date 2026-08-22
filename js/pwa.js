let installPrompt;
  const installButton = document.querySelector(".install");

  addEventListener("beforeinstallprompt", e => {
    e.preventDefault();
    installPrompt = e;
    installButton.style.display = "block";
  });

  installButton.addEventListener("click", async () => {
    await installPrompt.prompt();
    installPrompt = null;
  });

  addEventListener("appinstalled", () => {
    installPrompt = null;
    installButton.style.display = "none";
  });
