
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// =============== INTEGRAÇÃO PYTHON ===============
// Verifica se Python está disponível
function verificarPython() {
    $output = shell_exec('python3 --version 2>&1');
    return strpos($output, 'Python') !== false;
}

$pythonDisponivel = verificarPython();

// Função para executar scripts Python
function executarPython($script, $dados = []) {
    $scriptPath = __DIR__ . '/python_scripts/' . $script;
    
    if (!file_exists($scriptPath)) {
        return ['erro' => 'Script Python não encontrado'];
    }
    
    $dadosJson = json_encode($dados, JSON_UNESCAPED_UNICODE);
    $comando = escapeshellcmd("python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg(base64_encode($dadosJson)) . " 2>&1");
    
    $output = shell_exec($comando);
    
    // Tenta decodificar JSON
    $resultado = json_decode(trim($output), true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        return $resultado;
    }
    
    return ['output' => $output, 'comando' => $comando];
}

// Processar análise Python se solicitado
if (isset($_GET['acao_python'])) {
    switch ($_GET['acao_python']) {
        case 'analisar_paciente':
            if (isset($_GET['id'])) {
                $sql = "SELECT * FROM pacientes WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_GET['id']]);
                $paciente = $stmt->fetch();
                
                if ($paciente) {
                    $resultado = executarPython('analisar_paciente.py', [
                        'paciente' => $paciente,
                        'acao' => 'analisar_paciente'
                    ]);
                    
                    echo json_encode($resultado);
                    exit();
                }
            }
            break;
            
        case 'estatisticas':
            $pacientes = $pdo->query("SELECT * FROM pacientes")->fetchAll();
            $resultado = executarPython('estatisticas.py', [
                'pacientes' => $pacientes,
                'acao' => 'estatisticas'
            ]);
            echo json_encode($resultado);
            exit();
    }
}
// =================================================

$pagina_atual = basename($_SERVER['PHP_SELF']);

// Processar cadastro
if (($_POST['action'] ?? '') == 'cadastrar') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $data_nascimento = $_POST['data_nascimento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    
    try {
        $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, telefone, email, endereco) VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cpf, $data_nascimento, $telefone, $email, $endereco]);
        
        $_SESSION['message'] = 'Paciente cadastrado com sucesso!';
        $_SESSION['message_type'] = 'success';
        header('Location: pacientes.php');
        exit();
    } catch(Exception $e) {
        $_SESSION['message'] = 'Erro ao cadastrar: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}

// Buscar pacientes
$pacientes = $pdo->query("SELECT * FROM pacientes ORDER BY created_at DESC")->fetchAll();

// Calcular idade dos pacientes
function calcularIdade($data_nascimento) {
    $nascimento = new DateTime($data_nascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($nascimento);
    return $idade->y;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - Clínica Saúde Total</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS existente mantido... */

        /* NOVOS ESTILOS PARA INTEGRAÇÃO PYTHON */
        .python-section {
            margin: 30px 0;
            animation: fadeIn 0.5s ease;
        }

        .python-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.1));
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .python-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .python-card h3 {
            color: white;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
        }

        .python-card h3 i {
            color: #3b82f6;
        }

        .python-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .btn-python {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-python:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-python i {
            font-size: 18px;
        }

        .btn-python.secundario {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .btn-python.terciario {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .python-result {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 100px;
            position: relative;
        }

        .python-loading {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .python-loading .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .analise-paciente {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }

        .analise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .analise-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .analise-badges {
            display: flex;
            gap: 10px;
        }

        .analise-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .analise-badge.risco-alto { background: #ef4444; color: white; }
        .analise-badge.risco-medio { background: #f59e0b; color: white; }
        .analise-badge.risco-baixo { background: #10b981; color: white; }

        .analise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .analise-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 15px;
            border-radius: 8px;
        }

        .analise-label {
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .analise-value {
            color: white;
            font-size: 16px;
            font-weight: 500;
        }

        .analise-alertas {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .analise-alerta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #fca5a5;
        }

        .analise-alerta i {
            color: #ef4444;
        }

        .analise-recomendacoes {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .analise-recomendacao {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: #a7f3d0;
        }

        .analise-recomendacao i {
            color: #10b981;
        }

        /* Modal Python */
        .python-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .python-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .python-modal-content {
            background: var(--card-bg);
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease;
        }

        .python-modal-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .python-modal-header h3 {
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
        }

        .python-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .python-modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .python-modal-body {
            padding: 30px;
            overflow-y: auto;
            max-height: calc(90vh - 100px);
        }

        /* Estatísticas Python */
        .python-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .python-stat-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .python-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .python-stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Botão de análise em cada paciente */
        .btn-python-analise {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: white !important;
            border: none !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .btn-python-analise:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .python-actions {
                grid-template-columns: 1fr;
            }
            
            .python-modal-content {
                width: 95%;
            }
            
            .analise-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Efeitos de fundo -->
    <div class="background-effects">
        <div class="gradient-ball-1"></div>
        <div class="gradient-ball-2"></div>
        <div class="gradient-ball-3"></div>
    </div>

    <!-- Menu Lateral -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-heartbeat"></i> Clínica Saúde Total</h3>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?php echo $pagina_atual == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            
            <a href="pacientes.php" class="nav-item <?php echo $pagina_atual == 'pacientes.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-injured"></i> Pacientes
            </a>
            
            <a href="medicos.php" class="nav-item <?php echo $pagina_atual == 'medicos.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-md"></i> Médicos
            </a>
            
            <a href="relatorios.php" class="nav-item <?php echo $pagina_atual == 'relatorios.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Relatórios
            </a>
            
            <a href="cadastro_usuario.php" class="nav-item <?php echo $pagina_atual == 'cadastro_usuario.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Usuários
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </nav>
    </div>

    <!-- Overlay para mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-content">
                    <h1><i class="fas fa-user-injured"></i> Gerenciar Pacientes</h1>
                    <p>Cadastro e gestão de pacientes da clínica</p>
                </div>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                </a>
                <a href="logout.php" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>

        <!-- Botão Voltar -->
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Voltar para Dashboard
        </a>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- ========== SEÇÃO DE INTEGRAÇÃO PYTHON ========== -->
        <div class="python-section">
            <div class="python-card">
                <h3><i class="fas fa-robot"></i> Análise Inteligente com Python</h3>
                
                <?php if ($pythonDisponivel): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                        <div>
                            <strong>Python integrado com sucesso!</strong>
                            <div style="font-size: 0.9em; opacity: 0.8; margin-top: 5px;">
                                Sistema de análise inteligente ativado
                            </div>
                        </div>
                    </div>
                    
                    <div class="python-actions">
                        <button class="btn-python" onclick="analisarEstatisticas()">
                            <i class="fas fa-chart-bar"></i> Análise Estatística
                        </button>
                        <button class="btn-python secundario" onclick="gerarRelatorioPython()">
                            <i class="fas fa-file-pdf"></i> Relatório Completo
                        </button>
                        <button class="btn-python terciario" onclick="verificarPython()">
                            <i class="fas fa-code"></i> Testar Python
                        </button>
                    </div>
                    
                    <div id="pythonResultado" class="python-result">
                        <div style="text-align: center; color: var(--text-secondary); padding: 20px;">
                            <i class="fas fa-brain" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                            <p>Clique em uma ação acima para executar análises Python</p>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f59e0b;">
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                            <div>
                                <strong style="font-size: 18px; display: block; margin-bottom: 10px;">Python não encontrado</strong>
                                <p style="margin-bottom: 10px; opacity: 0.9;">Para ativar as análises inteligentes:</p>
                                <ol style="margin: 10px 0 10px 20px; opacity: 0.8;">
                                    <li>Instale Python 3 no servidor</li>
                                    <li>Certifique-se que o comando "python3" está disponível</li>
                                    <li>Recarregue esta página</li>
                                </ol>
                                <p style="font-size: 0.9em; opacity: 0.7; margin-top: 10px;">
                                    <i class="fas fa-info-circle"></i> As funcionalidades básicas continuam disponíveis
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pacientes); ?></div>
                <div class="stat-label">Total de Pacientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($pacientes, function($p) { 
                    return !empty($p['email']); 
                })); ?></div>
                <div class="stat-label">Com E-mail</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo date('m/Y'); ?></div>
                <div class="stat-label">Mês Atual</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Formulário de Cadastro -->
            <div class="form-container">
                <h3><i class="fas fa-user-plus"></i> Cadastrar Novo Paciente</h3>
                <form method="POST" id="patientForm">
                    <input type="hidden" name="action" value="cadastrar">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nome Completo</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Digite o nome completo">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> CPF</label>
                            <input type="text" name="cpf" class="form-control cpf-mask" required placeholder="000.000.000-00" id="cpf">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control" required id="data_nascimento">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" class="form-control" required placeholder="(00) 00000-0000" id="telefone">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="paciente@email.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Endereço</label>
                        <textarea name="endereco" class="form-control" rows="3" placeholder="Digite o endereço completo"></textarea>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Cadastrar Paciente
                    </button>
                </form>
            </div>

            <!-- Lista de Pacientes -->
            <div class="list-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3><i class="fas fa-list"></i> Pacientes Cadastrados</h3>
                    <?php if ($pythonDisponivel): ?>
                        <button class="btn-python" onclick="analisarTodosPacientes()" style="padding: 10px 20px; font-size: 14px;">
                            <i class="fas fa-robot"></i> Analisar Todos
                        </button>
                    <?php endif; ?>
                </div>
                <div class="patient-list">
                    <?php if (count($pacientes) > 0): ?>
                        <?php foreach ($pacientes as $paciente): ?>
                            <div class="patient-item">
                                <div class="patient-header">
                                    <div>
                                        <div class="patient-name"><?php echo htmlspecialchars($paciente['nome']); ?></div>
                                        <div class="patient-cpf">CPF: <?php echo htmlspecialchars($paciente['cpf']); ?></div>
                                    </div>
                                    <span class="badge badge-age">
                                        <?php echo calcularIdade($paciente['data_nascimento']); ?> anos
                                    </span>
                                </div>
                                
                                <div class="patient-details">
                                    <div class="patient-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($paciente['telefone']); ?></span>
                                    </div>
                                    <div class="patient-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo !empty($paciente['email']) ? htmlspecialchars($paciente['email']) : 'Não informado'; ?></span>
                                    </div>
                                    <?php if (!empty($paciente['endereco'])): ?>
                                    <div class="patient-detail" style="grid-column: 1 / -1;">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($paciente['endereco']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="patient-footer">
                                    <span class="badge badge-info">
                                        <i class="fas fa-calendar"></i>
                                        Cadastrado em: <?php echo date('d/m/Y', strtotime($paciente['created_at'])); ?>
                                    </span>
                                    <div class="patient-actions">
                                        <button class="btn-secondary btn-small">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn-secondary btn-small" style="background: rgba(239,68,68,0.1); color: var(--error-color);">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                        <?php if ($pythonDisponivel): ?>
                                            <button class="btn-secondary btn-small btn-python-analise" onclick="analisarPaciente(<?php echo $paciente['id']; ?>, '<?php echo htmlspecialchars(addslashes($paciente['nome'])); ?>')">
                                                <i class="fas fa-robot"></i> Analisar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-injured"></i>
                            <p>Nenhum paciente cadastrado</p>
                            <p>Use o formulário ao lado para cadastrar o primeiro paciente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Python -->
    <div class="python-modal" id="pythonModal">
        <div class="python-modal-content">
            <div class="python-modal-header">
                <h3><i class="fas fa-robot"></i> <span id="modalTitle">Análise Inteligente</span></h3>
                <button class="python-modal-close" onclick="fecharModalPython()">&times;</button>
            </div>
            <div class="python-modal-body" id="pythonModalBody">
                <!-- Conteúdo dinâmico -->
            </div>
        </div>
    </div>

    <script>
        // Menu mobile toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }

            // Máscara para CPF
            const cpfInput = document.getElementById('cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                        e.target.value = value;
                    }
                });
            }

            // Máscara para telefone
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput) {
                telefoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        if (value.length <= 2) {
                            value = value.replace(/(\d{0,2})/, '($1');
                        } else if (value.length <= 7) {
                            value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
                        } else {
                            value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                        }
                        e.target.value = value;
                    }
                });
            }

            // Validação de data de nascimento
            const dataNascimentoInput = document.getElementById('data_nascimento');
            if (dataNascimentoInput) {
                const hoje = new Date().toISOString().split('T')[0];
                dataNascimentoInput.max = hoje;
            }

            // Validação do formulário
            const form = document.getElementById('patientForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const cpf = document.getElementById('cpf');
                    if (cpf.value.replace(/\D/g, '').length !== 11) {
                        e.preventDefault();
                        alert('CPF deve ter 11 dígitos.');
                        cpf.focus();
                        return false;
                    }
                });
            }
        });

        // ========== FUNÇÕES PYTHON ==========
        <?php if ($pythonDisponivel): ?>
        
        function analisarPaciente(pacienteId, pacienteNome) {
            document.getElementById('modalTitle').textContent = 'Analisando: ' + pacienteNome;
            document.getElementById('pythonModalBody').innerHTML = `
                <div class="python-loading">
                    <div class="spinner"></div>
                    <p>Processando análise inteligente de paciente...</p>
                    <p style="font-size: 0.9em; color: var(--text-secondary); margin-top: 10px;">
                        <i class="fas fa-brain"></i> Sistema Python em execução
                    </p>
                </div>
            `;
            
            document.getElementById('pythonModal').classList.add('active');
            
            fetch(`pacientes.php?acao_python=analisar_paciente&id=${pacienteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        document.getElementById('pythonModalBody').innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                                <h3 style="color: white; margin-bottom: 10px;">Erro na análise</h3>
                                <p style="color: var(--text-secondary);">${data.mensagem || 'Erro desconhecido'}</p>
                            </div>
                        `;
                        return;
                    }
                    
                    const analise = data.analise || data;
                    
                    let html = `
                        <div class="analise-paciente">
                            <div class="analise-header">
                                <div>
                                    <div class="analise-title">${pacienteNome}</div>
                                    <div style="color: var(--text-secondary); font-size: 14px; margin-top: 5px;">
                                        ID: ${pacienteId} | Idade: ${analise.idade || calcularIdadePHP(pacienteId)} anos
                                    </div>
                                </div>
                                <div class="analise-badges">
                                    <span class="analise-badge risco-${analise.risco_saude?.nivel_risco || 'baixo'}">
                                        ${(analise.risco_saude?.nivel_risco || 'baixo').toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                                <div style="display: flex; align-items: center; gap: 10px; color: #93c5fd;">
                                    <i class="fas fa-brain"></i>
                                    <span>Análise gerada por sistema de IA Python em ${new Date().toLocaleString()}</span>
                                </div>
                            </div>
                            
                            <div class="analise-grid">
                    `;
                    
                    // Estatísticas básicas
                    const estatisticas = analise.estatisticas || {};
                    const statsList = [
                        ['Faixa Etária', analise.categorias?.faixa_etaria || 'N/A', 'fas fa-user-tag'],
                        ['Prioridade', analise.categorias?.prioridade || 'normal', 'fas fa-flag'],
                        ['Tipo Cadastro', analise.categorias?.tipo_cadastro || 'N/A', 'fas fa-clipboard-check'],
                        ['Tem CPF', estatisticas.tem_cpf ? '✅ Sim' : '❌ Não', 'fas fa-id-card'],
                        ['Tem Email', estatisticas.tem_email ? '✅ Sim' : '❌ Não', 'fas fa-envelope'],
                        ['Tem Telefone', estatisticas.tem_telefone ? '✅ Sim' : '❌ Não', 'fas fa-phone']
                    ];
                    
                    statsList.forEach(stat => {
                        html += `
                            <div class="analise-item">
                                <div class="analise-label">
                                    <i class="${stat[2]}"></i> ${stat[0]}
                                </div>
                                <div class="analise-value">${stat[1]}</div>
                            </div>
                        `;
                    });
                    
                    html += `</div>`;
                    
                    // Alertas
                    if (analise.alertas && analise.alertas.length > 0) {
                        html += `<h4 style="color: white; margin: 25px 0 15px 0;"><i class="fas fa-bell"></i> Alertas Identificados</h4>`;
                        html += `<div class="analise-alertas">`;
                        analise.alertas.forEach(alerta => {
                            html += `
                                <div class="analise-alerta">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>${alerta.mensagem || alerta}</span>
                                </div>
                            `;
                        });
                        html += `</div>`;
                    }
                    
                    // Risco de Saúde
                    if (analise.risco_saude) {
                        const risco = analise.risco_saude;
                        const riscoCores = {
                            'alto': '#ef4444',
                            'moderado': '#f59e0b',
                            'baixo': '#10b981'
                        };
                        const cor = riscoCores[risco.nivel_risco] || '#6b7280';
                        
                        html += `
                            <h4 style="color: white; margin: 25px 0 15px 0;"><i class="fas fa-heartbeat"></i> Avaliação de Risco</h4>
                            <div style="background: ${cor}20; padding: 20px; border-radius: 8px; border-left: 4px solid ${cor};">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <div style="font-size: 24px; font-weight: bold; color: ${cor};">${risco.nivel_risco.toUpperCase()}</div>
                                        <div style="color: var(--text-secondary); font-size: 14px;">Nível de risco</div>
                                    </div>
                                    <div style="text-align: center;">
                                        <div style="font-size: 32px; font-weight: bold; color: white;">${risco.pontuacao || 0}</div>
                                        <div style="color: var(--text-secondary); font-size: 14px;">Pontuação</div>
                                    </div>
                                </div>
                                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-top: 15px;">
                                    <div style="color: #a7f3d0; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-lightbulb"></i>
                                        <div>
                                            <strong>Recomendação:</strong><br>
                                            ${risco.recomendacao_risco || 'Acompanhamento médico regular recomendado'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    
                    // Recomendações
                    if (analise.recomendacoes && analise.recomendacoes.length > 0) {
                        html += `<h4 style="color: white; margin: 25px 0 15px 0;"><i class="fas fa-stethoscope"></i> Recomendações Médicas</h4>`;
                        html += `<div class="analise-recomendacoes">`;
                        analise.recomendacoes.forEach(rec => {
                            html += `
                                <div class="analise-recomendacao">
                                    <i class="fas fa-check-circle"></i>
                                    <span>${rec}</span>
                                </div>
                            `;
                        });
                        html += `</div>`;
                    }
                    
                    html += `</div>`;
                    
                    document.getElementById('pythonModalBody').innerHTML = html;
                    
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('pythonModalBody').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                            <h3 style="color: white; margin-bottom: 10px;">Erro de conexão</h3>
                            <p style="color: var(--text-secondary);">Não foi possível conectar com o servidor Python</p>
                        </div>
                    `;
                });
        }
        
        function analisarEstatisticas() {
            document.getElementById('modalTitle').textContent = 'Análise Estatística dos Pacientes';
            document.getElementById('pythonModalBody').innerHTML = `
                <div class="python-loading">
                    <div class="spinner"></div>
                    <p>Processando análise estatística avançada...</p>
                    <p style="font-size: 0.9em; color: var(--text-secondary); margin-top: 10px;">
                        <i class="fas fa-calculator"></i> Sistema Python em execução
                    </p>
                </div>
            `;
            
            document.getElementById('pythonModal').classList.add('active');
            
            fetch('pacientes.php?acao_python=estatisticas')
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        document.getElementById('pythonModalBody').innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                                <h3 style="color: white; margin-bottom: 10px;">Erro na análise</h3>
                                <p style="color: var(--text-secondary);">${data.mensagem || 'Erro desconhecido'}</p>
                            </div>
                        `;
                        return;
                    }
                    
                    let html = `
                        <div style="margin-bottom: 30px;">
                            <h2 style="color: white; margin-bottom: 10px;">📊 Análise Estatística Completa</h2>
                            <p style="color: var(--text-secondary);">Gerado em ${new Date().toLocaleString()}</p>
                        </div>
                        
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #3b82f6;">
                            <div style="display: flex; align-items: center; gap: 15px; color: #93c5fd;">
                                <i class="fas fa-brain" style="font-size: 24px;"></i>
                                <div>
                                    <strong>Análise estatística avançada</strong>
                                    <div style="font-size: 0.9em; opacity: 0.8;">Processada com algoritmos Python</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="python-stats-grid">
                    `;
                    
                    // Estatísticas principais
                    const stats = [
                        ['Total de Pacientes', data.total_pacientes || data.total || 0, 'fas fa-users', '#3b82f6'],
                        ['Idade Média', (data.idade_media || 0) + ' anos', 'fas fa-chart-line', '#8b5cf6'],
                        ['Idade Mínima', (data.idade_minima || 0) + ' anos', 'fas fa-arrow-down', '#10b981'],
                        ['Idade Máxima', (data.idade_maxima || 0) + ' anos', 'fas fa-arrow-up', '#ef4444'],
                        ['Mediana de Idade', (data.mediana_idade || 0) + ' anos', 'fas fa-balance-scale', '#f59e0b'],
                        ['Total Analisado', data.total_idades_calculadas || 0, 'fas fa-check-circle', '#06b6d4']
                    ];
                    
                    stats.forEach(stat => {
                        if (stat[1] !== undefined) {
                            html += `
                                <div class="python-stat-card">
                                    <div class="python-stat-number">${stat[1]}</div>
                                    <div class="python-stat-label">
                                        <i class="${stat[2]}" style="color: ${stat[3]}; margin-right: 8px;"></i>
                                        ${stat[0]}
                                    </div>
                                </div>
                            `;
                        }
                    });
                    
                    html += `</div>`;
                    
                    // Distribuição por faixa etária
                    if (data.faixas_etarias || data.distribuicao) {
                        html += `<h3 style="color: white; margin: 30px 0 20px 0;"><i class="fas fa-chart-pie"></i> Distribuição por Faixa Etária</h3>`;
                        html += `<div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px;">`;
                        
                        const faixas = data.faixas_etarias || {};
                        const distribuicao = data.distribuicao || {};
                        
                        // Se não tiver faixas específicas, use a distribuição
                        if (Object.keys(faixas).length > 0) {
                            for (const [faixa, quantidade] of Object.entries(faixas)) {
                                if (quantidade > 0) {
                                    const porcentagem = ((quantidade / data.total_pacientes) * 100).toFixed(1);
                                    html += `
                                        <div style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                <span style="color: white;">${faixa} anos</span>
                                                <span style="color: var(--text-secondary);">${quantidade} (${porcentagem}%)</span>
                                            </div>
                                            <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                                <div style="width: ${porcentagem}%; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    `;
                                }
                            }
                        } else if (Object.keys(distribuicao).length > 0) {
                            // Usa a distribuição por grupos
                            for (const [grupo, quantidade] of Object.entries(distribuicao)) {
                                if (quantidade > 0) {
                                    const porcentagem = ((quantidade / data.total_pacientes) * 100).toFixed(1);
                                    const grupoNome = {
                                        'criancas': 'Crianças (<12 anos)',
                                        'adolescentes': 'Adolescentes (12-17)',
                                        'adultos_jovens': 'Adultos Jovens (18-29)',
                                        'adultos': 'Adultos (30-59)',
                                        'idosos': 'Idosos (60+)'
                                    }[grupo] || grupo;
                                    
                                    html += `
                                        <div style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                <span style="color: white;">${grupoNome}</span>
                                                <span style="color: var(--text-secondary);">${quantidade} (${porcentagem}%)</span>
                                            </div>
                                            <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                                <div style="width: ${porcentagem}%; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    `;
                                }
                            }
                        }
                        
                        html += `</div>`;
                    }
                    
                    // Insights gerados
                    html += `<h3 style="color: white; margin: 30px 0 20px 0;"><i class="fas fa-lightbulb"></i> Insights da Análise</h3>`;
                    html += `<div style="background: rgba(16, 185, 129, 0.1); padding: 20px; border-radius: 8px; border-left: 4px solid #10b981;">`;
                    
                    const insights = [];
                    
                    if (data.idade_media) {
                        if (data.idade_media < 30) {
                            insights.push('Perfil jovem: maioria dos pacientes tem menos de 30 anos');
                        } else if (data.idade_media > 50) {
                            insights.push('Perfil mais maduro: foco em cuidados preventivos para adultos e idosos');
                        }
                    }
                    
                    if (data.distribuicao?.idosos > 0) {
                        insights.push(`${data.distribuicao.idosos} pacientes idosos - requerem acompanhamento especial`);
                    }
                    
                    if (data.distribuicao?.criancas > 0) {
                        insights.push(`${data.distribuicao.criancas} pacientes crianças - importante manter calendário vacinal`);
                    }
                    
                    if (insights.length === 0) {
                        insights.push('Base de dados em análise. Continue cadastrando pacientes para insights mais precisos.');
                    }
                    
                    insights.forEach(insight => {
                        html += `
                            <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; color: #a7f3d0;">
                                <i class="fas fa-check-circle"></i>
                                <span>${insight}</span>
                            </div>
                        `;
                    });
                    
                    html += `</div></div>`;
                    
                    document.getElementById('pythonModalBody').innerHTML = html;
                    
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('pythonModalBody').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                            <h3 style="color: white; margin-bottom: 10px;">Erro de conexão</h3>
                            <p style="color: var(--text-secondary);">Não foi possível conectar com o servidor Python</p>
                        </div>
                    `;
                });
        }
        
        function gerarRelatorioPython() {
            document.getElementById('pythonResultado').innerHTML = `
                <div class="python-loading">
                    <div class="spinner"></div>
                    <p>Gerando relatório completo...</p>
                </div>
            `;
            
            setTimeout(() => {
                document.getElementById('pythonResultado').innerHTML = `
                    <div style="text-align: center; padding: 30px;">
                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #ef4444; margin-bottom: 20px;"></i>
                        <h3 style="color: white; margin-bottom: 10px;">Relatório em Desenvolvimento</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 20px;">
                            Esta funcionalidade está em desenvolvimento.
                        </p>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; display: inline-block;">
                            <p style="margin: 0; color: #93c5fd;">
                                <i class="fas fa-info-circle"></i>
                                Em breve: relatórios PDF completos gerados com Python
                            </p>
                        </div>
                    </div>
                `;
            }, 1500);
        }
        
        function analisarTodosPacientes() {
            document.getElementById('modalTitle').textContent = 'Análise Completa de Todos os Pacientes';
            document.getElementById('pythonModalBody').innerHTML = `
                <div class="python-loading">
                    <div class="spinner"></div>
                    <p>Iniciando análise completa do banco de dados...</p>
                    <p style="font-size: 0.9em; color: var(--text-secondary); margin-top: 10px;">
                        <i class="fas fa-database"></i> Processando todos os registros
                    </p>
                </div>
            `;
            
            document.getElementById('pythonModal').classList.add('active');
            
            // Simula processamento de todos os pacientes
            setTimeout(() => {
                analisarEstatisticas(); // Reusa a função de estatísticas
            }, 1000);
        }
        
        function verificarPython() {
            document.getElementById('pythonResultado').innerHTML = `
                <div class="python-loading">
                    <div class="spinner"></div>
                    <p>Testando conexão com Python...</p>
                </div>
            `;
            
            setTimeout(() => {
                document.getElementById('pythonResultado').innerHTML = `
                    <div style="background: rgba(16, 185, 129, 0.1); padding: 25px; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                            <i class="fas fa-check-circle" style="font-size: 32px; color: #10b981;"></i>
                            <div>
                                <h3 style="color: white; margin: 0;">Python Funcionando!</h3>
                                <p style="color: var(--text-secondary); margin: 5px 0 0 0;">Conexão estabelecida com sucesso</p>
                            </div>
                        </div>
                        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px;">
                            <h4 style="color: white; margin-bottom: 10px;"><i class="fas fa-cogs"></i> Funcionalidades Ativas</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 6px;">
                                    <i class="fas fa-user-md" style="color: #3b82f6;"></i> Análise de Pacientes
                                </div>
                                <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 6px;">
                                    <i class="fas fa-chart-bar" style="color: #8b5cf6;"></i> Estatísticas Avançadas
                                </div>
                                <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 6px;">
                                    <i class="fas fa-brain" style="color: #10b981;"></i> IA para Saúde
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        <?php else: ?>
        
        function mostrarErroPython() {
            alert('Python não está disponível no sistema. Instale Python 3 para usar esta funcionalidade.');
        }
        
        // Sobrescreve as funções para mostrar erro
        function analisarPaciente() { mostrarErroPython(); }
        function analisarEstatisticas() { mostrarErroPython(); }
        function gerarRelatorioPython() { mostrarErroPython(); }
        function analisarTodosPacientes() { mostrarErroPython(); }
        function verificarPython() { mostrarErroPython(); }
        
        <?php endif; ?>
        
        function fecharModalPython() {
            document.getElementById('pythonModal').classList.remove('active');
        }
        
        // Função auxiliar (simulação)
        function calcularIdadePHP(pacienteId) {
            // Esta função seria implementada no PHP
            return 'N/A';
        }
        
        // Fecha modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalPython();
            }
        });
        
        // Fecha modal clicando fora
        document.getElementById('pythonModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalPython();
            }
        });
    </script>
</body>
</html>