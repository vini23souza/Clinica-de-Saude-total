
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$pagina_atual = basename($_SERVER['PHP_SELF']);

// Lista completa de especialidades médicas
$especialidades = [
    'Acupuntura',
    'Alergia e Imunologia',
    'Anestesiologia',
    'Angiologia',
    'Cardiologia',
    'Cirurgia Cardiovascular',
    'Cirurgia da Mão',
    'Cirurgia de Cabeça e Pescoço',
    'Cirurgia do Aparelho Digestivo',
    'Cirurgia Geral',
    'Cirurgia Pediátrica',
    'Cirurgia Plástica',
    'Cirurgia Torácica',
    'Cirurgia Vascular',
    'Clínica Médica',
    'Coloproctologia',
    'Dermatologia',
    'Endocrinologia e Metabologia',
    'Endoscopia',
    'Gastroenterologia',
    'Genética Médica',
    'Geriatria',
    'Ginecologia e Obstetrícia',
    'Hematologia e Hemoterapia',
    'Homeopatia',
    'Infectologia',
    'Mastologia',
    'Medicina de Emergência',
    'Medicina de Família e Comunidade',
    'Medicina do Trabalho',
    'Medicina Esportiva',
    'Medicina Física e Reabilitação',
    'Medicina Intensiva',
    'Medicina Legal e Perícia Médica',
    'Medicina Nuclear',
    'Medicina Preventiva e Social',
    'Nefrologia',
    'Neurocirurgia',
    'Neurologia',
    'Nutrologia',
    'Oftalmologia',
    'Oncologia Clínica',
    'Ortopedia e Traumatologia',
    'Otorrinolaringologia',
    'Patologia',
    'Pediatria',
    'Pneumologia',
    'Psiquiatria',
    'Radiologia e Diagnóstico por Imagem',
    'Radioterapia',
    'Reumatologia',
    'Urologia'
];

