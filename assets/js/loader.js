window.addEventListener("load", () => {
  const loader = document.querySelector(".loader");

  if (loader) { // Verifica se o elemento existe
    loader.classList.add("loader--hidden");

    loader.addEventListener("transitionend", () => {
      loader.remove(); // Utiliza remove() para remover o elemento
    });
  }
});