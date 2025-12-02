
<?php
// app.php - Página de processamento de login
// Este arquivo NÃO deve ter HTML, apenas processamento

// INÍCIO CORRETO - Verifica se a sessão já começou
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
// REMOVA require_once 'python_helper.php' daqui - só use nas páginas que precisam

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    try {
        // Buscar usuário pelo username
        $sql = "SELECT * FROM usuarios WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verificar a senha
            if (password_verify($password, $user['password'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['nome_completo'] = $user['nome_completo'];
                
                // Redirecionar para o dashboard
                header('Location: dashboard.php');
                exit();
                
            } else {
                // Senha incorreta
                $_SESSION['message'] = 'Senha incorreta. Tente novamente.';
                $_SESSION['message_type'] = 'error';
                header('Location: index.php');
                exit();
            }
        } else {
            // Usuário não encontrado
            $_SESSION['message'] = 'Usuário não encontrado.';
            $_SESSION['message_type'] = 'error';
            header('Location: index.php');
            exit();
        }
        
    } catch(PDOException $e) {
        $_SESSION['message'] = 'Erro no sistema: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header('Location: index.php');
        exit();
    }
} else {
    // Se não for POST, redirecionar para login
    header('Location: index.php');
    exit();
}
?>