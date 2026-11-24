<?php
require_once "config/conexao.php";
require_once "includes/header.php";

$totalVeiculos = $pdo->query("SELECT COUNT(*) FROM veiculos")->fetchColumn();
$totalManutencoes = $pdo->query("SELECT COUNT(*) FROM manutencoes")->fetchColumn();
$gastoTotal = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM manutencoes")->fetchColumn();

$veiculos = $pdo->query("SELECT * FROM veiculos ORDER BY id DESC")->fetchAll();
?>

<div class="p-4 p-md-5 mb-4 text-white rounded-3 bg-dark shadow-sm">
    <div class="col-lg-8">
        <h1 class="display-6 fw-bold">Garagem Digital</h1>
        <p class="lead my-3">
            Controle completo de revisões, trocas de peças, upgrades e quilometragem dos seus veículos em um só lugar.
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <a href="cadastro.php" class="btn btn-primary">Novo Cadastro</a>
            <a href="consulta.php" class="btn btn-outline-light">Consultar Histórico</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Veículos Cadastrados</h6>
                <h2 class="fw-bold mb-0"><?= (int)$totalVeiculos ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Serviços Registrados</h6>
                <h2 class="fw-bold mb-0"><?= (int)$totalManutencoes ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Investimento Total</h6>
                <h2 class="fw-bold text-success mb-0">
                    R$ <?= number_format((float)$gastoTotal, 2, ',', '.') ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">Veículos na Garagem</div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Placa</th>
                    <th>KM Atual</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($veiculos as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['modelo']) ?></td>
                        <td><?= (int)$v['ano'] ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($v['placa']) ?></span></td>
                        <td><?= number_format((int)$v['km_atual'], 0, ',', '.') ?> km</td>
                        <td>
                            <a href="atualizar.php?veiculo_id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-warning">Atualizar KM</a>
                            <a href="consulta.php?veiculo_id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-primary">Ver Serviços</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($veiculos)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Nenhum veículo cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>