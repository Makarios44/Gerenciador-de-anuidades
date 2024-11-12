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
        $pdo->beginTransaction(); 
        // Cadastro 
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

// Função para listar anuidades por ano e o status pago ou em atras
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['listar_anuidades_status'])) {
    try {
        // SQL para buscar todas as anuidades de todos os associados e seu status pago ou não
        $sql = "SELECT a.nome, a.cpf, an.ano, an.valor, 
                CASE WHEN p.pago THEN 'Paga' ELSE 'Em Atraso' END AS status
                FROM Associados a
                JOIN Pagamentos p ON a.id = p.associado_id
                JOIN Anuidades an ON p.anuidade_id = an.id
                ORDER BY an.ano ASC, a.nome ASC";  // Ordena por ano e nome do associado

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<h3>Relatório de Anuidades por Ano e Status</h3>";
            echo "<table border='1'><tr><th>Nome</th><th>CPF</th><th>Ano</th><th>Valor</th><th>Status</th></tr>";
            
            // Exibe todos os dados
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['nome']) . "</td>
                        <td>" . htmlspecialchars($row['cpf']) . "</td>
                        <td>" . $row['ano'] . "</td>
                        <td>R$" . number_format($row['valor'], 2, ',', '.') . "</td>
                        <td>" . $row['status'] . "</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhuma anuidade encontrada!";
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
    <script>
        // Função para exibir as abas de conteúdo
        function showTab(evt, tabName) {
            var i, tabcontent, tabbuttons;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tabbuttons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
</head>
<body>
    <div class="tabs">

        <button class="tab-button active" onclick="showTab(event, 'cadastro_associado')">Cadastro Associado</button>
        <button class="tab-button" onclick="showTab(event, 'cadastro_anuidade')">Cadastro Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'calculo_anuidades')">Calcular Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'pagar_anuidade')">Pagar Anuidade</button>
        <button class="tab-button" onclick="showTab(event, 'relatorio_status')">Relatório de Status</button>
    </div>

    <div id="relatorio_status" class="tab-content">
    <h2>Relatório de Status de Anuidades</h2>
    <form method="POST">
        <input type="submit" name="listar_anuidades_status" value="Gerar Relatório de Anuidades">
    </form>
</div>



    <div id="cadastro_associado" class="tab-content" style="display:block;">
        <h2>Cadastrar Associado</h2>
        <form method="POST">
            <label for="nome">Nome:</label><input type="text" name="nome" required><br>
            <label for="email">E-mail:</label><input type="email" name="email" required><br>
            <label for="cpf">CPF:</label><input type="text" name="cpf" required><br>
            <label for="data_filiacao">Data de Filiação:</label><input type="date" name="data_filiacao" required><br>
            <input type="submit" name="cadastrar_associado" value="Cadastrar">
        </form>
    </div>

    <div id="cadastro_anuidade" class="tab-content">
        <h2>Cadastrar Anuidade</h2>
        <form method="POST">
            <label for="ano">Ano:</label><input type="number" name="ano" required><br>
            <label for="valor">Valor:</label><input type="text" name="valor" required><br>
            <input type="submit" name="cadastrar_anuidade" value="Cadastrar">
        </form>
    </div>

    <div id="calculo_anuidades" class="tab-content">
        <h2>Calcular Anuidade</h2>
        <form method="POST">
            <label for="nome">Nome do Associado:</label><input type="text" name="nome" required><br>
            <input type="submit" name="calcular_anuidades" value="Calcular">
        </form>
    </div>

    <div id="pagar_anuidade" class="tab-content">
        <h2>Pagar Anuidade</h2>
        <form method="POST">
            <label for="nome">Nome do Associado:</label><input type="text" name="nome" required><br>
            <label for="ano">Ano da Anuidade:</label><input type="number" name="ano" required><br>
            <input type="submit" name="pagar_anuidade" value="Pagar">
        </form>
    </div>

    
    </div>
</body>
</html>
