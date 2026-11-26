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

    <link
        href="assets/css/style.css"
        rel="stylesheet">

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