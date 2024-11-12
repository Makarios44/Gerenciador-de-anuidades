<?php
include('database.php');  // Incluindo o arquivo de conexão

// Função para cadastrar associados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_associado'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cpf = $_POST['cpf'];
    $data_filiacao = $_POST['data_filiacao'];

    try {
        $pdo->beginTransaction(); // Inicia a transação
        // Cadastro do associado
        $sql = "INSERT INTO Associados (nome, email, cpf, data_filiacao) VALUES (:nome, :email, :cpf, :data_filiacao)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome, ':email' => $email, ':cpf' => $cpf, ':data_filiacao' => $data_filiacao]);
        
        $associado_id = $pdo->lastInsertId();

        // Associa o novo associado a todas as anuidades existentes
        $sql = "INSERT INTO Pagamentos (associado_id, anuidade_id) SELECT :associado_id, id FROM Anuidades";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':associado_id' => $associado_id]);

        $pdo->commit();
        echo "Novo associado cadastrado com sucesso!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Erro ao cadastrar associado: " . $e->getMessage();
    }
}

// Função para cadastrar anuidades e associá-las automaticamente a todos os associados
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar_anuidade'])) {
    $ano = $_POST['ano'];
    $valor = $_POST['valor'];

    try {
        $pdo->beginTransaction(); // Inicia a transação
        // Cadastro da anuidade
        $sql = "INSERT INTO Anuidades (ano, valor) VALUES (:ano, :valor)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ano' => $ano, ':valor' => $valor]);

        $anuidade_id = $pdo->lastInsertId();

        // Associa a nova anuidade a todos os associados
        $sql = "INSERT INTO Pagamentos (associado_id, anuidade_id) SELECT id, :anuidade_id FROM Associados";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':anuidade_id' => $anuidade_id]);

        $pdo->commit();
        echo "Nova anuidade cadastrada e associada a todos os associados!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Erro ao cadastrar anuidade: " . $e->getMessage();
    }
}

