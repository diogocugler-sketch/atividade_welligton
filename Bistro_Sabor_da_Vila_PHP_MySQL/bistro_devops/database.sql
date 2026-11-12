-- Bistrô Sabor da Vila: sistema didático de comandas.
-- No InfinityFree, crie o banco pelo painel e selecione-o no phpMyAdmin.
-- Se necessário, remova CREATE DATABASE e USE antes de importar.
CREATE DATABASE IF NOT EXISTS bistro_devops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bistro_devops;

CREATE TABLE IF NOT EXISTS produtos (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 nome VARCHAR(120) NOT NULL,
 descricao VARCHAR(255) DEFAULT NULL,
 categoria VARCHAR(60) NOT NULL DEFAULT 'Outros',
 preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id), INDEX idx_produtos_ativo_categoria (ativo,categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comandas (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 numero INT UNSIGNED DEFAULT NULL,
 data_abertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 observacao TEXT DEFAULT NULL,
 status ENUM('aberta','entregue') NOT NULL DEFAULT 'aberta',
 data_entrega DATETIME DEFAULT NULL,
 PRIMARY KEY (id), UNIQUE KEY uq_comandas_numero (numero),
 INDEX idx_comandas_status_data (status,data_abertura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens_comanda (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO produtos (nome,descricao,categoria,preco,ativo)
SELECT iniciais.nome,iniciais.descricao,iniciais.categoria,iniciais.preco,1
FROM (
 SELECT 'Prato feito de frango' nome,'Frango grelhado, arroz, feijão e salada' descricao,'Pratos feitos' categoria,24.90 preco
 UNION ALL SELECT 'Prato feito de carne','Carne acebolada, arroz, feijão e salada','Pratos feitos',28.90
 UNION ALL SELECT 'Prato feito vegetariano','Legumes salteados, arroz, feijão e salada','Pratos feitos',22.90
 UNION ALL SELECT 'X-Burger','Hambúrguer, queijo, alface e tomate','Lanches',19.90
 UNION ALL SELECT 'X-Salada','Hambúrguer, queijo, alface, tomate e maionese','Lanches',22.50
 UNION ALL SELECT 'Porção de batata frita','Porção média para compartilhar','Porções',18.00
 UNION ALL SELECT 'Suco natural','Copo de 400 ml','Bebidas',8.00
 UNION ALL SELECT 'Refrigerante lata','Lata de 350 ml','Bebidas',6.00
 UNION ALL SELECT 'Água mineral','Garrafa de 500 ml','Bebidas',4.00
 UNION ALL SELECT 'Pudim de leite','Fatia de pudim caseiro','Sobremesas',9.50
) AS iniciais
WHERE NOT EXISTS (SELECT 1 FROM produtos LIMIT 1);
