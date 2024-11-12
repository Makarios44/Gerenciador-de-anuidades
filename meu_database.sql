-- Tabela de Associados
CREATE TABLE Associados (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    data_filiacao DATE NOT NULL
);

-- Tabela de Anuidades
CREATE TABLE Anuidades (
    id SERIAL PRIMARY KEY,
    ano INT NOT NULL,
    valor DECIMAL(10, 2) NOT NULL
);

-- Tabela de Pagamentos
CREATE TABLE Pagamentos (
    id SERIAL PRIMARY KEY,
    associado_id INT NOT NULL REFERENCES Associados(id),
    anuidade_id INT NOT NULL REFERENCES Anuidades(id),
    pago BOOLEAN DEFAULT FALSE,
    data_pagamento DATE,
    UNIQUE(associado_id, anuidade_id) -- Garante que cada associado tenha apenas um registro para cada anuidade
);
