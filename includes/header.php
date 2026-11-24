<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garagem Digital - Diário de Bordo Automotivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
        .card { border: none; border-radius: 12px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Garagem Digital</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'quem_somos.php' ? 'active' : '' ?>" href="quem_somos.php">Quem Somos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'cadastro.php' ? 'active' : '' ?>" href="cadastro.php">Cadastro</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'consulta.php' ? 'active' : '' ?>" href="consulta.php">Consulta</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'atualizar.php' ? 'active' : '' ?>" href="atualizar.php">Atualização</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'excluir.php' ? 'active' : '' ?>" href="excluir.php">Exclusão</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">