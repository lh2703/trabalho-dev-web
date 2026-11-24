"use strict";

document.addEventListener("DOMContentLoaded", function () {

    // NORMALIZAÇÃO DE TEXTOS

    function normalizar(texto) {

        return String(texto || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();

    }


    // CONVERSÃO DE VALORES PARA CENTAVOS

    function converterCentavos(valor) {

        const texto = String(valor || "0")
            .replace(",", ".")
            .trim();

        if (!/^\d+(\.\d{1,2})?$/.test(texto)) {
            return 0;
        }

        const partes = texto.split(".");

        const reais = Number(partes[0]);

        const centavos = Number(
            (partes[1] || "").padEnd(2, "0")
        );

        return reais * 100 + centavos;

    }


    // FORMATAÇÃO MONETÁRIA

    function formatarMoeda(centavos) {

        return new Intl.NumberFormat("pt-BR", {
            style: "currency",
            currency: "BRL"
        }).format(centavos / 100);

    }


    // ELEMENTOS DOS FILTROS

    const filtroTexto = document.getElementById("filtroTexto");

    const filtroCategoria = document.getElementById("filtroCategoria");

    const tabela = document.getElementById("tabelaManutencoes");

    const totalEl = document.getElementById("totalFiltrado");

    const quantidadeEl = document.getElementById("quantidadeFiltrada");

    const semResultados = document.getElementById("semResultados");


    // ATUALIZAR FILTROS E TOTAL

    function atualizarTabelaETotal() {

        if (!tabela) {
            return;
        }

        const termo = normalizar(
            filtroTexto ? filtroTexto.value : ""
        );

        const categoria = normalizar(
            filtroCategoria ? filtroCategoria.value : ""
        );

        const linhas = tabela.querySelectorAll(
            "tbody tr[data-valor]"
        );

        let somaCentavos = 0;

        let quantidade = 0;

        linhas.forEach(function (linha) {

            const textoLinha = normalizar(
                linha.textContent
            );

            const categoriaLinha = normalizar(
                linha.dataset.categoria
            );

            const correspondeTexto = textoLinha.includes(termo);

            const correspondeCategoria =
                categoria === "" ||
                categoriaLinha === categoria;

            const corresponde =
                correspondeTexto &&
                correspondeCategoria;

            linha.hidden = !corresponde;

            if (corresponde) {

                somaCentavos += converterCentavos(
                    linha.dataset.valor
                );

                quantidade++;

            }

        });

        if (totalEl) {

            totalEl.textContent = formatarMoeda(
                somaCentavos
            );

        }

        if (quantidadeEl) {

            quantidadeEl.textContent =
                quantidade + " registro(s)";

        }

        if (semResultados) {

            semResultados.hidden = quantidade > 0;

        }

    }


    // EVENTOS DOS FILTROS

    if (filtroTexto) {

        filtroTexto.addEventListener(
            "input",
            atualizarTabelaETotal
        );

    }

    if (filtroCategoria) {

        filtroCategoria.addEventListener(
            "change",
            atualizarTabelaETotal
        );

    }

    atualizarTabelaETotal();


    // TRANSFORMAR PLACA EM MAIÚSCULAS

    document.querySelectorAll(
        'input[name="placa"]'
    ).forEach(function (campo) {

        campo.addEventListener("input", function () {

            campo.value = campo.value.toUpperCase();

        });

    });


    // CONFIRMAR EXCLUSÕES E EVITAR ENVIOS DUPLICADOS

    document.querySelectorAll("form").forEach(function (formulario) {

        const metodo = (
            formulario.getAttribute("method") || "GET"
        ).toUpperCase();

        if (metodo !== "POST") {
            return;
        }

        formulario.addEventListener("submit", function (evento) {

            if (formulario.dataset.enviando === "true") {

                evento.preventDefault();

                return;

            }

            const mensagem = formulario.dataset.confirmar;

            if (mensagem && !window.confirm(mensagem)) {

                evento.preventDefault();

                return;

            }

            formulario.dataset.enviando = "true";

            const botoes = formulario.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            );

            botoes.forEach(function (botao) {

                botao.disabled = true;

            });

        });

    });


    // RESTAURAR BOTÕES EM CASO DE RETORNO DO NAVEGADOR

    window.addEventListener("pageshow", function () {

        document.querySelectorAll(
            'form[data-enviando="true"]'
        ).forEach(function (formulario) {

            delete formulario.dataset.enviando;

            formulario.querySelectorAll(
                'button[type="submit"], input[type="submit"]'
            ).forEach(function (botao) {

                botao.disabled = false;

            });

        });

    });

});