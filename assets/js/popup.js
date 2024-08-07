let popup = document.getElementById("popup");

function openPopup() {
    popup.classList.add("open-popup");
}

function closePopup() {
    popup.classList.remove("open-popup");
    window.location.href = "../public/home.php";
}

document.getElementById("dishForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let formData = new FormData(this);

    fetch("registration.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            openPopup();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Erro:', error));
});

document.querySelector('.btn-close-popup').addEventListener("click", closePopup);