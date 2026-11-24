<?php

require_once __DIR__ . "/includes/funcoes.php";
require_once __DIR__ . "/includes/formularios.php";
require_once __DIR__ . "/config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        validarCsrf();

        $acao = $_POST["acao"] ?? "";

        // CADASTRAR VEÍCULO

        if ($acao === "cadastrar_veiculo") {

            $dados = dadosVeiculo($_POST);

            $stmt = $pdo->prepare("
                INSERT INTO veiculos (
                    modelo,
                    ano,
                    placa,
                    km_atual
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $dados["modelo"],
                $dados["ano"],
                $dados["placa"],
                $dados["km_atual"]
            ]);

            definirMensagem(
                "Veículo cadastrado com sucesso!"
            );

        }

        // CADASTRAR MANUTENÇÃO

        elseif ($acao === "cadastrar_manutencao") {

            $dados = dadosManutencao($_POST);

            $pdo->beginTransaction();

            bloquearVeiculo(
                $pdo,
                $dados["veiculo_id"]
            );

            $stmt = $pdo->prepare("
                INSERT INTO manutencoes (
                    veiculo_id,
                    servico,
                    categoria,
                    data,
                    km_servico,
                    proxima_km,
                    valor,
                    observacao
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $dados["veiculo_id"],
                $dados["servico"],
                $dados["categoria"],
                $dados["data"],
                $dados["km_servico"],
                $dados["proxima_km"],
                $dados["valor"],
                $dados["observacao"]
            ]);

            atualizarQuilometragem(
                $pdo,
                $dados["veiculo_id"],
                $dados["km_servico"]
            );

            $pdo->commit();

            definirMensagem(
                "Manutenção registrada com sucesso!"
            );

        } else {

            throw new RuntimeException(
                "Operação inválida."
            );

        }

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        tratarErroBanco(
            $e,
            "Não foi possível realizar o cadastro."
        );

    } catch (RuntimeException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        definirMensagem(
            $e->getMessage(),
            "warning"
        );

    }

    redirecionar("cadastro.php");
}


// LISTAGEM DE VEÍCULOS

$veiculos = $pdo->query("
    SELECT id, modelo, placa, km_atual
    FROM veiculos
    ORDER BY modelo, id
")->fetchAll();

require_once __DIR__ . "/includes/header.php";

?>

<h2 class="fw-bold mb-4">
    Cadastro
</h2>

<?php exibirMensagem(); ?>

<div class="row g-4">

    <!-- CADASTRO DE VEÍCULOS -->

    <div class="col-lg-5">

        <div class="card shadow-sm">

            <div class="card-header bg-dark text-white fw-bold">

                1. Cadastrar Veículo

            </div>

            <div class="card-body">

                <form method="POST" action="cadastro.php">

                    <?= campoCsrf() ?>

                    <input
                        type="hidden"
                        name="acao"
                        value="cadastrar_veiculo">

                    <?php camposVeiculo(); ?>

                    <button
                        type="submit"
                        class="btn btn-primary w-100 mt-4">

                        Cadastrar Veículo

                    </button>

                </form>

            </div>

        </div>

    </div>

    <!-- CADASTRO DE MANUTENÇÕES -->

    <div class="col-lg-7">

        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white fw-bold">

                2. Registrar Manutenção

            </div>

            <div class="card-body">

                <?php if (empty($veiculos)): ?>

                    <div class="alert alert-warning mb-0">

                        Cadastre um veículo antes de registrar uma manutenção.

                    </div>

                <?php else: ?>

                    <form method="POST" action="cadastro.php">

                        <?= campoCsrf() ?>

                        <input
                            type="hidden"
                            name="acao"
                            value="cadastrar_manutencao">

                        <?php camposManutencao($veiculos); ?>

                        <button
                            type="submit"
                            class="btn btn-success w-100 mt-4">

                            Registrar Manutenção

                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>