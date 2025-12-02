<?php
require_once 'config.php';

try {
    // Tabela de usuários
    $sql = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        user_type ENUM('admin', 'medico', 'recepcionista') DEFAULT 'recepcionista',
        nome_completo VARCHAR(100),
        telefone VARCHAR(15),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Tabela 'usuarios' criada com sucesso.<br>";
    
    // Tabela de pacientes
    $sql = "CREATE TABLE IF NOT EXISTS pacientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        cpf VARCHAR(14) UNIQUE,
        data_nascimento DATE,
        telefone VARCHAR(15),
        email VARCHAR(100),
        endereco TEXT,
        cidade VARCHAR(50),
        estado VARCHAR(2),
        cep VARCHAR(9),
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Tabela 'pacientes' criada com sucesso.<br>";
    
    // Tabela de médicos
    $sql = "CREATE TABLE IF NOT EXISTS medicos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        crm VARCHAR(20) UNIQUE,
        especialidade VARCHAR(100),
        telefone VARCHAR(15),
        email VARCHAR(100),
        endereco_consultorio TEXT,
        horario_atendimento VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Tabela 'medicos' criada com sucesso.<br>";
    
    // Tabela de consultas
    $sql = "CREATE TABLE IF NOT EXISTS consultas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        paciente_id INT,
        medico_id INT,
        data_consulta DATETIME,
        status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
        observacoes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
        FOREIGN KEY (medico_id) REFERENCES medicos(id)
    )";
    
    $pdo->exec($sql);
    echo "Tabela 'consultas' criada com sucesso.<br>";
    
    // Inserir usuário admin padrão
    $password = password_hash('password', PASSWORD_DEFAULT);
    $sql = "INSERT IGNORE INTO usuarios (username, password, email, user_type, nome_completo, telefone) 
            VALUES ('admin', '$password', 'admin@clinica.com', 'admin', 'Administrador do Sistema', '(11) 9999-9999')";
    $pdo->exec($sql);
    echo "Usuário admin criado: admin / password<br>";
    
    echo "<p style='color: green; padding: 20px; background: #f0f9ff; border-radius: 10px;'>Todas as tabelas foram criadas com sucesso! ✅</p>";
    
} catch(PDOException $e) {
    die("ERRO ao criar tabelas: " . $e->getMessage());
}
?>