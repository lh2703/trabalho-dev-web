<?php

$host = "localhost";
$dbname = "garagem_digital";
$user = "root";
$pass = "";
$port = 3306;

// Carregar credenciais específicas do ambiente,
// caso o arquivo exista.

$arquivoCredenciais = __DIR__ . "/credenciais.php";

if (is_file($arquivoCredenciais)) {
    require $arquivoCredenciais;
}

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log("Erro de conexão: " . $e->getMessage());

    http_response_code(500);

    exit("Não foi possível conectar ao banco de dados.");

}