// Processar cadastro
if (($_POST['action'] ?? '') == 'cadastrar') {
    $nome = $_POST['nome'];
    $crm = $_POST['crm'];
    $especialidade = $_POST['especialidade'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco_consultorio = $_POST['endereco_consultorio'];
    
    try {
        $sql = "INSERT INTO medicos (nome, crm, especialidade, telefone, email, endereco_consultorio) VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $crm, $especialidade, $telefone, $email, $endereco_consultorio]);
        
        $_SESSION['message'] = 'Médico cadastrado com sucesso!';
        $_SESSION['message_type'] = 'success';
        header('Location: medicos.php');
        exit();
    } catch(Exception $e) {
        $_SESSION['message'] = 'Erro ao cadastrar: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}

// Buscar médicos
$medicos = $pdo->query("SELECT * FROM medicos ORDER BY created_at DESC")->fetchAll();

// Estatísticas
$total_medicos = count($medicos);
$especialidades_unicas = array_unique(array_column($medicos, 'especialidade'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos - Clínica Saúde Total</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Melhorias específicas para a página de médicos */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .form-container, .list-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .form-container::before, .list-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
        }

        .form-container h3, .list-container h3 {
            font-size: 22px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-container h3 i, .list-container h3 i {
            color: var(--accent-glow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            width: 16px;
            color: var(--accent-glow);
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--text-color);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Sistema de especialidades melhorado */
        .specialty-container {
            position: relative;
        }

        .specialty-search {
            margin-bottom: 10px;
            position: relative;
        }

        .specialty-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .specialty-search input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-color);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .specialty-search input:focus {
            border-color: var(--accent-color);
            background: rgba(255, 255, 255, 0.08);
        }

        .specialty-list {
            max-height: 200px;
            overflow-y: auto;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .specialty-option {
            padding: 12px 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-color);
        }

        .specialty-option:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .specialty-option:last-child {
            border-bottom: none;
        }

        .selected-specialty {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            border-left: 4px solid var(--accent-glow);
        }

        .selected-specialty-display {
            margin-top: 15px;
            padding: 15px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            border-left: 4px solid var(--accent-color);
            animation: slideDown 0.3s ease;
        }

        .selected-specialty-display strong {
            color: var(--accent-glow);
        }

        .toggle-custom {
            color: var(--accent-color);
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .toggle-custom:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        .custom-specialty {
            margin-top: 15px;
            animation: slideDown 0.3s ease;
        }

        /* Lista de médicos melhorada */
        .doctor-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .doctor-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .doctor-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.5s;
        }

        .doctor-item:hover::before {
            left: 100%;
        }

        .doctor-item:hover {
            transform: translateX(5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .doctor-name {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        .doctor-crm {
            color: var(--text-secondary);
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }

        .doctor-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .doctor-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .doctor-detail i {
            width: 16px;
            color: var(--accent-color);
        }

        .doctor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .doctor-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 8px;
        }

        /* Badges melhoradas */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success-color), #16a34a);
            color: white;
        }

        .badge-info {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            font-size: 11px;
        }

        /* Estados vazios */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        /* Estatísticas rápidas */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            background: linear-gradient(135deg, var(--accent-glow), var(--gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Animações */
        @keyframes slideDown {
            from { 
                opacity: 0; 
                transform: translateY(-10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsividade */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .doctor-details {
                grid-template-columns: 1fr;
            }
            
            .doctor-footer {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .doctor-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-row {
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
                    <h1><i class="fas fa-user-md"></i> Gerenciar Médicos</h1>
                    <p>Cadastro e gestão de profissionais da saúde</p>
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

        <!-- Estatísticas Rápidas -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_medicos; ?></div>
                <div class="stat-label">Total de Médicos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($especialidades_unicas); ?></div>
                <div class="stat-label">Especialidades</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo date('m/Y'); ?></div>
                <div class="stat-label">Mês Atual</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Formulário de Cadastro -->
            <div class="form-container">
                <h3><i class="fas fa-user-plus"></i> Cadastrar Novo Médico</h3>
                <form method="POST" id="medicoForm">
                    <input type="hidden" name="action" value="cadastrar">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Dr. Nome Completo">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> CRM *</label>
                            <input type="text" name="crm" class="form-control" required placeholder="CRM/UF 000000" id="crm">
                            <small style="color: var(--text-secondary); font-size: 12px; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> Formato: CRM/UF 123456
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone *</label>
                            <input type="tel" name="telefone" class="form-control" required placeholder="(00) 00000-0000" id="telefone">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-stethoscope"></i> Especialidade Principal *</label>
                        <div class="specialty-container">
                            <div class="specialty-search">
                                <i class="fas fa-search"></i>
                                <input type="text" id="specialtySearch" placeholder="Buscar especialidade...">
                            </div>
                            <div class="specialty-list" id="specialtyList">
                                <?php foreach ($especialidades as $esp): ?>
                                    <div class="specialty-option" data-value="<?php echo htmlspecialchars($esp); ?>">
                                        <?php echo htmlspecialchars($esp); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="especialidade" id="selectedSpecialty" required>
                            <div class="selected-specialty-display" id="selectedSpecialtyDisplay" style="display: none;">
                                <strong>Especialidade selecionada:</strong> <span id="specialtyText"></span>
                            </div>
                            <div class="toggle-custom" onclick="toggleCustomSpecialty()">
                                <i class="fas fa-plus"></i>
                                Especialidade não encontrada? Clique para digitar
                            </div>
                            <div class="custom-specialty" id="customSpecialty">
                                <input type="text" class="form-control" id="customSpecialtyInput" placeholder="Digite a especialidade">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> E-mail *</label>
                        <input type="email" name="email" class="form-control" required placeholder="medico@clinica.com">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Endereço do Consultório</label>
                        <textarea name="endereco_consultorio" class="form-control" rows="3" placeholder="Endereço completo do consultório"></textarea>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Cadastrar Médico
                    </button>
                </form>
            </div>

            <!-- Lista de Médicos -->
            <div class="list-container">
                <h3><i class="fas fa-list"></i> Médicos Cadastrados</h3>
                <div class="doctor-list">
                    <?php if (count($medicos) > 0): ?>
                        <?php foreach ($medicos as $medico): ?>
                            <div class="doctor-item">
                                <div class="doctor-header">
                                    <div>
                                        <div class="doctor-name">Dr. <?php echo htmlspecialchars($medico['nome']); ?></div>
                                        <div class="doctor-crm"><?php echo htmlspecialchars($medico['crm']); ?></div>
                                    </div>
                                    <span class="badge badge-primary">
                                        <?php echo htmlspecialchars($medico['especialidade']); ?>
                                    </span>
                                </div>
                                
                                <div class="doctor-details">
                                    <div class="doctor-detail">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($medico['telefone']); ?></span>
                                    </div>
                                    <div class="doctor-detail">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($medico['email']); ?></span>
                                    </div>
                                    <?php if (!empty($medico['endereco_consultorio'])): ?>
                                    <div class="doctor-detail" style="grid-column: 1 / -1;">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($medico['endereco_consultorio']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="doctor-footer">
                                    <span class="badge badge-info">
                                        <i class="fas fa-calendar"></i>
                                        Cadastrado em: <?php echo date('d/m/Y', strtotime($medico['created_at'])); ?>
                                    </span>
                                    <div class="doctor-actions">
                                        <button class="btn-secondary btn-small">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn-secondary btn-small" style="background: rgba(239,68,68,0.1); color: var(--error-color);">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-md"></i>
                            <p>Nenhum médico cadastrado</p>
                            <p>Use o formulário ao lado para cadastrar o primeiro médico</p>
                        </div>
                    <?php endif; ?>
                </div>
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
            
            // Inicializar funcionalidades
            inicializarSelecaoEspecialidade();
            inicializarMascaras();
        });

        function inicializarSelecaoEspecialidade() {
            const searchInput = document.getElementById('specialtySearch');
            const specialtyList = document.getElementById('specialtyList');
            const specialtyOptions = specialtyList.querySelectorAll('.specialty-option');
            const selectedSpecialty = document.getElementById('selectedSpecialty');
            const selectedDisplay = document.getElementById('selectedSpecialtyDisplay');
            const specialtyText = document.getElementById('specialtyText');
            
            // Busca em tempo real
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                
                specialtyOptions.forEach(option => {
                    const text = option.textContent.toLowerCase();
                    if (text.includes(term)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            // Seleção de especialidade
            specialtyOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remover seleção anterior
                    specialtyOptions.forEach(opt => opt.classList.remove('selected-specialty'));
                    
                    // Selecionar atual
                    this.classList.add('selected-specialty');
                    
                    // Atualizar campos
                    const value = this.dataset.value;
                    selectedSpecialty.value = value;
                    specialtyText.textContent = value;
                    selectedDisplay.style.display = 'block';
                    
                    // Esconder especialidade customizada se estiver visível
                    document.getElementById('customSpecialty').style.display = 'none';
                    document.getElementById('customSpecialtyInput').value = '';
                    
                    // Efeito visual
                    this.style.animation = 'pulse 0.5s ease';
                    setTimeout(() => this.style.animation = '', 500);
                });
            });
            
            // Validação do formulário
            document.getElementById('medicoForm').addEventListener('submit', function(e) {
                if (!selectedSpecialty.value && !document.getElementById('customSpecialtyInput').value) {
                    e.preventDefault();
                    alert('Por favor, selecione ou digite uma especialidade.');
                    return false;
                }
                
                // Se especialidade customizada foi preenchida, usar ela
                if (document.getElementById('customSpecialtyInput').value) {
                    selectedSpecialty.value = document.getElementById('customSpecialtyInput').value;
                }
            });
        }

        function inicializarMascaras() {
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
            
            // Máscara para CRM
            const crmInput = document.getElementById('crm');
            if (crmInput) {
                crmInput.addEventListener('input', function(e) {
                    let value = e.target.value.toUpperCase();
                    // Formatar como CRM/UF 123456
                    value = value.replace(/[^A-Z0-9\/]/g, '');
                    e.target.value = value;
                });
            }
        }
        
        function toggleCustomSpecialty() {
            const customDiv = document.getElementById('customSpecialty');
            const isVisible = customDiv.style.display === 'block';
            
            customDiv.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Limpar seleção atual
                document.querySelectorAll('.specialty-option').forEach(opt => 
                    opt.classList.remove('selected-specialty'));
                document.getElementById('selectedSpecialty').value = '';
                document.getElementById('selectedSpecialtyDisplay').style.display = 'none';
                document.getElementById('specialtySearch').value = '';
                
                // Mostrar todas as opções novamente
                document.querySelectorAll('.specialty-option').forEach(opt => 
                    opt.style.display = 'block');
                
                // Focar no input customizado
                setTimeout(() => {
                    document.getElementById('customSpecialtyInput').focus();
                }, 100);
            }
        }
    </script>
</body>
</html>