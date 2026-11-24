<?php
require_once "config/conexao.php";

$mensagem = "";
$tipoAlerta = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"] ?? "";

    try {
        if ($acao === "cadastrar_veiculo") {
            $modelo = trim($_POST["modelo"]);
            $ano = (int)$_POST["ano"];
            $placa = strtoupper(trim($_POST["placa"]));
            $km_atual = (int)$_POST["km_atual"];

            $stmt = $pdo->prepare("INSERT INTO veiculos (modelo, ano, placa, km_atual) VALUES (?, ?, ?, ?)");
            $stmt->execute([$modelo, $ano, $placa, $km_atual]);
            $mensagem = "Veículo cadastrado com sucesso!";
        }

        if ($acao === "cadastrar_manutencao") {
            $veiculo_id = (int)$_POST["veiculo_id"];
            $servico = trim($_POST["servico"]);
            $categoria = trim($_POST["categoria"]);
            $data = $_POST["data"];
            $km_servico = (int)$_POST["km_servico"];
            $proxima_km = (int)$_POST["proxima_km"];
            $valor = (float)$_POST["valor"];
            $observacao = trim($_POST["observacao"]);

            $stmt = $pdo->prepare("
                INSERT INTO manutencoes (veiculo_id, servico, categoria, data, km_servico, proxima_km, valor, observacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$veiculo_id, $servico, $categoria, $data, $km_servico, $proxima_km, $valor, $observacao]);

            $pdo->prepare("UPDATE veiculos SET km_atual = GREATEST(km_atual, ?) WHERE id = ?")
                ->execute([$km_servico, $veiculo_id]);

            $mensagem = "Manutenção registrada com sucesso!";
        }
    } catch (PDOException $e) {
        $tipoAlerta = "danger";
        $mensagem = "Erro ao salvar: " . $e->getMessage();
    }
}

$veiculos = $pdo->query("SELECT id, modelo, placa, km_atual FROM veiculos ORDER BY modelo")->fetchAll();
require_once "includes/header.php";
?>

<h2 class="fw-bold mb-4">Cadastro</h2>

<?php if ($mensagem !== ""): ?>
    <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show">
        <?= htmlspecialchars($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">1. Cadastrar Veículo</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_veiculo">
                    <div class="mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control" placeholder="Ex: Hyundai HB20 1.0" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ano</label>
                            <input type="number" name="ano" class="form-control" min="1950" max="2030" value="2022" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Placa</label>
                            <input type="text" name="placa" class="form-control" maxlength="10" placeholder="ABC1D23" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quilometragem Atual (km)</label>
                        <input type="number" name="km_atual" class="form-control" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Salvar Veículo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white fw-bold">2. Registrar Manutenção / Upgrade</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_manutencao">
                    <div class="mb-3">
                        <label class="form-label">Veículo</label>
                        <select name="veiculo_id" class="form-select" required>
                            <option value="">Selecione um veículo...</option>
                            <?php foreach ($veiculos as $v): ?>
                                <option value="<?= (int)$v['id'] ?>">
                                    <?= htmlspecialchars($v['modelo']) ?> (<?= htmlspecialchars($v['placa']) ?> - <?= (int)$v['km_atual'] ?> km)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Serviço / Peça</label>
                            <input type="text" name="servico" class="form-control" placeholder="Ex: Troca de óleo e filtro" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="categoria" class="form-select">
                                <option value="Revisão">Revisão</option>
                                <option value="Freios">Freios</option>
                                <option value="Suspensão">Suspensão</option>
                                <option value="Motor">Motor</option>
                                <option value="Upgrade">Upgrade</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">KM no Serviço</label>
                            <input type="number" name="km_servico" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Próxima Revisão (KM)</label>
                            <input type="number" name="proxima_km" class="form-control" min="0" placeholder="Ex: 55000">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="number" step="0.01" name="valor" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Observação</label>
                            <input type="text" name="observacao" class="form-control" placeholder="Marca da peça, oficina, detalhes...">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Registrar Serviço</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>