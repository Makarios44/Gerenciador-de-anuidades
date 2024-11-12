<?php

$host = "localhost";           
$port = "5432";                
$dbname = "sistema_anuidades"; 
$user = "postgres";            
$password = "131329";      




try {
    // Estabelecendo a conexão com o banco de dados
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    // Configurando o modo de erro para exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    // Captura e exibe os erros de conexão
    echo "Erro de conexão: " . $e->getMessage();
}
?>
