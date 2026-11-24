<?php

require_once __DIR__ . "/funcoes.php";

$paginaAtual = basename($_SERVER["SCRIPT_NAME"] ?? "");

$menu = [
    "index.php" => "Home",
    "quem_somos.php" => "Quem Somos",
    "cadastro.php" => "Cadastro",
    "consulta.php" => "Consulta",
    "atualizar.php" => "Atualização",
    "excluir.php" => "Exclusão"
];

$tituloPagina = $menu[$paginaAtual] ?? "Garagem Digital";

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Garagem Digital - Sistema de gerenciamento de manutenções automotivas.">

    <title><?= h($tituloPagina) ?> | Garagem Digital</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    <style>

        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .card {
            border: none;
            border-radius: 12px;
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 12px 10px;
        }

        .table td {
            vertical-align: middle;
            padding: 14px 10px;
            font-size: 0.875rem;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .veiculo-coluna {
            min-width: 155px;
        }

        .veiculo-titulo {
            font-weight: 600;
            line-height: 1.4;
            color: #212529;
        }

        .veiculo-placa {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 3px;
        }

        .servico-coluna {
            min-width: 230px;
            max-width: 320px;
            text-align: left;
        }

        .servico-titulo {
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.4;
            color: #212529;
            overflow-wrap: anywhere;
        }

        .observacao {
            font-size: 0.78rem;
            color: #6c757d;
            line-height: 1.5;
            margin-top: 5px;
            white-space: normal;
            overflow-wrap: anywhere;
            text-align: left;
        }

        .valor-coluna {
            white-space: nowrap;
            font-weight: 500;
        }

        .data-coluna,
        .km-coluna,
        .status-coluna {
            white-space: nowrap;
        }

        .acoes {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        #tabelaManutencoes .acoes {
            flex-wrap: nowrap;
        }

        .acoes .btn {
            white-space: nowrap;
        }

        .form-label {
            font-weight: 500;
        }

        footer {
            margin-top: auto;
        }

        @media (max-width: 768px) {

            .servico-coluna {
                min-width: 200px;
            }

            .table td,
            .table th {
                padding: 10px 8px;
            }

        }

    </style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            Garagem Digital
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menuPrincipal"
            aria-controls="menuPrincipal"
            aria-expanded="false"
            aria-label="Abrir menu">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div
            class="collapse navbar-collapse"
            id="menuPrincipal">

            <ul class="navbar-nav ms-auto">

                <?php foreach ($menu as $arquivo => $nome): ?>

                    <li class="nav-item">

                        <a
                            class="nav-link <?= $paginaAtual === $arquivo ? "active" : "" ?>"
                            href="<?= h($arquivo) ?>"
                            <?= $paginaAtual === $arquivo
                                ? 'aria-current="page"'
                                : "" ?>>

                            <?= h($nome) ?>

                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    </div>

</nav>

<main class="container pb-5">