<?php
// config.php - VERSÃO CORRIGIDA

// ============ REMOVA OU COMENTE TODOS OS session_start() DAQUI ============
// session_start(); // ← COMENTE ou REMOVA ESTA LINHA!

// ============ CONFIGURAÇÕES DO BANCO DE DADOS ============
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinica_profissional');

// ============ CONEXÃO COM O BANCO DE DADOS ============
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Em desenvolvimento, mostra o erro
    die("ERRO: Não foi possível conectar ao banco de dados. " . $e->getMessage());
    
    // Em produção, use:
    // error_log("Erro de conexão: " . $e->getMessage());
    // die("Erro no sistema. Tente novamente mais tarde.");
}

// ============ TIMEZONE ============
date_default_timezone_set('America/Sao_Paulo');

// ============ CONFIGURAÇÕES ADICIONAIS ============
define('SITE_NAME', 'Clínica Profissional');
define('SITE_URL', 'http://localhost/clinica_profissional/');

// ============ VARIÁVEIS GLOBAIS (opcional) ============
// Remova estas linhas se não forem usadas:
// $host = "";
// $user = "";
// $pass = "";
// $db = "";

// ============ NÃO COLOQUE session_start() AQUI! ============
// A sessão deve ser iniciada em cada arquivo individualmente
// if (session_status() === PHP_SESSION_NONE) {
//     session_start(); // ← REMOVA ESTA LINHA TAMBÉM!
// }
?>