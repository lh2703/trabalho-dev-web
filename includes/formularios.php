<?php

function camposVeiculo(array $dados = []): void
{
    ?>

    <div class="row g-3">

        <div class="col-12">

            <label for="modelo" class="form-label">
                Modelo
            </label>

            <input
                type="text"
                name="modelo"
                id="modelo"
                class="form-control"
                maxlength="100"
                placeholder="Ex.: Honda Civic Touring"
                value="<?= h($dados["modelo"] ?? "") ?>"
                required>

        </div>

        <div class="col-md-6">

            <label for="ano" class="form-label">
                Ano
            </label>

            <input
                type="number"
                name="ano"
                id="ano"
                class="form-control"
                min="1900"
                max="<?= (int)date("Y") + 1 ?>"
                value="<?= h($dados["ano"] ?? "") ?>"
                required>

        </div>

        <div class="col-md-6">

            <label for="placa" class="form-label">
                Placa
            </label>

            <input
                type="text"
                name="placa"
                id="placa"
                class="form-control text-uppercase"
                maxlength="10"
                placeholder="ABC1D23"
                value="<?= h($dados["placa"] ?? "") ?>"
                required>

        </div>

        <div class="col-12">

            <label for="km_atual" class="form-label">
                Quilometragem atual
            </label>

            <input
                type="number"
                name="km_atual"
                id="km_atual"
                class="form-control"
                min="0"
                max="2147483647"
                step="1"
                value="<?= h($dados["km_atual"] ?? 0) ?>"
                required>

        </div>

    </div>

    <?php
}


function camposManutencao(
    array $veiculos,
    array $dados = []
): void {

    ?>

    <div class="row g-3">

        <div class="col-12">

            <label for="veiculo_id" class="form-label">
                Veículo
            </label>

            <select
                name="veiculo_id"
                id="veiculo_id"
                class="form-select"
                required>

                <option value="">
                    Selecione um veículo...
                </option>

                <?php foreach ($veiculos as $v): ?>

                    <option
                        value="<?= (int)$v["id"] ?>"
                        <?= (int)($dados["veiculo_id"] ?? 0)
                            === (int)$v["id"]
                            ? "selected"
                            : "" ?>>

                        <?= h($v["modelo"]) ?>
                        (<?= h($v["placa"]) ?>)

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-7">

            <label for="servico" class="form-label">
                Serviço / Peça
            </label>

            <input
                type="text"
                name="servico"
                id="servico"
                class="form-control"
                maxlength="120"
                placeholder="Ex.: Troca de óleo"
                value="<?= h($dados["servico"] ?? "") ?>"
                required>

        </div>

        <div class="col-md-5">

            <label for="categoria" class="form-label">
                Categoria
            </label>

            <select
                name="categoria"
                id="categoria"
                class="form-select"
                required>

                <?php foreach (categorias() as $categoria): ?>

                    <option
                        value="<?= h($categoria) ?>"
                        <?= ($dados["categoria"] ?? "Revisão")
                            === $categoria
                            ? "selected"
                            : "" ?>>

                        <?= h($categoria) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-6">

            <label for="data" class="form-label">
                Data do serviço
            </label>

            <input
                type="date"
                name="data"
                id="data"
                class="form-control"
                max="<?= date("Y-m-d") ?>"
                value="<?= h($dados["data"] ?? date("Y-m-d")) ?>"
                required>

        </div>

        <div class="col-md-6">

            <label for="valor" class="form-label">
                Valor (R$)
            </label>

            <input
                type="number"
                name="valor"
                id="valor"
                class="form-control"
                min="0"
                max="99999999.99"
                step="0.01"
                value="<?= h($dados["valor"] ?? "0.00") ?>"
                required>

        </div>

        <div class="col-md-6">

            <label for="km_servico" class="form-label">
                KM no serviço
            </label>

            <input
                type="number"
                name="km_servico"
                id="km_servico"
                class="form-control"
                min="0"
                max="2147483647"
                step="1"
                value="<?= h($dados["km_servico"] ?? "") ?>"
                required>

        </div>

        <div class="col-md-6">

            <label for="proxima_km" class="form-label">
                Próxima revisão (KM)
            </label>

            <input
                type="number"
                name="proxima_km"
                id="proxima_km"
                class="form-control"
                min="0"
                max="2147483647"
                step="1"
                value="<?= h($dados["proxima_km"] ?? 0) ?>"
                required>

            <small class="text-muted">
                Informe 0 quando não houver previsão.
            </small>

        </div>

        <div class="col-12">

            <label for="observacao" class="form-label">
                Observações
            </label>

            <textarea
                name="observacao"
                id="observacao"
                class="form-control"
                maxlength="5000"
                rows="3"><?= h($dados["observacao"] ?? "") ?></textarea>

        </div>

    </div>

    <?php
}