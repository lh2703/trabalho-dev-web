<?php

require_once __DIR__ . "/includes/funcoes.php";
require_once __DIR__ . "/config/conexao.php";


// PROCESSAR EXCLUSÃO

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        validarCsrf();

        $tipo = $_POST["tipo"] ?? "";

        $id = idValido($_POST["id"] ?? 0);

        if (!$id) {

            throw new RuntimeException(
                "Identificador inválido."
            );

        }

        // EXCLUIR MANUTENÇÃO

        if ($tipo === "manutencao") {

            $stmt = $pdo->prepare("
                DELETE FROM manutencoes
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            if ($stmt->rowCount() === 1) {

                definirMensagem(
                    "Manutenção #$id excluída com sucesso!"
                );

            } else {

                definirMensagem(
                    "A manutenção não existe ou já foi excluída.",
                    "warning"
                );

            }

        }

        // EXCLUIR VEÍCULO

        elseif ($tipo === "veiculo") {

            $stmt = $pdo->prepare("
                DELETE FROM veiculos
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            if ($stmt->rowCount() === 1) {

                definirMensagem(
                    "Veículo #$id e suas manutenções excluídos com sucesso!"
                );

            } else {

                definirMensagem(
                    "O veículo não existe ou já foi excluído.",
                    "warning"
                );

            }

        } else {

            throw new RuntimeException(
                "Tipo de registro inválido."
            );

        }

    } catch (PDOException $e) {

        tratarErroBanco(
            $e,
            "Não foi possível excluir o registro."
        );

    } catch (RuntimeException $e) {

        definirMensagem(
            $e->getMessage(),
            "warning"
        );

    }

    redirecionar("excluir.php");
}


// IDENTIFICADOR RECEBIDO DA CONSULTA

$idPreSelecionado = idValido($_GET["id"] ?? 0);


// LISTAR MANUTENÇÕES

$manutencoes = $pdo->query("
    SELECT
        m.id,
        m.servico,
        m.data,
        m.valor,
        v.modelo,
        v.placa
    FROM manutencoes m
    INNER JOIN veiculos v
        ON m.veiculo_id = v.id
    ORDER BY m.data DESC, m.id DESC
")->fetchAll();


// LISTAR VEÍCULOS

$veiculos = $pdo->query("
    SELECT
        v.id,
        v.modelo,
        v.ano,
        v.placa,
        v.km_atual,
        (
            SELECT COUNT(*)
            FROM manutencoes m
            WHERE m.veiculo_id = v.id
        ) AS total_manutencoes
    FROM veiculos v
    ORDER BY v.modelo, v.id
")->fetchAll();

require_once __DIR__ . "/includes/header.php";

?>

<h2 class="fw-bold mb-2">

    Exclusão de Registros

</h2>

<p class="text-muted mb-4">

    Gerencie a remoção de veículos e manutenções cadastradas.

</p>

<?php exibirMensagem(); ?>

<div class="row g-4">

    <!-- EXCLUSÃO DE MANUTENÇÕES -->

    <div class="col-lg-7">

        <div class="card shadow-sm">

            <div class="card-header bg-danger text-white fw-bold">

                Excluir Manutenção

            </div>

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

                            <tr class="<?= $idPreSelecionado === (int)$m["id"]
                                ? "table-warning"
                                : "" ?>">

                                <td>

                                    <strong>

                                        <?= h($m["modelo"]) ?>

                                    </strong>

                                    <div class="small text-muted">

                                        <?= h($m["placa"]) ?>

                                    </div>

                                </td>

                                <td>

                                    <?= h($m["servico"]) ?>

                                </td>

                                <td>

                                    <?= dataBrasileira($m["data"]) ?>

                                </td>

                                <td>

                                    <?= moeda($m["valor"]) ?>

                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        action="excluir.php"
                                        data-confirmar="Confirma a exclusão permanente desta manutenção?">

                                        <?= campoCsrf() ?>

                                        <input
                                            type="hidden"
                                            name="tipo"
                                            value="manutencao">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$m["id"] ?>">

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger">

                                            Excluir

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (empty($manutencoes)): ?>

                            <tr>

                                <td class="text-center text-muted py-4" colspan="5">

                                    Nenhuma manutenção para excluir.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- EXCLUSÃO DE VEÍCULOS -->

    <div class="col-lg-5">

        <div class="card shadow-sm">

            <div class="card-header bg-dark text-white fw-bold">

                Excluir Veículo

            </div>

            <div class="card-body">

                <div class="alert alert-warning small">

                    A exclusão de um veículo também remove
                    permanentemente todas as suas manutenções.

                </div>

                <ul class="list-group">

                    <?php foreach ($veiculos as $v): ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center gap-3">

                            <div>

                                <strong>

                                    <?= h($v["modelo"]) ?>

                                </strong>

                                <div class="small text-muted">

                                    Placa: <?= h($v["placa"]) ?>

                                </div>

                                <div class="small text-muted">

                                    Ano: <?= (int)$v["ano"] ?>

                                    | ID: <?= (int)$v["id"] ?>

                                </div>

                                <div class="small text-muted">

                                    <?= (int)$v["total_manutencoes"] ?>

                                    manutenção(ões) vinculada(s)

                                </div>

                            </div>

                            <form
                                method="POST"
                                action="excluir.php"
                                data-confirmar="<?= h(
                                    "Confirma a exclusão do veículo "
                                    . $v["modelo"]
                                    . ", placa "
                                    . $v["placa"]
                                    . ", e de todas as suas manutenções?"
                                ) ?>">

                                <?= campoCsrf() ?>

                                <input
                                    type="hidden"
                                    name="tipo"
                                    value="veiculo">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$v["id"] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-danger">

                                    Remover

                                </button>

                            </form>

                        </li>

                    <?php endforeach; ?>

                    <?php if (empty($veiculos)): ?>

                        <li class="list-group-item text-center text-muted">

                            Nenhum veículo cadastrado.

                        </li>

                    <?php endif; ?>

                </ul>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>