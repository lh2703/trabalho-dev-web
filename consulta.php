<?php

require_once __DIR__ . "/includes/funcoes.php";
require_once __DIR__ . "/config/conexao.php";

$veiculoFiltro = idValido($_GET["veiculo_id"] ?? 0);

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

$veiculos = $pdo->query("
    SELECT id, modelo, placa
    FROM veiculos
    ORDER BY modelo, id
")->fetchAll();

$totalInicial = array_sum(
    array_column($manutencoes, "valor")
);

require_once __DIR__ . "/includes/header.php";

?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

    <h2 class="fw-bold mb-0">

        Consulta de Manutenções

    </h2>

    <div class="bg-white px-3 py-2 rounded shadow-sm">

        <span class="text-muted">

            Total exibido:

        </span>

        <strong class="text-success fs-5" id="totalFiltrado">

            <?= moeda($totalInicial) ?>

        </strong>

    </div>

</div>

<div class="card shadow-sm mb-4">

    <div class="card-body">

        <div class="row g-3">

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

                        <button class="btn btn-primary mt-2">

                            Filtrar

                        </button>

                    </noscript>

                </form>

            </div>

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

        <div class="d-flex justify-content-between align-items-center mt-3">

            <small class="text-muted" id="quantidadeFiltrada">

                <?= count($manutencoes) ?> registro(s)

            </small>

            <a href="consulta.php" class="btn btn-sm btn-outline-secondary">

                Limpar filtros

            </a>

        </div>

    </div>

</div>

<div class="card shadow-sm">

    <div class="card-header bg-white fw-bold">

        Histórico de Manutenções

    </div>

    <div class="card-body table-responsive">

        <table
            class="table table-hover align-middle mb-0"
            id="tabelaManutencoes">

            <thead>

                <tr>
                    <th>Veículo</th>
                    <th>Serviço</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>KM Serviço</th>
                    <th>Próxima Revisão</th>
                    <th>Status</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($manutencoes as $m): ?>

                    <?php

                    $status = statusManutencao(
                        (int)$m["km_atual"],
                        (int)$m["proxima_km"]
                    );

                    ?>

                    <tr
                        data-valor="<?= h($m["valor"]) ?>"
                        data-categoria="<?= h($m["categoria"]) ?>">

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

                            <?php if (!empty($m["observacao"])): ?>

                                <div class="small text-muted observacao">

                                    <?= h($m["observacao"]) ?>

                                </div>

                            <?php endif; ?>

                        </td>

                        <td>

                            <span class="badge bg-secondary">

                                <?= h($m["categoria"]) ?>

                            </span>

                        </td>

                        <td>

                            <?= dataBrasileira($m["data"]) ?>

                        </td>

                        <td>

                            <?= quilometragem($m["km_servico"]) ?>

                        </td>

                        <td>

                            <?= (int)$m["proxima_km"] > 0
                                ? quilometragem($m["proxima_km"])
                                : "-" ?>

                        </td>

                        <td>

                            <span class="badge bg-<?= h($status["classe"]) ?>">

                                <?= h($status["texto"]) ?>

                            </span>

                        </td>

                        <td>

                            <?= moeda($m["valor"]) ?>

                        </td>

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

                <tr
                    id="semResultados"
                    <?= !empty($manutencoes) ? "hidden" : "" ?>>

                    <td colspan="9" class="text-center text-muted py-4">

                        Nenhuma manutenção encontrada.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

<div class="small text-muted mt-3">

    Os indicadores de revisão são calculados individualmente
    para cada registro, considerando a quilometragem atual
    do veículo.

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>