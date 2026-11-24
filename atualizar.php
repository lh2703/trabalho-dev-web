<?php
require_once "config/conexao.php";

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = "";
$tipoAlerta = "success";

$veiculoId = (int)($_GET['veiculo_id'] ?? 0);
$manutencaoId = (int)($_GET['id'] ?? 0);

$categorias = [
    "Revisão",
    "Freios",
    "Suspensão",
    "Motor",
    "Upgrade",
    "Outros"
];

function h($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $acao = $_POST["acao"] ?? "";

    try {

        if (!hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token'] ?? ''
        )) {
            throw new RuntimeException("Requisição inválida.");
        }

        // ATUALIZAR VEÍCULO

        if ($acao === "editar_veiculo") {

            $veiculoId = (int)($_POST["id"] ?? 0);
            $modelo = trim($_POST["modelo"] ?? "");
            $ano = (int)($_POST["ano"] ?? 0);
            $placa = strtoupper(trim($_POST["placa"] ?? ""));
            $km_atual = (int)($_POST["km_atual"] ?? -1);

            if (
                $veiculoId <= 0 ||
                $modelo === "" ||
                $placa === "" ||
                $ano < 1900 ||
                $ano > (int)date("Y") + 1 ||
                $km_atual < 0 ||
                strlen($placa) > 10 ||
                mb_strlen($modelo) > 100
            ) {
                throw new RuntimeException(
                    "Preencha corretamente os dados do veículo."
                );
            }

            $stmt = $pdo->prepare("
                UPDATE veiculos
                SET modelo = ?,
                    ano = ?,
                    placa = ?,
                    km_atual = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $modelo,
                $ano,
                $placa,
                $km_atual,
                $veiculoId
            ]);

            $mensagem = "Veículo atualizado com sucesso!";
        }

        // ATUALIZAR MANUTENÇÃO

        elseif ($acao === "editar_manutencao") {

            $manutencaoId = (int)($_POST["id"] ?? 0);
            $veiculo_id = (int)($_POST["veiculo_id"] ?? 0);

            $servico = trim($_POST["servico"] ?? "");
            $categoria = trim($_POST["categoria"] ?? "");
            $data = $_POST["data"] ?? "";

            $km_servico = (int)($_POST["km_servico"] ?? -1);
            $proxima_km = (int)($_POST["proxima_km"] ?? -1);

            $valor = filter_var(
                $_POST["valor"] ?? "",
                FILTER_VALIDATE_FLOAT
            );

            $observacao = trim($_POST["observacao"] ?? "");

            $dataObj = DateTime::createFromFormat('!Y-m-d', $data);

            if (
                $manutencaoId <= 0 ||
                $veiculo_id <= 0 ||
                $servico === "" ||
                mb_strlen($servico) > 120 ||
                !$dataObj ||
                $dataObj->format('Y-m-d') !== $data ||
                !in_array($categoria, $categorias, true) ||
                $km_servico < 0 ||
                $proxima_km < 0 ||
                $valor === false ||
                $valor < 0 ||
                $valor > 99999999.99
            ) {
                throw new RuntimeException(
                    "Preencha corretamente os dados da manutenção."
                );
            }

            if ($proxima_km > 0 && $proxima_km <= $km_servico) {
                throw new RuntimeException(
                    "A próxima revisão deve possuir quilometragem superior à do serviço."
                );
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT id
                FROM manutencoes
                WHERE id = ?
                FOR UPDATE
            ");

            $stmt->execute([$manutencaoId]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    "Manutenção não encontrada."
                );
            }

            $stmt = $pdo->prepare("
                UPDATE manutencoes
                SET veiculo_id = ?,
                    servico = ?,
                    categoria = ?,
                    data = ?,
                    km_servico = ?,
                    proxima_km = ?,
                    valor = ?,
                    observacao = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $veiculo_id,
                $servico,
                $categoria,
                $data,
                $km_servico,
                $proxima_km,
                $valor,
                $observacao,
                $manutencaoId
            ]);

            // Atualiza a quilometragem somente se for maior.

            $stmt = $pdo->prepare("
                UPDATE veiculos
                SET km_atual = GREATEST(km_atual, ?)
                WHERE id = ?
            ");

            $stmt->execute([
                $km_servico,
                $veiculo_id
            ]);

            $pdo->commit();

            $mensagem = "Manutenção atualizada com sucesso!";
        }

        else {
            throw new RuntimeException("Operação inválida.");
        }

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $tipoAlerta = "danger";

        if ($e->getCode() === "23000") {
            $mensagem = "Dados duplicados ou relacionamento inválido.";
        } else {
            $mensagem = "Não foi possível atualizar o registro.";
        }

        error_log($e->getMessage());

    } catch (RuntimeException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $tipoAlerta = "danger";
        $mensagem = $e->getMessage();
    }
}

