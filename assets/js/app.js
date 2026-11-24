document.addEventListener("DOMContentLoaded", function () {
    const filtroTexto = document.getElementById("filtroTexto");
    const filtroCategoria = document.getElementById("filtroCategoria");
    const tabela = document.getElementById("tabelaManutencoes");
    const totalEl = document.getElementById("totalFiltrado");

    function atualizarTabelaETotal() {
        if (!tabela) return;

        const termo = filtroTexto ? filtroTexto.value.toLowerCase().trim() : "";
        const categoria = filtroCategoria ? filtroCategoria.value.toLowerCase() : "";
        const linhas = tabela.querySelectorAll("tbody tr[data-valor]");
        let soma = 0;

        linhas.forEach((linha) => {
            const textoLinha = linha.textContent.toLowerCase();
            const catLinha = (linha.getAttribute("data-categoria") || "").toLowerCase();
            const valor = parseFloat(linha.getAttribute("data-valor")) || 0;

            const bateTexto = textoLinha.includes(termo);
            const bateCategoria = categoria === "" || catLinha === categoria;

            if (bateTexto && bateCategoria) {
                linha.style.display = "";
                soma += valor;
            } else {
                linha.style.display = "none";
            }
        });

        if (totalEl) {
            totalEl.textContent = soma.toLocaleString("pt-BR", {
                style: "currency",
                currency: "BRL"
            });
        }
    }

    if (filtroTexto) filtroTexto.addEventListener("input", atualizarTabelaETotal);
    if (filtroCategoria) filtroCategoria.addEventListener("change", atualizarTabelaETotal);
    atualizarTabelaETotal();
});