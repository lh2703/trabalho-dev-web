<?php

require_once __DIR__ . "/includes/funcoes.php";
require_once __DIR__ . "/includes/formularios.php";
require_once __DIR__ . "/config/conexao.php";


// IDENTIFICAR REGISTROS SELECIONADOS

$veiculoId = idValido($_GET["veiculo_id"] ?? 0);

$manutencaoId = idValido($_GET["id"] ?? 0);


// PROCESSAR ATUALIZAÇÕES

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $destino = "atualizar.php";

    try {

        validarCsrf();

        $acao = $_POST["acao"] ?? "";

        $id = idValido($_POST["id"] ?? 0);

        if (!$id) {
            throw new RuntimeException(
                "Identificador de registro inválido."
            );
        }


        // ATUALIZAR VEÍCULO

        if ($acao === "editar_veiculo") {

            $destino = "atualizar.php?veiculo_id=" . $id;

            $dados = dadosVeiculo($_POST);

            $pdo->beginTransaction();

            bloquearVeiculo($pdo, $id);


            // VERIFICAR QUILOMETRAGEM DO HISTÓRICO

            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(km_servico), 0)
                FROM manutencoes
                WHERE veiculo_id = ?
            ");

            $stmt->execute([$id]);

            $maiorKm = (int)$stmt->fetchColumn();

            if ($dados["km_atual"] < $maiorKm) {

                throw new RuntimeException(
                    "A quilometragem atual não pode ser inferior "
                    . "à maior quilometragem registrada no histórico: "
                    . quilometragem($maiorKm) . "."
                );

            }


            // SALVAR ALTERAÇÕES DO VEÍCULO

            $stmt = $pdo->prepare("
                UPDATE veiculos
                SET
                    modelo = ?,
                    ano = ?,
                    placa = ?,
                    km_atual = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $dados["modelo"],
                $dados["ano"],
                $dados["placa"],
                $dados["km_atual"],
                $id
            ]);

            $alterado = $stmt->rowCount() > 0;

            $pdo->commit();

            if ($alterado) {

                definirMensagem(
                    "Veículo atualizado com sucesso!",
                    "success"
                );

            } else {

                definirMensagem(
                    "Nenhuma alteração foi realizada.",
                    "info"
                );

            }

        }


        // ATUALIZAR MANUTENÇÃO

        elseif ($acao === "editar_manutencao") {

            $destino = "atualizar.php?id=" . $id;

            $dados = dadosManutencao($_POST);

            $registro = buscarManutencao($pdo, $id);

            if (!$registro) {

                throw new RuntimeException(
                    "Manutenção não encontrada."
                );

            }

            $pdo->beginTransaction();


            // BLOQUEAR OS VEÍCULOS ENVOLVIDOS

            $ids = array_unique([
                (int)$registro["veiculo_id"],
                $dados["veiculo_id"]
            ]);

            sort($ids, SORT_NUMERIC);

            foreach ($ids as $vid) {

                bloquearVeiculo($pdo, (int)$vid);

            }


            // VERIFICAR SE A MANUTENÇÃO AINDA EXISTE

            $stmt = $pdo->prepare("
                SELECT id, veiculo_id
                FROM manutencoes
                WHERE id = ?
                FOR UPDATE
            ");

            $stmt->execute([$id]);

            $registroAtual = $stmt->fetch();

            if (!$registroAtual) {

                throw new RuntimeException(
                    "A manutenção não existe mais."
                );

            }

            if (
                (int)$registroAtual["veiculo_id"]
                !== (int)$registro["veiculo_id"]
            ) {

                throw new RuntimeException(
                    "O registro foi alterado por outra operação. "
                    . "Atualize a página e tente novamente."
                );

            }


            // SALVAR ALTERAÇÕES DA MANUTENÇÃO

            $stmt = $pdo->prepare("
                UPDATE manutencoes
                SET
                    veiculo_id = ?,
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
                $dados["veiculo_id"],
                $dados["servico"],
                $dados["categoria"],
                $dados["data"],
                $dados["km_servico"],
                $dados["proxima_km"],
                $dados["valor"],
                $dados["observacao"],
                $id
            ]);

            $alterado = $stmt->rowCount() > 0;


            // ATUALIZAR QUILOMETRAGEM DO VEÍCULO

            atualizarQuilometragem(
                $pdo,
                $dados["veiculo_id"],
                $dados["km_servico"]
            );

            $pdo->commit();

            if ($alterado) {

                definirMensagem(
                    "Manutenção atualizada com sucesso!",
                    "success"
                );

            } else {

                definirMensagem(
                    "Nenhuma alteração foi realizada nos dados da manutenção.",
                    "info"
                );

            }

        }


        // OPERAÇÃO INVÁLIDA

        else {

            throw new RuntimeException(
                "Operação inválida."
            );

        }

    }


    // TRATAR ERROS DO BANCO DE DADOS

    catch (PDOException $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }

        tratarErroBanco(
            $e,
            "Não foi possível atualizar o registro."
        );

    }


    // TRATAR ERROS DE VALIDAÇÃO

    catch (RuntimeException $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }

        definirMensagem(
            $e->getMessage(),
            "warning"
        );

    }


    // EVITAR REENVIO DO FORMULÁRIO

    redirecionar($destino);

}


