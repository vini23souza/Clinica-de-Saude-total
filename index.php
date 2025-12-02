<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Saúde Total - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #06d6a0;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
            --border-radius: 16px;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .background-effects {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .gradient-ball {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
        }

        .gradient-ball-1 {
            width: 300px;
            height: 300px;
            background: var(--primary);
            top: -100px;
            left: -100px;
            animation: float 15s infinite ease-in-out;
        }

        .gradient-ball-2 {
            width: 400px;
            height: 400px;
            background: var(--secondary);
            bottom: -150px;
            right: -100px;
            animation: float 18s infinite ease-in-out reverse;
        }

        .gradient-ball-3 {
            width: 200px;
            height: 200px;
            background: var(--accent);
            top: 50%;
            left: 70%;
            animation: float 12s infinite ease-in-out;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 50px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 10;
            transition: var(--transition);
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.4);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
        }

        .logo-icon::after {
            content: '+';
            font-size: 32px;
            color: white;
            font-weight: 300;
            z-index: 2;
        }

        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .logo p {
            color: var(--gray);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 20px 16px 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            transition: var(--transition);
        }

        .input-wrapper input::placeholder {
            color: var(--gray);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
            transition: var(--transition);
        }

        .input-wrapper input:focus + .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .login-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .links {
            text-align: center;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
            margin-bottom: 10px;
        }

        .links a:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .remember-me label {
            color: var(--gray);
            font-size: 14px;
            cursor: pointer;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: rgba(30, 41, 59, 0.95);
            padding: 30px;
            border-radius: 20px;
            max-width: 400px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: var(--shadow);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            color: var(--gray);
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
            border: 1px solid;
            backdrop-filter: blur(10px);
            animation: slideDown 0.5s ease;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.3);
        }

        .message.info {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
        }

        .role-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .role-option.active {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .role-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--gray);
        }

        .role-option.active i {
            color: var(--primary);
        }

        .role-option span {
            display: block;
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -20px); }
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 26px;
            }
            
            .links {
                flex-direction: column;
                align-items: center;
            }
            
            .links a {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Efeitos de fundo -->
    <div class="background-effects">
        <div class="gradient-ball gradient-ball-1"></div>
        <div class="gradient-ball gradient-ball-2"></div>
        <div class="gradient-ball gradient-ball-3"></div>
    </div>

    <div class="login-container">
        <div class="logo">
            <div class="logo-icon"></div>
            <h1>Clínica Saúde Total</h1>
            <p>Sistema de Gestão Médica</p>
        </div>
        
        <div class="role-selector">
            <div class="role-option active" data-role="medico">
                <i class="fas fa-user-md"></i>
                <span>Médico</span>
            </div>
            <div class="role-option" data-role="enfermeiro">
                <i class="fas fa-user-nurse"></i>
                <span>Enfermeiro</span>
            </div>
            <div class="role-option" data-role="admin">
                <i class="fas fa-cogs"></i>
                <span>Administrador</span>
            </div>
        </div>
        
        <form action="app.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="username">Usuário</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" required placeholder="Digite seu usuário">
                    <div class="input-icon"><i class="fas fa-user"></i></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required placeholder="Digite sua senha">
                    <div class="input-icon"><i class="fas fa-lock"></i></div>
                    <div class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Lembrar-me</label>
            </div>
            
            <button type="submit" class="login-btn" id="loginButton">
                <span id="buttonText">Acessar Sistema</span>
                <div class="loading" id="loadingSpinner"></div>
            </button>
        </form>
        
        <div class="links">
            <a href="#" onclick="showCredentials()"><i class="fas fa-key"></i> Credenciais de teste</a>
            <a href="#"><i class="fas fa-question-circle"></i> Ajuda</a>
            <a href="#"><i class="fas fa-lock"></i> Esqueci minha senha</a>
        </div>
    </div>

    <!-- Modal de credenciais -->
    <div id="credentialsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="hideCredentials()">&times;</span>
            <h3 style="color: white; margin-bottom: 15px; text-align: center;">Credenciais de Teste</h3>
            <div style="background: rgba(99, 102, 241, 0.1); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(99, 102, 241, 0.3);">
                <p style="color: #a1a1aa; margin-bottom: 8px;"><strong>Usuário:</strong></p>
                <p style="color: white; font-family: monospace; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 5px;">admin</p>
                
                <p style="color: #a1a1aa; margin: 15px 0 8px;"><strong>Senha:</strong></p>
                <p style="color: white; font-family: monospace; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 5px;">password</p>
            </div>
            <button onclick="hideCredentials()" class="login-btn" style="margin-top: 0;">Fechar</button>
        </div>
    </div>

    <script>
        // Seleção de função (médico, enfermeiro, admin)
        const roleOptions = document.querySelectorAll('.role-option');
        roleOptions.forEach(option => {
            option.addEventListener('click', () => {
                roleOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
            });
        });

        // Alternar visibilidade da senha
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        
        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordToggle.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Funções do modal
        function showCredentials() {
            document.getElementById('credentialsModal').style.display = 'flex';
        }
        
        function hideCredentials() {
            document.getElementById('credentialsModal').style.display = 'none';
        }
        
        // Fechar modal clicando fora
        document.getElementById('credentialsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCredentials();
            }
        });

        // Animação de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const loginContainer = document.querySelector('.login-container');
            loginContainer.style.opacity = '0';
            loginContainer.style.transform = 'translateY(30px) scale(0.95)';
            
            setTimeout(() => {
                loginContainer.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
                loginContainer.style.opacity = '1';
                loginContainer.style.transform = 'translateY(0) scale(1)';
            }, 100);
            
            // Foco automático
            document.getElementById('username').focus();
        });
        
        // Validação do formulário
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const button = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            if (!username || !password) {
                e.preventDefault();
                showMessage('Por favor, preencha todos os campos.', 'error');
                return false;
            }
            
            // Loading state
            buttonText.style.display = 'none';
            loadingSpinner.style.display = 'block';
            button.disabled = true;
            
            // Simular tempo de processamento
            setTimeout(() => {
                buttonText.style.display = 'block';
                loadingSpinner.style.display = 'none';
                button.disabled = false;
            }, 2000);
        });

        // Função para exibir mensagens
        function showMessage(text, type) {
            // Remover mensagens existentes
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Criar nova mensagem
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.textContent = text;
            
            // Inserir após o logo
            const logo = document.querySelector('.logo');
            logo.parentNode.insertBefore(message, logo.nextSibling);
            
            // Remover após 5 segundos
            setTimeout(() => {
                if (message.parentNode) {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => {
                        if (message.parentNode) {
                            message.remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
    </script>
</body>
</html>