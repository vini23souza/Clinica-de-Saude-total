<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// Obter filtros da URL ou usar padrões
$periodo = $_GET['periodo'] ?? '30dias';
$tipo = $_GET['tipo'] ?? 'todos';

// Buscar estatísticas com filtros
try {
    // Definir intervalo de datas baseado no período
    $intervalo = '';
    switch($periodo) {
        case '7dias':
            $intervalo = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            $label_periodo = "Últimos 7 Dias";
            break;
        case '30dias':
            $intervalo = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $label_periodo = "Últimos 30 Dias";
            break;
        case '90dias':
            $intervalo = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
            $label_periodo = "Últimos 90 Dias";
            break;
        case 'ano':
            $intervalo = "AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            $label_periodo = "Último Ano";
            break;
        default:
            $intervalo = "";
            $label_periodo = "Todo o Período";
    }
    
    // Estatísticas principais
    $total_pacientes = $pdo->query("SELECT COUNT(*) as total FROM pacientes")->fetch()['total'];
    $novos_pacientes = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE 1=1 $intervalo")->fetch()['total'];
    
    $total_medicos = $pdo->query("SELECT COUNT(*) as total FROM medicos")->fetch()['total'];
    $novos_medicos = $pdo->query("SELECT COUNT(*) as total FROM medicos WHERE 1=1 $intervalo")->fetch()['total'];
    
    $total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
    $total_consultas = $pdo->query("SELECT COUNT(*) as total FROM consultas")->fetch()['total'];
    $consultas_periodo = $pdo->query("SELECT COUNT(*) as total FROM consultas WHERE 1=1 $intervalo")->fetch()['total'];
    
    // Calcular crescimento vs período anterior
    $periodo_anterior_sql = str_replace('CURDATE()', 'DATE_SUB(CURDATE(), INTERVAL '.($periodo == '7dias' ? 14 : ($periodo == '30dias' ? 60 : ($periodo == '90dias' ? 180 : 730))).' DAY)', $intervalo);
    $pacientes_periodo_anterior = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE 1=1 $periodo_anterior_sql")->fetch()['total'];
    $crescimento_pacientes = $pacientes_periodo_anterior > 0 ? round((($novos_pacientes - $pacientes_periodo_anterior) / $pacientes_periodo_anterior) * 100, 1) : ($novos_pacientes > 0 ? 100 : 0);
    
    // Dados para gráficos
    $dados_mensais = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            DATE_FORMAT(created_at, '%b/%Y') as mes_label,
            COUNT(*) as total
        FROM pacientes 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b/%Y')
        ORDER BY mes
    ")->fetchAll();
    
    // Especialidades mais comuns
    $especialidades_top = $pdo->query("
        SELECT especialidade, COUNT(*) as total 
        FROM medicos 
        GROUP BY especialidade 
        ORDER BY total DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Atividade recente
    $atividades_recentes = $pdo->query("
        (SELECT 'paciente' as tipo, nome as descricao, created_at 
         FROM pacientes 
         ORDER BY created_at DESC 
         LIMIT 3)
        UNION ALL
        (SELECT 'medico' as tipo, nome as descricao, created_at 
         FROM medicos 
         ORDER BY created_at DESC 
         LIMIT 2)
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Estatísticas de performance
    $taxa_ocupacao = $total_medicos > 0 ? round(($consultas_periodo / ($total_medicos * 20)) * 100, 1) : 0;
    $pacientes_por_medico = $total_medicos > 0 ? round($total_pacientes / $total_medicos, 1) : 0;
    $consultas_por_medico = $total_medicos > 0 ? round($consultas_periodo / $total_medicos, 1) : 0;
    
} catch(Exception $e) {
    $total_pacientes = $novos_pacientes = $total_medicos = $novos_medicos = 
    $total_usuarios = $total_consultas = $consultas_periodo = 0;
    $dados_mensais = $especialidades_top = $atividades_recentes = [];
    $crescimento_pacientes = $taxa_ocupacao = $pacientes_por_medico = $consultas_por_medico = 0;
}

$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clínica Saúde Total</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-header {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .filters-container {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }
        
        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            border-color: transparent;
        }
        
        .period-badge {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
        }
        
        .kpi-main {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .kpi-value {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--accent-glow), var(--gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        
        .kpi-trend {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
        
        .trend-up {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .trend-down {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .trend-neutral {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
        }
        
        .kpi-label {
            color: var(--text-color);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .kpi-description {
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.4;
        }
        
        .kpi-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .comparison-label {
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .comparison-value {
            color: var(--text-color);
            font-size: 13px;
            font-weight: 600;
        }
        
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .main-chart {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            height: 400px;
        }
        
        .side-cards {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .side-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            flex: 1;
        }
        
        .side-card-title {
            color: var(--text-color);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .specialty-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .specialty-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .specialty-item:last-child {
            border-bottom: none;
        }
        
        .specialty-name {
            color: var(--text-color);
            font-size: 13px;
        }
        
        .specialty-count {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .activity-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(5px);
        }
        
        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .icon-paciente {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
        }
        
        .icon-medico {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            color: var(--text-color);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .activity-time {
            color: var(--text-secondary);
            font-size: 11px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .metric-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .loading-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .section-title {
            color: var(--text-color);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="background-effects">
        <div class="gradient-ball-1"></div>
        <div class="gradient-ball-2"></div>
        <div class="gradient-ball-3"></div>
    </div>

    <!-- Menu Lateral -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Clínica Saúde Total</h3>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?php echo $pagina_atual == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
            
            <a href="pacientes.php" class="nav-item <?php echo $pagina_atual == 'pacientes.php' ? 'active' : ''; ?>">
                Pacientes
            </a>
            
            <a href="medicos.php" class="nav-item <?php echo $pagina_atual == 'medicos.php' ? 'active' : ''; ?>">
                Médicos
            </a>
            
            <a href="relatorios.php" class="nav-item <?php echo $pagina_atual == 'relatorios.php' ? 'active' : ''; ?>">
                Relatórios
            </a>
            
            <a href="cadastro_usuario.php" class="nav-item <?php echo $pagina_atual == 'cadastro_usuario.php' ? 'active' : ''; ?>">
                Usuários
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="logout.php" class="nav-item">
                    Sair
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
                <button class="menu-toggle" id="menuToggle">☰</button>
                <div class="header-content">
                    <h1>Dashboard Executivo</h1>
                    <p>Visão geral e métricas de desempenho</p>
                </div>
            </div>
            <div class="user-info">
                <span>Olá, <strong><?php echo $_SESSION['nome_completo'] ?? $_SESSION['username']; ?></strong></span>
                <span class="badge badge-primary"><?php echo ucfirst($_SESSION['user_type']); ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <!-- Filtros e Período -->
        <div class="dashboard-header">
            <div class="filters-container">
                <div class="filter-group">
                    <span class="filter-label">Período:</span>
                    <button class="filter-btn periodo-filter <?php echo $periodo == '7dias' ? 'active' : ''; ?>" data-periodo="7dias">7 Dias</button>
                    <button class="filter-btn periodo-filter <?php echo $periodo == '30dias' ? 'active' : ''; ?>" data-periodo="30dias">30 Dias</button>
                    <button class="filter-btn periodo-filter <?php echo $periodo == '90dias' ? 'active' : ''; ?>" data-periodo="90dias">90 Dias</button>
                    <button class="filter-btn periodo-filter <?php echo $periodo == 'ano' ? 'active' : ''; ?>" data-periodo="ano">1 Ano</button>
                </div>
                
                <div style="margin-left: auto; display: flex; align-items: center; gap: 15px;">
                    <span style="color: var(--text-secondary); font-size: 14px;">Período:</span>
                    <span class="period-badge"><?php echo $label_periodo; ?></span>
                </div>
            </div>
            
            <div style="color: var(--text-secondary); font-size: 13px;">
                Dados atualizados em tempo real • Última atualização: <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading" id="loadingIndicator">
            <div class="loading-spinner"></div>
            <div>Atualizando métricas...</div>
        </div>

        <!-- KPIs Principais -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-value"><?php echo $total_pacientes; ?></div>
                    <div class="kpi-trend <?php echo $crescimento_pacientes > 0 ? 'trend-up' : ($crescimento_pacientes < 0 ? 'trend-down' : 'trend-neutral'); ?>">
                        <?php echo $crescimento_pacientes > 0 ? '+' : ''; ?><?php echo $crescimento_pacientes; ?>%
                    </div>
                </div>
                <div class="kpi-label">Total de Pacientes</div>
                <div class="kpi-description">Cadastrados no sistema</div>
                <div class="kpi-comparison">
                    <span class="comparison-label">Novos no período</span>
                    <span class="comparison-value">+<?php echo $novos_pacientes; ?></span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-value"><?php echo $total_medicos; ?></div>
                    <div class="kpi-trend trend-up">+<?php echo $novos_medicos; ?></div>
                </div>
                <div class="kpi-label">Médicos Cadastrados</div>
                <div class="kpi-description">Profissionais ativos</div>
                <div class="kpi-comparison">
                    <span class="comparison-label">Novos no período</span>
                    <span class="comparison-value">+<?php echo $novos_medicos; ?></span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-main">
                    <div class="kpi-value"><?php echo $consultas_periodo; ?></div>
                    <div class="kpi-trend trend-up">+15%</div>
                </div>
                <div class="kpi-label">Consultas Realizadas</div>
                <div class="kpi-description">No período selecionado</div>
                <div class="kpi-comparison">
                    <span class="comparison-label">Total geral</span>
                    <span class="comparison-value"><?php echo $total_consultas; ?></span>
                </div>
            </div>
        </div>

        <!-- Gráficos e Side Cards -->
        <div class="charts-section">
            <div class="main-chart">
                <div class="section-title">Evolução de Pacientes</div>
                <canvas id="evolucaoPacientesChart"></canvas>
            </div>
            
            <div class="side-cards">
                <div class="side-card">
                    <div class="side-card-title">Especialidades em Destaque</div>
                    <div class="specialty-list">
                        <?php if (count($especialidades_top) > 0): ?>
                            <?php foreach ($especialidades_top as $especialidade): ?>
                                <div class="specialty-item">
                                    <span class="specialty-name"><?php echo htmlspecialchars($especialidade['especialidade']); ?></span>
                                    <span class="specialty-count"><?php echo $especialidade['total']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-secondary); padding: 20px;">
                                Nenhuma especialidade cadastrada
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="side-card">
                    <div class="side-card-title">Atividade Recente</div>
                    <div class="activity-list">
                        <?php if (count($atividades_recentes) > 0): ?>
                            <?php foreach ($atividades_recentes as $atividade): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $atividade['tipo'] == 'paciente' ? 'icon-paciente' : 'icon-medico'; ?>">
                                        <?php echo $atividade['tipo'] == 'paciente' ? 'P' : 'M'; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <?php echo $atividade['tipo'] == 'paciente' ? 'Novo paciente' : 'Novo médico'; ?> cadastrado
                                        </div>
                                        <div class="activity-time">
                                            <?php echo date('d/m H:i', strtotime($atividade['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-secondary); padding: 20px;">
                                Nenhuma atividade recente
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Métricas de Performance -->
        <div class="section-title">Métricas de Performance</div>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $taxa_ocupacao; ?>%</div>
                <div class="metric-label">Taxa de Ocupação</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value"><?php echo $pacientes_por_medico; ?></div>
                <div class="metric-label">Pacientes por Médico</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value"><?php echo $consultas_por_medico; ?></div>
                <div class="metric-label">Consultas por Médico</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value"><?php echo $total_usuarios; ?></div>
                <div class="metric-label">Usuários do Sistema</div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="section-title">Ações Rápidas</div>
        <div class="actions-grid">
            <div class="card">
                <h3>Gerenciar Pacientes</h3>
                <p>Cadastre, edite e visualize informações dos pacientes da clínica.</p>
                <a href="pacientes.php" class="btn">Acessar Pacientes</a>
            </div>

            <div class="card">
                <h3>Gerenciar Médicos</h3>
                <p>Administre o cadastro de profissionais de saúde e especialidades.</p>
                <a href="medicos.php" class="btn">Acessar Médicos</a>
            </div>

            <div class="card">
                <h3>Relatórios Detalhados</h3>
                <p>Visualize relatórios completos e estatísticas avançadas.</p>
                <a href="relatorios.php" class="btn">Ver Relatórios</a>
            </div>
        </div>
    </div>

    <script>
        // Configuração dos filtros
        const filters = {
            periodo: '<?php echo $periodo; ?>'
        };

        // Inicializar gráficos
        let evolucaoChart;

        document.addEventListener('DOMContentLoaded', function() {
            inicializarGraficos();
            inicializarFiltros();
            
            // Menu mobile toggle
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
        });

        function inicializarGraficos() {
            // Gráfico de evolução de pacientes
            const ctx = document.getElementById('evolucaoPacientesChart').getContext('2d');
            evolucaoChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($dados_mensais, 'mes_label')); ?>,
                    datasets: [{
                        label: 'Novos Pacientes',
                        data: <?php echo json_encode(array_column($dados_mensais, 'total')); ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            });
        }

        function inicializarFiltros() {
            // Filtros por período
            document.querySelectorAll('.periodo-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    filters.periodo = this.dataset.periodo;
                    
                    // Atualizar estado visual
                    document.querySelectorAll('.periodo-filter').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    atualizarDashboard();
                });
            });
        }

        function atualizarDashboard() {
            const loading = document.getElementById('loadingIndicator');
            loading.style.display = 'block';
            
            // Simular requisição AJAX
            setTimeout(() => {
                window.location.href = `dashboard.php?periodo=${filters.periodo}`;
            }, 800);
        }

        // Fechar menu ao clicar em um link (mobile)
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.sidebar').classList.remove('active');
                    document.getElementById('overlay').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>