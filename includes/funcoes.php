<?php

// CONFIGURAÇÃO DA SESSÃO

date_default_timezone_set("America/Sao_Paulo");

if (session_status() === PHP_SESSION_NONE) {

    $https = !empty($_SERVER["HTTPS"])
        && strtolower($_SERVER["HTTPS"]) !== "off";

    session_start([
        "use_strict_mode" => true,
        "use_only_cookies" => true,
        "cookie_httponly" => true,
        "cookie_secure" => $https,
        "cookie_samesite" => "Lax"
    ]);

}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("X-Content-Type-Options: nosniff");


// PROTEÇÃO CONTRA XSS

function h($valor): string
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}


// CATEGORIAS PERMITIDAS

function categorias(): array
{
    return [
        "Revisão",
        "Freios",
        "Suspensão",
        "Motor",
        "Upgrade",
        "Outros"
    ];
}


// PROTEÇÃO CSRF

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . h(csrfToken()) . '">';
}

function validarCsrf(): void
{
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        throw new RuntimeException(
            "Requisição inválida. Atualize a página e tente novamente."
        );
    }
}


// MENSAGENS FLASH

function definirMensagem(
    string $mensagem,
    string $tipo = "success"
): void {

    $_SESSION["flash"] = [
        "mensagem" => $mensagem,
        "tipo" => $tipo
    ];
}

function obterMensagem(): ?array
{
    $mensagem = $_SESSION["flash"] ?? null;

    unset($_SESSION["flash"]);

    return $mensagem;
}

function exibirMensagem(): void
{
    $flash = obterMensagem();

    if (!$flash) {
        return;
    }

    $tipos = ["success", "danger", "warning", "info"];

    $tipo = in_array($flash["tipo"], $tipos, true)
        ? $flash["tipo"]
        : "info";

    ?>

    <div
        class="alert alert-<?= h($tipo) ?> alert-dismissible fade show"
        role="alert">

        <?= h($flash["mensagem"]) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Fechar">
        </button>

    </div>

    <?php
}


// REDIRECIONAMENTO POST/REDIRECT/GET

function redirecionar(string $pagina): void
{
    header("Location: " . $pagina, true, 303);
    exit;
}


// VALIDAR IDENTIFICADORES

function idValido($valor): int
{
    if (!is_string($valor) && !is_int($valor)) {
        return 0;
    }

    $id = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1,
                "max_range" => 2147483647
            ]
        ]
    );

    return $id === false ? 0 : $id;
}


// VALIDAR TEXTOS

function validarTexto(
    $valor,
    string $campo,
    int $limite,
    bool $obrigatorio = true
): string {

    if (!is_string($valor)) {
        throw new RuntimeException("$campo inválido.");
    }

    $valor = trim($valor);

    if ($obrigatorio && $valor === "") {
        throw new RuntimeException(
            "O campo $campo é obrigatório."
        );
    }

    if (!preg_match("//u", $valor)) {
        throw new RuntimeException(
            "O campo $campo contém caracteres inválidos."
        );
    }

    $tamanho = function_exists("mb_strlen")
        ? mb_strlen($valor, "UTF-8")
        : preg_match_all("/./us", $valor);

    if ($tamanho > $limite) {
        throw new RuntimeException(
            "O campo $campo deve ter até $limite caracteres."
        );
    }

    return $valor;
}


// VALIDAR NÚMEROS INTEIROS

function validarInteiro(
    $valor,
    string $campo,
    int $minimo = 0,
    int $maximo = 2147483647
): int {

    if (!is_string($valor) && !is_int($valor)) {
        throw new RuntimeException("$campo inválido.");
    }

    $numero = filter_var(
        $valor,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => $minimo,
                "max_range" => $maximo
            ]
        ]
    );

    if ($numero === false) {
        throw new RuntimeException(
            "Informe um valor válido para $campo."
        );
    }

    return $numero;
}


// VALIDAR PLACA

function validarPlaca($valor): string
{
    $placa = validarTexto($valor, "placa", 10);

    $placa = strtoupper($placa);

    $placa = str_replace(["-", " "], "", $placa);

    if (!preg_match("/^[A-Z0-9]{1,10}$/", $placa)) {
        throw new RuntimeException("Placa inválida.");
    }

    return $placa;
}


// VALIDAR DATA

function validarData($valor): string
{
    if (!is_string($valor)) {
        throw new RuntimeException("Data inválida.");
    }

    $data = DateTime::createFromFormat("!Y-m-d", $valor);

    if (!$data || $data->format("Y-m-d") !== $valor) {
        throw new RuntimeException("Informe uma data válida.");
    }

    if ($valor > date("Y-m-d")) {
        throw new RuntimeException(
            "A data do serviço não pode estar no futuro."
        );
    }

    return $valor;
}


// VALIDAR VALOR MONETÁRIO

function validarValor($valor): string
{
    if (!is_string($valor) && !is_int($valor)) {
        throw new RuntimeException("Valor financeiro inválido.");
    }

    $valor = str_replace(",", ".", trim((string)$valor));

    if (!preg_match("/^\d{1,8}(\.\d{1,2})?$/", $valor)) {
        throw new RuntimeException(
            "Informe um valor financeiro válido e não negativo."
        );
    }

    $partes = explode(".", $valor);

    $inteiro = $partes[0];
    $decimal = str_pad($partes[1] ?? "", 2, "0");

    return $inteiro . "." . $decimal;
}


