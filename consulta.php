<?php
require_once "config/conexao.php";
require_once "includes/header.php";

$veiculoFiltro = isset($_GET['veiculo_id']) ? (int)$_GET['veiculo_id'] : 0;

$sql = "
    SELECT m.*, v.modelo, v.placa, v.km_atual
    FROM manutencoes m
    INNER JOIN veiculos v ON m.veiculo_id = v.id
";
$params = [];

if ($veiculoFiltro > 0) {
    $sql .= " WHERE m.veiculo_id = ?";
    $params[] = $veiculoFiltro;
}
$sql .= " ORDER BY m.data DESC, m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$manutencoes = $stmt->fetchAll();

$veiculos = $pdo->query("SELECT id, modelo, placa FROM veiculos ORDER BY modelo")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h2 class="fw-bold mb-0">Consulta de Manutenções</h2>
    <div class="bg-white px-3 py-2 rounded shadow-sm">
        <span class="text-muted">Total exibido:</span>
        <strong class="text-success fs-5" id="totalFiltrado">R$ 0,00</strong>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filtrar por Veículo (Banco)</label>
                <form method="GET">
                    <select name="veiculo_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">Todos os veículos</option>
                        <?php foreach ($veiculos as $v): ?>
                            <option value="<?= (int)$v['id'] ?>" <?= $veiculoFiltro === (int)$v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['modelo']) ?> (<?= htmlspecialchars($v['placa']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-md-4">
                <label class="form-label">Categoria (Filtro Instantâneo JS)</label>
                <select id="filtroCategoria" class="form-select">
                    <option value="">Todas as categorias</option>
                    <option value="Revisão">Revisão</option>
                    <option value="Freios">Freios</option>
                    <option value="Suspensão">Suspensão</option>
                    <option value="Motor">Motor</option>
                    <option value="Upgrade">Upgrade</option>
                    <option value="Outros">Outros</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Busca Rápida (Serviço/Placa/Obs)</label>
                <input type="text" id="filtroTexto" class="form-control" placeholder="Digite para filtrar na hora...">
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle" id="tabelaManutencoes">
            <thead class="table-dark">
                <tr>
                    <th>Veículo</th>
                    <th>Serviço</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>KM Serviço</th>
                    <th>Status Revisão</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manutencoes as $m): ?>
                    <?php
                        $prox = (int)$m['proxima_km'];
                        $kmAtual = (int)$m['km_atual'];
                        if ($prox <= 0) {
                            $badge = '<span class="badge bg-secondary">Sem prazo</span>';
                        } elseif ($kmAtual >= $prox) {
                            $badge = '<span class="badge bg-danger">Vencida (' . number_format($prox, 0, ',', '.') . ' km)</span>';
                        } elseif (($prox - $kmAtual) <= 1500) {
                            $badge = '<span class="badge bg-warning text-dark">Vencendo (' . number_format($prox, 0, ',', '.') . ' km)</span>';
                        } else {
                            $badge = '<span class="badge bg-success">Em dia (até ' . number_format($prox, 0, ',', '.') . ' km)</span>';
                        }
                    ?>
                    <tr data-valor="<?= (float)$m['valor'] ?>" data-categoria="<?= htmlspecialchars($m['categoria']) ?>">
                        <td>
                            <strong><?= htmlspecialchars($m['modelo']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($m['placa']) ?> • Atual: <?= number_format($kmAtual, 0, ',', '.') ?> km</small>
                        </td>
                        <td>
                            <?= htmlspecialchars($m['servico']) ?>
                            <?php if (!empty($m['observacao'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($m['observacao']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($m['categoria']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
                        <td><?= number_format((int)$m['km_servico'], 0, ',', '.') ?> km</td>
                        <td><?= $badge ?></td>
                        <td class="fw-bold">R$ <?= number_format((float)$m['valor'], 2, ',', '.') ?></td>
                        <td>
                            <a href="atualizar.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="excluir.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-danger">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($manutencoes)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma manutenção encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>