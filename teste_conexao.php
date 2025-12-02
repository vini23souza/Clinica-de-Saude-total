<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "clinica_profissional";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    echo "✅ Conectado ao banco com sucesso!";
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage();
}