// Função para calcular anuidades devidas
// Função para calcular anuidades devidas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calcular_anuidades'])) {
    $nome = $_POST['nome'];

    try {
        // Buscar o associado pelo nome
        $sql = "SELECT * FROM Associados WHERE nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome]);

        if ($stmt->rowCount() > 0) {
            $associado = $stmt->fetch(PDO::FETCH_ASSOC);
            $data_filiacao = new DateTime($associado['data_filiacao']);
            $hoje = new DateTime();
            $anos_filiacao = $hoje->diff($data_filiacao)->y;

            // Selecionar todas as anuidades devidas desde o ano de filiação
            $sql_anuidades = "SELECT * FROM Anuidades WHERE ano >= :ano_filiacao";
            $stmt_anuidades = $pdo->prepare($sql_anuidades);
            $stmt_anuidades->execute([':ano_filiacao' => $data_filiacao->format('Y')]);

            $total_devido = 0;
            while ($anuidade = $stmt_anuidades->fetch(PDO::FETCH_ASSOC)) {
                $total_devido += $anuidade['valor'];
            }

            // Exibir o total devido apenas uma vez
            echo "Total devido por $nome: R$" . number_format($total_devido, 2, ',', '.');
        } else {
            echo "Associado não encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}


// Função para registrar pagamento de uma anuidade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pagar_anuidade'])) {
    $nome = $_POST['nome'];
    $ano = $_POST['ano'];

    try {
        // Buscar o associado pelo nome
        $sql = "SELECT id FROM Associados WHERE nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome]);

        if ($stmt->rowCount() > 0) {
            $associado = $stmt->fetch(PDO::FETCH_ASSOC);
            $associado_id = $associado['id'];

            // Marcar a anuidade como paga
            $sql = "UPDATE Pagamentos 
                    SET pago = TRUE, data_pagamento = NOW() 
                    WHERE associado_id = :associado_id AND anuidade_id = (SELECT id FROM Anuidades WHERE ano = :ano)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':associado_id' => $associado_id, ':ano' => $ano]);

            if ($stmt->rowCount() > 0) {
                echo "Anuidade de $ano para $nome paga com sucesso!";
            } else {
                echo "Anuidade não encontrada ou já paga.";
            }
        } else {
            echo "Associado não encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Função para listar associados em dia e em atraso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['listar_status'])) {
    try {
        $sql = "SELECT a.nome, a.cpf, 
                CASE WHEN bool_and(p.pago) THEN 'Em Dia' ELSE 'Em Atraso' END AS status
                FROM Associados a
                JOIN Pagamentos p ON a.id = p.associado_id
                GROUP BY a.id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<h3>Relatório de Associados</h3><table border='1'><tr><th>Nome</th><th>CPF</th><th>Status</th></tr>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>" . $row['nome'] . "</td><td>" . $row['cpf'] . "</td><td>" . $row['status'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum associado encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}
// Função para registrar pagamento de uma anuidade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pagar_anuidade'])) {
    $nome = $_POST['nome'];
    $ano = $_POST['ano'];

    try {
        // Buscar o associado pelo nome
        $sql = "SELECT id FROM Associados WHERE nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome]);

        if ($stmt->rowCount() > 0) {
            $associado = $stmt->fetch(PDO::FETCH_ASSOC);
            $associado_id = $associado['id'];

            // Buscar o ID da anuidade usando o ano informado
            $sql = "SELECT id FROM Anuidades WHERE ano = :ano";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ano' => $ano]);

            if ($stmt->rowCount() > 0) {
                $anuidade = $stmt->fetch(PDO::FETCH_ASSOC);
                $anuidade_id = $anuidade['id'];

                // Marcar a anuidade como paga
                $sql = "UPDATE Pagamentos 
                        SET pago = TRUE, data_pagamento = NOW() 
                        WHERE associado_id = :associado_id AND anuidade_id = :anuidade_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':associado_id' => $associado_id, ':anuidade_id' => $anuidade_id]);

                if ($stmt->rowCount() > 0) {
                    echo "Anuidade de $ano para $nome paga com sucesso!";
                } else {
                    echo "Anuidade não encontrada ou já paga.";
                }
            } else {
                echo "Anuidade do ano $ano não encontrada!";
            }
        } else {
            echo "Associado não encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}
// Função para calcular anuidades devidas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calcular_anuidades'])) {
    $nome = $_POST['nome'];

    try {
        // Buscar o associado pelo nome
        $sql = "SELECT * FROM Associados WHERE nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome]);

        if ($stmt->rowCount() > 0) {
            $associado = $stmt->fetch(PDO::FETCH_ASSOC);
            $data_filiacao = new DateTime($associado['data_filiacao']);
            $hoje = new DateTime();
            $anos_filiacao = $hoje->diff($data_filiacao)->y;

            // Selecionar todas as anuidades devidas desde o ano de filiação
            $sql_anuidades = "SELECT * FROM Anuidades WHERE ano >= :ano_filiacao";
            $stmt_anuidades = $pdo->prepare($sql_anuidades);
            $stmt_anuidades->execute([':ano_filiacao' => $data_filiacao->format('Y')]);

            $total_devido = 0;
            while ($anuidade = $stmt_anuidades->fetch(PDO::FETCH_ASSOC)) {
                $total_devido += $anuidade['valor'];
            }

            echo "Total devido por $nome: R$" . number_format($total_devido, 2, ',', '.');
        } else {
            echo "Associado não encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}
// Função para calcular anuidades devidas, ano a ano
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calcular_anuidades'])) {
    $nome = $_POST['nome'];

    try {
        // Buscar o associado pelo nome
        $sql = "SELECT * FROM Associados WHERE nome = :nome";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nome' => $nome]);

        if ($stmt->rowCount() > 0) {
            $associado = $stmt->fetch(PDO::FETCH_ASSOC);
            $data_filiacao = new DateTime($associado['data_filiacao']);
            $hoje = new DateTime();
            $anos_filiacao = $hoje->diff($data_filiacao)->y;

            // Selecionar todas as anuidades devidas desde o ano de filiação
            $sql_anuidades = "SELECT * FROM Anuidades WHERE ano >= :ano_filiacao ORDER BY ano";
            $stmt_anuidades = $pdo->prepare($sql_anuidades);
            $stmt_anuidades->execute([':ano_filiacao' => $data_filiacao->format('Y')]);

            // Exibir as anuidades devidas
            $total_devido = 0;
            echo "<h3>Resumo de Anuidades de $nome</h3>";
            echo "<table border='1'><tr><th>Ano</th><th>Valor</th><th>Status</th></tr>";

            while ($anuidade = $stmt_anuidades->fetch(PDO::FETCH_ASSOC)) {
                $ano_anuidade = $anuidade['ano'];
                $valor_anuidade = $anuidade['valor'];

                // Verificar se o pagamento foi feito para esse ano
                $sql_pagamento = "SELECT * FROM Pagamentos WHERE associado_id = :associado_id AND anuidade_id = :anuidade_id";
                $stmt_pagamento = $pdo->prepare($sql_pagamento);
                $stmt_pagamento->execute([
                    ':associado_id' => $associado['id'],
                    ':anuidade_id' => $anuidade['id']
                ]);

                $status_pagamento = 'Não Pago'; // Default é não pago
                if ($stmt_pagamento->rowCount() > 0) {
                    $pagamento = $stmt_pagamento->fetch(PDO::FETCH_ASSOC);
                    if ($pagamento['pago'] == 1) {
                        $status_pagamento = 'Pago';
                    }
                }

                // Exibir linha da anuidade
                echo "<tr>
                        <td>$ano_anuidade</td>
                        <td>R$ " . number_format($valor_anuidade, 2, ',', '.') . "</td>
                        <td>$status_pagamento</td>
                      </tr>";

                // Acumulando o total devido
                if ($status_pagamento == 'Não Pago') {
                    $total_devido += $valor_anuidade;
                }
            }

            // Exibir total devido
            echo "</table>";
            echo "<br><strong>Total devido: R$ " . number_format($total_devido, 2, ',', '.') . "</strong>";
        } else {
            echo "Associado não encontrado!";
        }
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Anuidades</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <div class="tabs">
        <!-- Botões de navegação das abas -->
        <button class="tab-button active" onclick="showTab(event, 'cadastro_associado')">Cadastro Associado</button>
        <button class="tab-button" onclick="showTab(event, 'cadastro_anuidade')">Cadastro Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'calculo_anuidades')">Calcular Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'pagar_anuidade')">Pagar Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'listar_associados')">Listar Associados</button>
    </div>

    <div id="calculo_anuidades" class="tab-content">
    <h2>Cálculo de Anuidades Devidas</h2>
    <form method="POST">
        <label for="nome">Nome do Associado:</label><br>
        <input type="text" id="nome" name="nome" required><br><br>
        <input type="submit" name="calcular_anuidades" value="Calcular Total Devido">
    </form>
    </div>




    <!-- Função Cadastro Associado -->
    <div id="cadastro_associado" class="tab-content">
        <h2>Cadastro de Associados</h2>
        <form method="POST">
            <label for="nome">Nome:</label><br>
            <input type="text" id="nome" name="nome" required><br>
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required><br>
            <label for="cpf">CPF:</label><br>
            <input type="text" id="cpf" name="cpf" required><br>
            <label for="data_filiacao">Data de Filição:</label><br>
            <input type="date" id="data_filiacao" name="data_filiacao" required><br><br>
            <input type="submit" name="cadastrar_associado" value="Cadastrar Associado">
        </form>
    </div>

    <!-- Função Cadastro Anuidade -->
    <div id="cadastro_anuidade" class="tab-content">
        <h2>Cadastro de Anuidades</h2>
        <form method="POST">
            <label for="ano">Ano:</label><br>
            <input type="number" id="ano" name="ano" required><br>
            <label for="valor">Valor:</label><br>
            <input type="text" id="valor" name="valor" required><br><br>
            <input type="submit" name="cadastrar_anuidade" value="Cadastrar Anuidade">
        </form>
    </div>

    <!-- Função Cálculo de Anuidades -->
    <div id="calculo_anuidades" class="tab-content">
        <h2>Cálculo de Anuidades Devidas</h2>
        <form method="POST">
            <label for="cpf">CPF do Associado:</label><br>
            <input type="text" id="cpf" name="cpf" required><br><br>
            <input type="submit" name="calcular_anuidades" value="Calcular Total Devido">
        </form>
    </div>

    <!-- Função Pagamento de Anuidade -->
    <div id="pagar_anuidade" class="tab-content">
        <h2>Pagamento de Anuidade</h2>
        <form method="POST">
            <label for="id_anuidade">ID da Anuidade:</label><br>
            <input type="number" id="id_anuidade" name="id_anuidade" required><br><br>
            <input type="submit" name="pagar_anuidade" value="Pagar Anuidade">
        </form>
    </div>

    <!-- Função Listar Associados -->
    <div id="listar_associados" class="tab-content">
        <h2>Listar Associados</h2>
        <form method="POST">
            <input type="submit" name="listar_status" value="Listar Status de Associados">
        </form>
    </div>

    <form method="post">
    <input type="hidden" name="pagar_anuidade" value="1">
    <label>Nome do Associado:</label>
    <input type="text" name="nome" required>
    <label>Ano da Anuidade:</label>
    <input type="number" name="ano" required>
    <button type="submit">Pagar Anuidade</button>
</form>



    <script src="script.js"></script> 
</body>
</html>
