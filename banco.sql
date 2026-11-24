CREATE DATABASE IF NOT EXISTS garagem_digital
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE garagem_digital;

CREATE TABLE IF NOT EXISTS veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(100) NOT NULL,
    ano INT NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    km_atual INT NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    servico VARCHAR(120) NOT NULL,
    categoria VARCHAR(50) NOT NULL DEFAULT 'Revisão',
    data DATE NOT NULL,
    km_servico INT NOT NULL,
    proxima_km INT NOT NULL DEFAULT 0,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacao TEXT,
    CONSTRAINT fk_manutencao_veiculo
        FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

INSERT INTO veiculos (modelo, ano, placa, km_atual) VALUES
('Hyundai HB20 1.0 Sense', 2022, 'ABC1D23', 48500),
('Honda Civic Touring', 2020, 'XYZ9K87', 62300);

INSERT INTO manutencoes (veiculo_id, servico, categoria, data, km_servico, proxima_km, valor, observacao) VALUES
(1, 'Troca de óleo 5W30 e filtro', 'Revisão', '2026-09-15', 45000, 55000, 340.00, 'Óleo sintético + filtro original'),
(1, 'Pastilhas de freio dianteiras', 'Freios', '2026-06-10', 40000, 48000, 480.00, 'Troca preventiva realizada'),
(2, 'Upgrade molas esportivas + alinhamento', 'Upgrade', '2026-10-02', 61000, 75000, 1850.00, 'Geometria completa realizada');