// CONSULTAR VEÍCULOS

$veiculos = $pdo->query("
    SELECT *
    FROM veiculos
    ORDER BY modelo
")->fetchAll();

// CONSULTAR MANUTENÇÕES

$manutencoes = $pdo->query("
    SELECT
        m.id,
        m.servico,
        m.data,
        v.modelo,
        v.placa
    FROM manutencoes m
    INNER JOIN veiculos v
        ON m.veiculo_id = v.id
    ORDER BY m.data DESC, m.id DESC
")->fetchAll();

$veiculo = null;
$manutencao = null;

// BUSCAR VEÍCULO SELECIONADO

if ($veiculoId > 0) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM veiculos
        WHERE id = ?
    ");

    $stmt->execute([$veiculoId]);
    $veiculo = $stmt->fetch();

    if (!$veiculo) {
        $tipoAlerta = "warning";
        $mensagem = "Veículo não encontrado.";
    }
}

// BUSCAR MANUTENÇÃO SELECIONADA

if ($manutencaoId > 0) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM manutencoes
        WHERE id = ?
    ");

    $stmt->execute([$manutencaoId]);
    $manutencao = $stmt->fetch();

    if (!$manutencao) {
        $tipoAlerta = "warning";
        $mensagem = "Manutenção não encontrada.";
    }
}

require_once "includes/header.php";
?>

<div class="mb-4">
    <h2 class="fw-bold">Atualização de Registros</h2>

    <p class="text-muted">
        Altere as informações dos veículos e serviços cadastrados.
    </p>
</div>

<?php if ($mensagem !== ""): ?>

    <div class="alert alert-<?= h($tipoAlerta) ?> alert-dismissible fade show">

        <?= h($mensagem) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>

<!-- SELEÇÃO DOS REGISTROS -->