// CONSULTAR VEÍCULOS CADASTRADOS

$veiculos = $pdo->query("
    SELECT *
    FROM veiculos
    ORDER BY modelo, id
")->fetchAll();


// CONSULTAR MANUTENÇÕES CADASTRADAS

$manutencoes = $pdo->query("
    SELECT
        m.id,
        m.servico,
        m.data,
        v.modelo,
        v.placa
    FROM manutencoes m
    INNER JOIN veiculos v
        ON v.id = m.veiculo_id
    ORDER BY m.data DESC, m.id DESC
")->fetchAll();


// BUSCAR VEÍCULO SELECIONADO

$veiculo = $veiculoId
    ? buscarVeiculo($pdo, $veiculoId)
    : null;


// BUSCAR MANUTENÇÃO SELECIONADA

$manutencao = $manutencaoId
    ? buscarManutencao($pdo, $manutencaoId)
    : null;


// CARREGAR CABEÇALHO

require_once __DIR__ . "/includes/header.php";

?>


<!-- CABEÇALHO DA PÁGINA -->

<div class="mb-4">

    <h2 class="fw-bold mb-1">

        Atualização de Registros

    </h2>

    <p class="text-muted mb-0">

        Edite as informações dos veículos e das manutenções
        cadastradas na sua garagem.

    </p>

</div>


<!-- MENSAGENS DO SISTEMA -->

<?php exibirMensagem(); ?>


<!-- REGISTRO NÃO ENCONTRADO -->

<?php if (
    ($veiculoId && !$veiculo) ||
    ($manutencaoId && !$manutencao)
): ?>

    <div class="alert alert-warning">

        O registro solicitado não foi encontrado
        ou já foi excluído.

    </div>

<?php endif; ?>


<!-- SELEÇÃO DE REGISTROS -->

<div class="row g-4 mb-4">


    <!-- SELECIONAR VEÍCULO -->

    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white fw-bold py-3">

                Selecionar Veículo

            </div>

            <div class="card-body p-4">

                <p class="small text-muted">

                    Altere o modelo, ano, placa ou
                    quilometragem atual de um veículo.

                </p>

                <?php if (empty($veiculos)): ?>

                    <div class="alert alert-light border mb-0">

                        Nenhum veículo cadastrado.

                        <a href="cadastro.php">

                            Cadastrar veículo

                        </a>

                    </div>

                <?php else: ?>

                    <form method="GET" action="atualizar.php">

                        <label
                            for="selecionarVeiculo"
                            class="form-label">

                            Veículo

                        </label>

                        <select
                            name="veiculo_id"
                            id="selecionarVeiculo"
                            class="form-select mb-3"
                            required>

                            <option value="">

                                Selecione um veículo...

                            </option>

                            <?php foreach ($veiculos as $v): ?>

                                <option
                                    value="<?= (int)$v["id"] ?>"
                                    <?= $veiculoId === (int)$v["id"]
                                        ? "selected"
                                        : "" ?>>

                                    <?= h($v["modelo"]) ?>
                                    (<?= h($v["placa"]) ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            Editar Veículo

                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- SELECIONAR MANUTENÇÃO -->

    <div class="col-lg-6">

        <div class="card shadow-sm h-100">

            <div class="card-header bg-dark text-white fw-bold py-3">

                Selecionar Manutenção

            </div>

            <div class="card-body p-4">

                <p class="small text-muted">

                    Altere as informações de um serviço
                    registrado no histórico.

                </p>

                <?php if (empty($manutencoes)): ?>

                    <div class="alert alert-light border mb-0">

                        Nenhuma manutenção cadastrada.

                        <a href="cadastro.php">

                            Registrar manutenção

                        </a>

                    </div>

                <?php else: ?>

                    <form method="GET" action="atualizar.php">

                        <label
                            for="selecionarManutencao"
                            class="form-label">

                            Manutenção

                        </label>

                        <select
                            name="id"
                            id="selecionarManutencao"
                            class="form-select mb-3"
                            required>

                            <option value="">

                                Selecione uma manutenção...

                            </option>

                            <?php foreach ($manutencoes as $m): ?>

                                <option
                                    value="<?= (int)$m["id"] ?>"
                                    <?= $manutencaoId === (int)$m["id"]
                                        ? "selected"
                                        : "" ?>>

                                    <?= h($m["modelo"]) ?>
                                    (<?= h($m["placa"]) ?>)
                                    - <?= h($m["servico"]) ?>
                                    - <?= dataBrasileira($m["data"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            Editar Manutenção

                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<!-- FORMULÁRIO DE EDIÇÃO DO VEÍCULO -->

<?php if ($veiculo): ?>

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white py-3">

            <h6 class="fw-bold mb-0">

                Editar Veículo

            </h6>

        </div>

        <div class="card-body p-4">

            <div class="border-bottom pb-3 mb-4">

                <h5 class="fw-bold mb-1">

                    <?= h($veiculo["modelo"]) ?>

                </h5>

                <div class="small text-muted">

                    Placa: <?= h($veiculo["placa"]) ?>

                    |

                    Quilometragem atual:
                    <?= quilometragem($veiculo["km_atual"]) ?>

                </div>

            </div>

            <form method="POST" action="atualizar.php">

                <?= campoCsrf() ?>

                <input
                    type="hidden"
                    name="acao"
                    value="editar_veiculo">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$veiculo["id"] ?>">


                <!-- CAMPOS DO VEÍCULO -->

                <?php camposVeiculo($veiculo); ?>


                <!-- INFORMAÇÃO SOBRE QUILOMETRAGEM -->

                <div class="alert alert-light border small mt-4">

                    A quilometragem atual não pode ser inferior
                    à maior quilometragem registrada no histórico
                    de manutenções deste veículo.

                </div>


                <!-- BOTÕES -->

                <div class="d-flex flex-wrap gap-2 mt-4">

                    <button
                        type="submit"
                        class="btn btn-success">

                        Salvar Alterações

                    </button>

                    <a
                        href="atualizar.php"
                        class="btn btn-outline-secondary">

                        Cancelar

                    </a>

                    <a
                        href="index.php"
                        class="btn btn-outline-primary">

                        Voltar à Home

                    </a>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<!-- FORMULÁRIO DE EDIÇÃO DA MANUTENÇÃO -->

<?php if ($manutencao): ?>

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white py-3">

            <h6 class="fw-bold mb-0">

                Editar Manutenção

            </h6>

        </div>

        <div class="card-body p-4">

            <div class="border-bottom pb-3 mb-4">

                <h5 class="fw-bold mb-1">

                    <?= h($manutencao["servico"]) ?>

                </h5>

                <div class="small text-muted">

                    Data do serviço:
                    <?= dataBrasileira($manutencao["data"]) ?>

                    |

                    Valor registrado:
                    <?= moeda($manutencao["valor"]) ?>

                </div>

            </div>

            <form method="POST" action="atualizar.php">

                <?= campoCsrf() ?>

                <input
                    type="hidden"
                    name="acao"
                    value="editar_manutencao">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)$manutencao["id"] ?>">


                <!-- CAMPOS DA MANUTENÇÃO -->

                <?php camposManutencao(
                    $veiculos,
                    $manutencao
                ); ?>


                <!-- INFORMAÇÃO SOBRE QUILOMETRAGEM -->

                <div class="alert alert-light border small mt-4">

                    Caso a quilometragem informada no serviço
                    seja superior à quilometragem atual do veículo,
                    o sistema atualizará esse valor automaticamente.

                </div>


                <!-- BOTÕES -->

                <div class="d-flex flex-wrap gap-2 mt-4">

                    <button
                        type="submit"
                        class="btn btn-success">

                        Salvar Alterações

                    </button>

                    <a
                        href="atualizar.php"
                        class="btn btn-outline-secondary">

                        Cancelar

                    </a>

                    <a
                        href="consulta.php"
                        class="btn btn-outline-primary">

                        Voltar à Consulta

                    </a>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<!-- ORIENTAÇÃO INICIAL -->

<?php if (!$veiculo && !$manutencao): ?>

    <div class="card shadow-sm">

        <div class="card-body text-center py-5">

            <h5 class="fw-bold mb-2">

                Selecione um registro para editar

            </h5>

            <p class="text-muted mb-0">

                Utilize os campos acima para escolher
                um veículo ou uma manutenção.

            </p>

        </div>

    </div>

<?php endif; ?>


<!-- RODAPÉ -->

<?php require_once __DIR__ . "/includes/footer.php"; ?>