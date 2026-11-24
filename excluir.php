<?php
require_once "config/conexao.php";

$mensagem = "";
$tipoAlerta = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST["tipo"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    try {
        if ($tipo === "manutencao" && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM manutencoes WHERE id = ?");
            $stmt->execute([$id]);
            $mensagem = "Registro de manutenção excluído com sucesso!";
        } elseif ($tipo === "veiculo" && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = ?");
            $stmt->execute([$id]);
            $mensagem = "Veículo e suas manutenções excluídos com sucesso!";
        }
    } catch (PDOException $e) {
        $tipoAlerta = "danger";
        $mensagem = "Erro ao excluir: " . $e->getMessage();
    }
}

$idPreSelecionado = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$manutencoes = $pdo->query("
    SELECT m.id, m.servico, m.data, m.valor, v.modelo, v.placa
    FROM manutencoes m
    INNER JOIN veiculos v ON m.veiculo_id = v.id
    ORDER BY m.data DESC
")->fetchAll();

$veiculos = $pdo->query("SELECT * FROM veiculos ORDER BY modelo")->fetchAll();
require_once "includes/header.php";
?>

<h2 class="fw-bold mb-4">Exclusão de Registros</h2>

<?php if ($mensagem !== ""): ?>
    <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show">
        <?= htmlspecialchars($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white fw-bold">Excluir Manutenção</div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Veículo</th>
                            <th>Serviço</th>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manutencoes as $m): ?>
                            <tr class="<?= $idPreSelecionado === (int)$m['id'] ? 'table-warning' : '' ?>">
                                <td><?= htmlspecialchars($m['modelo']) ?> (<?= htmlspecialchars($m['placa']) ?>)</td>
                                <td><?= htmlspecialchars($m['servico']) ?></td>
                                <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
                                <td>R$ <?= number_format((float)$m['valor'], 2, ',', '.') ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Confirma a exclusão desta manutenção?');">
                                        <input type="hidden" name="tipo" value="manutencao">
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($manutencoes)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhuma manutenção para excluir.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Excluir Veículo</div>
            <div class="card-body">
                <p class="small text-muted">
                    Atenção: excluir um veículo também apaga todas as manutenções vinculadas a ele (<code>ON DELETE CASCADE</code>).
                </p>
                <ul class="list-group">
                    <?php foreach ($veiculos as $v): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($v['modelo']) ?></strong><br>
                                <small class="text-muted">Placa: <?= htmlspecialchars($v['placa']) ?></small>
                            </div>
                            <form method="POST" onsubmit="return confirm('Tem certeza? Isso apagará o veículo e todas as manutenções dele!');">
                                <input type="hidden" name="tipo" value="veiculo">
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($veiculos)): ?>
                        <li class="list-group-item text-center text-muted">Nenhum veículo cadastrado.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>