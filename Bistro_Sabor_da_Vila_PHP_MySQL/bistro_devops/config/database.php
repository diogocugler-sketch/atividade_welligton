<?php
declare(strict_types=1);

$dbHost = getenv('BISTRO_DB_HOST') ?: 'localhost';
$dbName = getenv('BISTRO_DB_NAME') ?: 'bistro_devops';
$dbUser = getenv('BISTRO_DB_USER') ?: 'root';
$dbPass = getenv('BISTRO_DB_PASS') ?: '';

if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $dbName)) {
    throw new RuntimeException('O nome do banco deve conter apenas letras, números e sublinhados.');
}

$dsn = "mysql:host={$dbHost};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    try {
        $pdo = new PDO($dsn . ";dbname={$dbName}", $dbUser, $dbPass, $options);
    } catch (PDOException $exception) {
        if ((int)($exception->errorInfo[1] ?? 0) !== 1049) {
            throw $exception;
        }

        $server = new PDO($dsn, $dbUser, $dbPass, $options);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO($dsn . ";dbname={$dbName}", $dbUser, $dbPass, $options);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        nome VARCHAR(120) NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        categoria VARCHAR(60) NOT NULL DEFAULT 'Outros',
        preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_produtos_ativo_categoria (ativo,categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comandas (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        numero INT UNSIGNED DEFAULT NULL,
        data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        observacao TEXT DEFAULT NULL,
        status ENUM('aberta','entregue') NOT NULL DEFAULT 'aberta',
        data_entrega DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_comandas_numero (numero),
        INDEX idx_comandas_status_data (status,data_abertura)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS itens_comanda (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        comanda_id INT UNSIGNED NOT NULL,
        produto_id INT UNSIGNED DEFAULT NULL,
        nome_produto VARCHAR(120) NOT NULL,
        quantidade INT UNSIGNED NOT NULL,
        preco_unitario DECIMAL(10,2) NOT NULL,
        observacao_item VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        CONSTRAINT fk_itens_comanda_comanda FOREIGN KEY (comanda_id) REFERENCES comandas(id) ON DELETE CASCADE,
        CONSTRAINT fk_itens_comanda_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL,
        INDEX idx_itens_comanda (comanda_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ((int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn() === 0) {
        $produtosIniciais = [
            ['Prato feito de frango', 'Frango grelhado, arroz, feijão e salada', 'Pratos feitos', 24.90],
            ['Prato feito de carne', 'Carne acebolada, arroz, feijão e salada', 'Pratos feitos', 28.90],
            ['Prato feito vegetariano', 'Legumes salteados, arroz, feijão e salada', 'Pratos feitos', 22.90],
            ['X-Burger', 'Hambúrguer, queijo, alface e tomate', 'Lanches', 19.90],
            ['X-Salada', 'Hambúrguer, queijo, alface, tomate e maionese', 'Lanches', 22.50],
            ['Porção de batata frita', 'Porção média para compartilhar', 'Porções', 18.00],
            ['Suco natural', 'Copo de 400 ml', 'Bebidas', 8.00],
            ['Refrigerante lata', 'Lata de 350 ml', 'Bebidas', 6.00],
            ['Água mineral', 'Garrafa de 500 ml', 'Bebidas', 4.00],
            ['Pudim de leite', 'Fatia de pudim caseiro', 'Sobremesas', 9.50],
        ];
        $statement = $pdo->prepare(
            'INSERT INTO produtos (nome,descricao,categoria,preco,ativo) VALUES (?,?,?,?,1)'
        );
        foreach ($produtosIniciais as $produto) {
            $statement->execute($produto);
        }
    }
} catch (PDOException $exception) {
    error_log('Erro de inicialização do banco: ' . $exception->getMessage());
    http_response_code(500);
    exit(
        'Não foi possível preparar o banco de dados. Confira se o MySQL está iniciado e se ' .
        'config/database.php ou as variáveis BISTRO_DB_HOST, BISTRO_DB_NAME, BISTRO_DB_USER ' .
        'e BISTRO_DB_PASS contêm os dados corretos e têm permissão para criar o banco e as tabelas.'
    );
}
