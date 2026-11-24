<?php

require_once __DIR__ . "/includes/funcoes.php";
require_once __DIR__ . "/config/conexao.php";


// IDENTIFICAR FILTRO DE VEÍCULO

$veiculoFiltro = idValido($_GET["veiculo_id"] ?? 0);


// CONSULTAR MANUTENÇÕES

$sql = "
    SELECT
        m.*,
        v.modelo,
        v.placa,
        v.km_atual
    FROM manutencoes m
    INNER JOIN veiculos v
        ON m.veiculo_id = v.id
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


// LISTAR VEÍCULOS

$veiculos = $pdo->query("
    SELECT id, modelo, placa
    FROM veiculos
    ORDER BY modelo, id
")->fetchAll();


// CALCULAR TOTAL INICIAL

$totalInicial = array_sum(
    array_column($manutencoes, "valor")
);


require_once __DIR__ . "/includes/header.php";

?>

<!-- CABEÇALHO DA PÁGINA -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

    <div>

        <h2 class="fw-bold mb-1">

            Consulta de Manutenções

        </h2>

        <p class="text-muted mb-0">

            Consulte e acompanhe o histórico de serviços dos seus veículos.

        </p>

    </div>

    <div class="bg-white px-4 py-3 rounded shadow-sm">

        <div class="small text-muted">

            Total exibido

        </div>

        <strong class="text-success fs-4" id="totalFiltrado">

            <?= moeda($totalInicial) ?>

        </strong>

    </div>

</div>


<!-- FILTROS -->

<div class="card shadow-sm mb-4">

    <div class="card-body">

        <div class="row g-3">

            <!-- FILTRO POR VEÍCULO -->

            <div class="col-md-4">

                <form method="GET" action="consulta.php">

                    <label for="filtroVeiculo" class="form-label">

                        Filtrar por veículo

                    </label>

                    <select
                        name="veiculo_id"
                        id="filtroVeiculo"
                        class="form-select"
                        onchange="this.form.submit()">

                        <option value="0">

                            Todos os veículos

                        </option>

                        <?php foreach ($veiculos as $v): ?>

                            <option
                                value="<?= (int)$v["id"] ?>"
                                <?= $veiculoFiltro === (int)$v["id"]
                                    ? "selected"
                                    : "" ?>>

                                <?= h($v["modelo"]) ?>
                                (<?= h($v["placa"]) ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <noscript>

                        <button
                            type="submit"
                            class="btn btn-primary mt-2">

                            Filtrar

                        </button>

                    </noscript>

                </form>

            </div>


            <!-- FILTRO POR CATEGORIA -->

            <div class="col-md-4">

                <label for="filtroCategoria" class="form-label">

                    Categoria

                </label>

                <select
                    id="filtroCategoria"
                    class="form-select">

                    <option value="">

                        Todas as categorias

                    </option>

                    <?php foreach (categorias() as $categoria): ?>

                        <option value="<?= h($categoria) ?>">

                            <?= h($categoria) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- PESQUISA TEXTUAL -->

            <div class="col-md-4">

                <label for="filtroTexto" class="form-label">

                    Buscar

                </label>

                <input
                    type="search"
                    id="filtroTexto"
                    class="form-control"
                    placeholder="Serviço, veículo, placa...">

            </div>

        </div>


        <!-- CONTADOR E LIMPEZA DOS FILTROS -->

        <div class="d-flex justify-content-between align-items-center mt-3">

            <small class="text-muted" id="quantidadeFiltrada">

                <?= count($manutencoes) ?> registro(s)

            </small>

            <a
                href="consulta.php"
                class="btn btn-sm btn-outline-secondary">

                Limpar filtros

            </a>

        </div>

    </div>

</div>


<!-- HISTÓRICO DE MANUTENÇÕES -->

<div class="card shadow-sm">

    <div class="card-header bg-white py-3">

        <h6 class="fw-bold mb-0">

            Histórico de Manutenções

        </h6>

    </div>

    <div class="card-body table-responsive">

        <table
            class="table table-hover align-middle mb-0"
            id="tabelaManutencoes">

            <thead>

                <tr>

                    <th scope="col">Veículo</th>

                    <th scope="col">Serviço</th>

                    <th scope="col">Categoria</th>

                    <th scope="col">Data</th>

                    <th scope="col">KM Serviço</th>

                    <th scope="col">Próxima Revisão</th>

                    <th scope="col">Status</th>

                    <th scope="col">Valor</th>

                    <th scope="col">Ações</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($manutencoes as $m): ?>

                    <?php

                    $status = statusManutencao(
                        (int)$m["km_atual"],
                        (int)$m["proxima_km"]
                    );

                    $observacao = trim(
                        (string)($m["observacao"] ?? "")
                    );

                    ?>

                    <tr
                        data-valor="<?= h($m["valor"]) ?>"
                        data-categoria="<?= h($m["categoria"]) ?>">


                        <!-- VEÍCULO -->

                        <td class="veiculo-coluna">

                            <div class="veiculo-titulo">
                                <?= h($m["modelo"]) ?>
                            </div>

                            <div class="veiculo-placa">
                                <?= h($m["placa"]) ?>
                            </div>

                        </td>


                        <!-- SERVIÇO E OBSERVAÇÃO -->

                        <td class="servico-coluna">

                            <div class="servico-titulo">
                                <?= h($m["servico"]) ?>
                            </div>

                            <?php if ($observacao !== ""): ?>

                                <div class="observacao">
                                    <?= h($observacao) ?>
                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- CATEGORIA -->

                        <td>

                            <span class="badge bg-secondary">

                                <?= h($m["categoria"]) ?>

                            </span>

                        </td>


                        <!-- DATA -->

                        <td class="data-coluna">

                            <?= dataBrasileira($m["data"]) ?>

                        </td>


                        <!-- QUILOMETRAGEM DO SERVIÇO -->

                        <td class="km-coluna">

                            <?= quilometragem($m["km_servico"]) ?>

                        </td>


                        <!-- PRÓXIMA REVISÃO -->

                        <td class="km-coluna">

                            <?php if ((int)$m["proxima_km"] > 0): ?>

                                <?= quilometragem($m["proxima_km"]) ?>

                            <?php else: ?>

                                <span class="text-muted">

                                    -

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- STATUS -->

                        <td class="status-coluna">

                            <span class="badge bg-<?= h($status["classe"]) ?>">

                                <?= h($status["texto"]) ?>

                            </span>

                        </td>


                        <!-- VALOR -->

                        <td class="valor-coluna">

                            <?= moeda($m["valor"]) ?>

                        </td>


                        <!-- AÇÕES -->

                        <td>

                            <div class="acoes">

                                <a
                                    href="atualizar.php?id=<?= (int)$m["id"] ?>"
                                    class="btn btn-sm btn-outline-primary">

                                    Editar

                                </a>

                                <a
                                    href="excluir.php?id=<?= (int)$m["id"] ?>"
                                    class="btn btn-sm btn-outline-danger">

                                    Excluir

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <!-- MENSAGEM SEM RESULTADOS -->

                <tr
                    id="semResultados"
                    <?= !empty($manutencoes) ? "hidden" : "" ?>>

                    <td
                        colspan="9"
                        class="text-center text-muted py-5">

                        <div class="fw-semibold mb-1">

                            Nenhuma manutenção encontrada.

                        </div>

                        <div class="small">

                            Verifique os filtros ou cadastre um novo serviço.

                        </div>

                        <a
                            href="cadastro.php"
                            class="btn btn-sm btn-outline-primary mt-3">

                            Registrar manutenção

                        </a>

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>


<!-- LEGENDA DOS STATUS -->

<div class="card shadow-sm mt-4">

    <div class="card-body">

        <h6 class="fw-bold mb-3">

            Indicadores de revisão

        </h6>

        <div class="row g-3">

            <div class="col-sm-6 col-lg-3">

                <span class="badge bg-success mb-2">

                    Em dia

                </span>

                <div class="small text-muted">

                    Mais de 1.500 km restantes.

                </div>

            </div>

            <div class="col-sm-6 col-lg-3">

                <span class="badge bg-warning text-dark mb-2">

                    Atenção

                </span>

                <div class="small text-muted">

                    Até 1.500 km restantes.

                </div>

            </div>

            <div class="col-sm-6 col-lg-3">

                <span class="badge bg-danger mb-2">

                    Vencida

                </span>

                <div class="small text-muted">

                    Quilometragem prevista atingida ou ultrapassada.

                </div>

            </div>

            <div class="col-sm-6 col-lg-3">

                <span class="badge bg-secondary mb-2">

                    Sem prazo

                </span>

                <div class="small text-muted">

                    Sem previsão de próxima revisão.

                </div>

            </div>

        </div>

        <hr>

        <div class="small text-muted">

            Os indicadores são calculados individualmente para
            cada manutenção, considerando a quilometragem atual
            do veículo.

        </div>

    </div>

</div>


<?php require_once __DIR__ . "/includes/footer.php"; ?>