// VALIDAR DADOS DO VEÍCULO

function dadosVeiculo(array $dados): array
{
    return [
        "modelo" => validarTexto(
            $dados["modelo"] ?? "",
            "modelo",
            100
        ),

        "ano" => validarInteiro(
            $dados["ano"] ?? "",
            "ano",
            1900,
            (int)date("Y") + 1
        ),

        "placa" => validarPlaca(
            $dados["placa"] ?? ""
        ),

        "km_atual" => validarInteiro(
            $dados["km_atual"] ?? "",
            "quilometragem atual"
        )
    ];
}


// VALIDAR DADOS DA MANUTENÇÃO

function dadosManutencao(array $dados): array
{
    $veiculoId = idValido($dados["veiculo_id"] ?? 0);

    if (!$veiculoId) {
        throw new RuntimeException("Selecione um veículo válido.");
    }

    $categoria = validarTexto(
        $dados["categoria"] ?? "",
        "categoria",
        50
    );

    if (!in_array($categoria, categorias(), true)) {
        throw new RuntimeException("Categoria inválida.");
    }

    $kmServico = validarInteiro(
        $dados["km_servico"] ?? "",
        "quilometragem do serviço"
    );

    $proximaKm = validarInteiro(
        $dados["proxima_km"] ?? "0",
        "quilometragem da próxima revisão"
    );

    if ($proximaKm > 0 && $proximaKm <= $kmServico) {
        throw new RuntimeException(
            "A próxima revisão deve ter quilometragem superior à do serviço."
        );
    }

    return [

        "veiculo_id" => $veiculoId,

        "servico" => validarTexto(
            $dados["servico"] ?? "",
            "serviço",
            120
        ),

        "categoria" => $categoria,

        "data" => validarData(
            $dados["data"] ?? ""
        ),

        "km_servico" => $kmServico,

        "proxima_km" => $proximaKm,

        "valor" => validarValor(
            $dados["valor"] ?? ""
        ),

        "observacao" => validarTexto(
            $dados["observacao"] ?? "",
            "observação",
            5000,
            false
        )

    ];
}


// BUSCAR VEÍCULO

function buscarVeiculo(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM veiculos
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $registro = $stmt->fetch();

    return $registro ?: null;
}


// BUSCAR MANUTENÇÃO

function buscarManutencao(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM manutencoes
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $registro = $stmt->fetch();

    return $registro ?: null;
}


// BLOQUEAR VEÍCULO DURANTE TRANSAÇÃO

function bloquearVeiculo(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM veiculos
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$id]);

    if (!$stmt->fetchColumn()) {
        throw new RuntimeException(
            "O veículo selecionado não existe."
        );
    }
}


// ATUALIZAR QUILOMETRAGEM AUTOMATICAMENTE

function atualizarQuilometragem(
    PDO $pdo,
    int $veiculoId,
    int $km
): void {

    $stmt = $pdo->prepare("
        UPDATE veiculos
        SET km_atual = GREATEST(km_atual, ?)
        WHERE id = ?
    ");

    $stmt->execute([$km, $veiculoId]);
}


// FORMATAR VALORES

function moeda($valor): string
{
    return "R$ " . number_format(
        (float)$valor,
        2,
        ",",
        "."
    );
}

function quilometragem($valor): string
{
    return number_format(
        (int)$valor,
        0,
        ",",
        "."
    ) . " km";
}

function dataBrasileira(string $data): string
{
    $objeto = DateTime::createFromFormat("!Y-m-d", $data);

    return $objeto
        ? $objeto->format("d/m/Y")
        : "-";
}


// CALCULAR STATUS DA MANUTENÇÃO

function statusManutencao(int $kmAtual, int $proximaKm): array
{
    if ($proximaKm <= 0) {
        return [
            "texto" => "Sem prazo",
            "classe" => "secondary"
        ];
    }

    $restante = $proximaKm - $kmAtual;

    if ($restante <= 0) {
        return [
            "texto" => "Vencida",
            "classe" => "danger"
        ];
    }

    if ($restante <= 1500) {
        return [
            "texto" => "Atenção",
            "classe" => "warning text-dark"
        ];
    }

    return [
        "texto" => "Em dia",
        "classe" => "success"
    ];
}


// TRATAR ERROS DE BANCO

function tratarErroBanco(
    PDOException $e,
    string $mensagemPadrao
): void {

    error_log($e->getMessage());

    $codigoMySQL = $e->errorInfo[1] ?? 0;

    if ((int)$codigoMySQL === 1062) {

        definirMensagem(
            "Já existe um veículo cadastrado com essa placa.",
            "warning"
        );

    } elseif ((int)$codigoMySQL === 1452) {

        definirMensagem(
            "O veículo informado não existe.",
            "warning"
        );

    } else {

        definirMensagem($mensagemPadrao, "danger");

    }
}