<div class="row g-4 mb-4">

    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white fw-bold">
                Selecionar Veículo
            </div>

            <div class="card-body">

                <form method="GET">

                    <label class="form-label">
                        Veículo
                    </label>

                    <select
                        name="veiculo_id"
                        class="form-select mb-3"
                        required>

                        <option value="">
                            Selecione um veículo...
                        </option>

                        <?php foreach ($veiculos as $v): ?>

                            <option
                                value="<?= (int)$v['id'] ?>"
                                <?= $veiculoId === (int)$v['id'] ? 'selected' : '' ?>>

                                <?= h($v['modelo']) ?>
                                - <?= h($v['placa']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Editar Veículo

                    </button>

                </form>

            </div>

        </div>

    </div>

    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white fw-bold">
                Selecionar Manutenção
            </div>

            <div class="card-body">

                <form method="GET">

                    <label class="form-label">
                        Manutenção
                    </label>

                    <select
                        name="id"
                        class="form-select mb-3"
                        required>

                        <option value="">
                            Selecione uma manutenção...
                        </option>

                        <?php foreach ($manutencoes as $m): ?>

                            <option
                                value="<?= (int)$m['id'] ?>"
                                <?= $manutencaoId === (int)$m['id'] ? 'selected' : '' ?>>

                                <?= h($m['modelo']) ?>
                                - <?= h($m['servico']) ?>
                                (<?= date('d/m/Y', strtotime($m['data'])) ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        type="submit"
                        class="btn btn-primary w-100">

                        Editar Manutenção

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- FORMULÁRIO DE EDIÇÃO DE VEÍCULO -->

<?php if ($veiculo): ?>

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white fw-bold">
            Editar Veículo
        </div>

        <div class="card-body p-4">

            <form method="POST" action="atualizar.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['csrf_token']) ?>">

                <input
                    type="hidden"
                    name="acao"
                    value="editar_veiculo">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$veiculo['id'] ?>">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">Modelo</label>

                        <input
                            type="text"
                            name="modelo"
                            class="form-control"
                            maxlength="100"
                            value="<?= h($veiculo['modelo']) ?>"
                            required>

                    </div>

                    <div class="col-md-2">

                        <label class="form-label">Ano</label>

                        <input
                            type="number"
                            name="ano"
                            class="form-control"
                            min="1900"
                            max="<?= date('Y') + 1 ?>"
                            value="<?= (int)$veiculo['ano'] ?>"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">Placa</label>

                        <input
                            type="text"
                            name="placa"
                            class="form-control"
                            maxlength="10"
                            value="<?= h($veiculo['placa']) ?>"
                            required>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Quilometragem Atual
                        </label>

                        <input
                            type="number"
                            name="km_atual"
                            class="form-control"
                            min="0"
                            value="<?= (int)$veiculo['km_atual'] ?>"
                            required>

                    </div>

                </div>

                <div class="d-flex gap-2 mt-4">

                    <button type="submit" class="btn btn-success">
                        Salvar Alterações
                    </button>

                    <a href="index.php" class="btn btn-secondary">
                        Voltar
                    </a>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>

<!-- FORMULÁRIO DE EDIÇÃO DE MANUTENÇÃO -->

<?php if ($manutencao): ?>

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white fw-bold">
            Editar Manutenção
        </div>

        <div class="card-body p-4">

            <form method="POST" action="atualizar.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['csrf_token']) ?>">

                <input
                    type="hidden"
                    name="acao"
                    value="editar_manutencao">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$manutencao['id'] ?>">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">Veículo</label>

                        <select
                            name="veiculo_id"
                            class="form-select"
                            required>

                            <?php foreach ($veiculos as $v): ?>

                                <option
                                    value="<?= (int)$v['id'] ?>"
                                    <?= (int)$manutencao['veiculo_id'] === (int)$v['id'] ? 'selected' : '' ?>>

                                    <?= h($v['modelo']) ?>
                                    - <?= h($v['placa']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">Serviço / Peça</label>

                        <input
                            type="text"
                            name="servico"
                            class="form-control"
                            maxlength="120"
                            value="<?= h($manutencao['servico']) ?>"
                            required>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">Categoria</label>

                        <select
                            name="categoria"
                            class="form-select"
                            required>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= h($categoria) ?>"
                                    <?= $manutencao['categoria'] === $categoria ? 'selected' : '' ?>>

                                    <?= h($categoria) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">Data do Serviço</label>

                        <input
                            type="date"
                            name="data"
                            class="form-control"
                            value="<?= h($manutencao['data']) ?>"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            KM no Serviço
                        </label>

                        <input
                            type="number"
                            name="km_servico"
                            class="form-control"
                            min="0"
                            value="<?= (int)$manutencao['km_servico'] ?>"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Próxima Revisão (KM)
                        </label>

                        <input
                            type="number"
                            name="proxima_km"
                            class="form-control"
                            min="0"
                            value="<?= (int)$manutencao['proxima_km'] ?>"
                            required>

                        <small class="text-muted">
                            Informe 0 caso não exista previsão.
                        </small>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">Valor (R$)</label>

                        <input
                            type="number"
                            name="valor"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= h($manutencao['valor']) ?>"
                            required>

                    </div>

                    <div class="col-12">

                        <label class="form-label">Observações</label>

                        <textarea
                            name="observacao"
                            class="form-control"
                            rows="4"><?= h($manutencao['observacao']) ?></textarea>

                    </div>

                </div>

                <div class="d-flex gap-2 mt-4">

                    <button type="submit" class="btn btn-success">
                        Salvar Alterações
                    </button>

                    <a href="consulta.php" class="btn btn-secondary">
                        Voltar
                    </a>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>

<?php require_once "includes/footer.php"; ?>