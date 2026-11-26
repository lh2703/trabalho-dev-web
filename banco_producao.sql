-- ==========================================
-- GARAGEM DIGITAL
-- Ambiente de produção
-- ==========================================
SET NAMES utf8mb4;


-- TABELA DE VEÍCULOS

CREATE TABLE IF NOT EXISTS veiculos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    modelo VARCHAR(100) NOT NULL,

    ano INT NOT NULL,

    placa VARCHAR(10) NOT NULL UNIQUE,

    km_atual INT NOT NULL DEFAULT 0,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


-- TABELA DE MANUTENÇÕES

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

        FOREIGN KEY (veiculo_id)
        REFERENCES veiculos(id)

        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


-- DADOS DEMONSTRATIVOS

START TRANSACTION;


INSERT INTO veiculos (modelo, ano, placa, km_atual)

SELECT
    'Hyundai HB20 1.0 Sense',
    2022,
    'ABC1D23',
    48500

WHERE NOT EXISTS (
    SELECT 1
    FROM veiculos
    WHERE placa = 'ABC1D23'
);


INSERT INTO veiculos (modelo, ano, placa, km_atual)

SELECT
    'Honda Civic Touring',
    2020,
    'XYZ9K87',
    62300

WHERE NOT EXISTS (
    SELECT 1
    FROM veiculos
    WHERE placa = 'XYZ9K87'
);


-- MANUTENÇÃO 1

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

SELECT
    v.id,
    'Troca de óleo 5W30 e filtro',
    'Revisão',
    '2026-09-15',
    45000,
    55000,
    340.00,
    'Óleo sintético + filtro original'

FROM veiculos v

WHERE v.placa = 'ABC1D23'

AND NOT EXISTS (

    SELECT 1
    FROM manutencoes m
    WHERE m.veiculo_id = v.id
    AND m.servico = 'Troca de óleo 5W30 e filtro'
    AND m.data = '2026-09-15'
    AND m.km_servico = 45000

);


-- MANUTENÇÃO 2

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

SELECT
    v.id,
    'Pastilhas de freio dianteiras',
    'Freios',
    '2026-06-10',
    40000,
    48000,
    480.00,
    'Troca preventiva realizada'

FROM veiculos v

WHERE v.placa = 'ABC1D23'

AND NOT EXISTS (

    SELECT 1
    FROM manutencoes m
    WHERE m.veiculo_id = v.id
    AND m.servico = 'Pastilhas de freio dianteiras'
    AND m.data = '2026-06-10'
    AND m.km_servico = 40000

);


-- MANUTENÇÃO 3

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

SELECT
    v.id,
    'Upgrade molas esportivas + alinhamento',
    'Upgrade',
    '2026-10-02',
    61000,
    75000,
    1850.00,
    'Geometria completa realizada'

FROM veiculos v

WHERE v.placa = 'XYZ9K87'

AND NOT EXISTS (

    SELECT 1
    FROM manutencoes m
    WHERE m.veiculo_id = v.id
    AND m.servico = 'Upgrade molas esportivas + alinhamento'
    AND m.data = '2026-10-02'
    AND m.km_servico = 61000

);


-- GARANTIR CONSISTÊNCIA DA QUILOMETRAGEM

UPDATE veiculos v

INNER JOIN (

    SELECT
        veiculo_id,
        MAX(km_servico) AS maior_km

    FROM manutencoes

    GROUP BY veiculo_id

) m ON m.veiculo_id = v.id

SET v.km_atual = GREATEST(
    v.km_atual,
    m.maior_km
);


COMMIT;