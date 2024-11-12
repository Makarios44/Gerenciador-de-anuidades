document.addEventListener("DOMContentLoaded", function() {
    // Função para exibir a aba selecionada e ocultar as outras
    function showTab(event, tabId) {
        // Oculta todos os conteúdos das abas
        const tabContents = document.querySelectorAll(".tab-content");
        tabContents.forEach(function(content) {
            content.style.display = "none";
        });

        // Remove a classe 'active' de todos os botões de abas
        const tabButtons = document.querySelectorAll(".tab-button");
        tabButtons.forEach(function(button) {
            button.classList.remove("active");
        });

        // Exibe o conteúdo da aba selecionada e adiciona a classe 'active' ao botão correspondente
        document.getElementById(tabId).style.display = "block";
        event.currentTarget.classList.add("active");
    }

    // Exibe a primeira aba por padrão ao carregar a página
    const defaultTabButton = document.querySelector(".tab-button");
    if (defaultTabButton) {
        defaultTabButton.click();
    }

    // Adiciona o evento de clique aos botões das abas
    const tabButtons = document.querySelectorAll(".tab-button");
    tabButtons.forEach(function(button) {
        button.addEventListener("click", function(event) {
            showTab(event, button.getAttribute("onclick").split("'")[1]);
        });
    });
});
