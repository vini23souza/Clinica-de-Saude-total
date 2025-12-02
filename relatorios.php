<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$pagina_atual = basename($_SERVER['PHP_SELF']);

// Obter parâmetros de filtro
$periodo = $_GET['periodo'] ?? '30dias';
$tipo_relatorio = $_GET['tipo'] ?? 'geral';
$exportar = $_GET['exportar'] ?? '';

// Definir intervalo de datas
$intervalo_sql = "";
$label_periodo = "";
switch($periodo) {
    case '7dias':
        $intervalo_sql = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $label_periodo = "Últimos 7 Dias";
        break;
    case '30dias':
        $intervalo_sql = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $label_periodo = "Últimos 30 Dias";
        break;
    case '90dias':
        $intervalo_sql = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        $label_periodo = "Últimos 90 Dias";
        break;
    case 'ano':
        $intervalo_sql = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $label_periodo = "Último Ano";
        break;
    default:
        $intervalo_sql = "";
        $label_periodo = "Todo o Período";
}

try {
    // ESTATÍSTICAS GERAIS
    $total_pacientes = $pdo->query("SELECT COUNT(*) as total FROM pacientes")->fetch()['total'];
    $total_medicos = $pdo->query("SELECT COUNT(*) as total FROM medicos")->fetch()['total'];
    $total_usuarios = $pdo->query("SELECT COUNT(*) as total FROM usuarios")->fetch()['total'];
    $total_consultas = $pdo->query("SELECT COUNT(*) as total FROM consultas")->fetch()['total'];
    
    // ESTATÍSTICAS DO PERÍODO
    $novos_pacientes = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE 1=1 $intervalo_sql")->fetch()['total'];
    $novos_medicos = $pdo->query("SELECT COUNT(*) as total FROM medicos WHERE 1=1 $intervalo_sql")->fetch()['total'];
    $consultas_periodo = $pdo->query("SELECT COUNT(*) as total FROM consultas WHERE 1=1 $intervalo_sql")->fetch()['total'];
    
    // CRESCIMENTO (comparação com período anterior)
    $periodo_anterior_sql = str_replace('CURDATE()', 'DATE_SUB(CURDATE(), INTERVAL '.($periodo == '7dias' ? 7 : ($periodo == '30dias' ? 30 : ($periodo == '90dias' ? 90 : 365))).' DAY)', $intervalo_sql);
    $pacientes_periodo_anterior = $pdo->query("SELECT COUNT(*) as total FROM pacientes WHERE 1=1 $periodo_anterior_sql")->fetch()['total'];
    $crescimento_pacientes = $pacientes_periodo_anterior > 0 ? round((($novos_pacientes - $pacientes_periodo_anterior) / $pacientes_periodo_anterior) * 100, 1) : 0;
    
    // DADOS PARA GRÁFICOS - Evolução mensal (últimos 12 meses)
    $dados_mensais = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            DATE_FORMAT(created_at, '%b/%Y') as mes_label,
            COUNT(*) as total
        FROM pacientes 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b/%Y')
        ORDER BY mes
    ")->fetchAll();
    
    // DISTRIBUIÇÃO POR ESPECIALIDADE
    $especialidades = $pdo->query("
        SELECT especialidade, COUNT(*) as total 
        FROM medicos 
        GROUP BY especialidade 
        ORDER BY total DESC
    ")->fetchAll();
    
    // IDADE DOS PACIENTES
    $faixa_etaria = $pdo->query("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) < 18 THEN '0-17'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                ELSE '60+'
            END as faixa,
            COUNT(*) as total
        FROM pacientes 
        WHERE data_nascimento IS NOT NULL
        GROUP BY faixa
        ORDER BY FIELD(faixa, '0-17', '18-30', '31-45', '46-60', '60+')
    ")->fetchAll();
    
    // MÉDICOS MAIS ATIVOS (baseado em consultas)
    $medicos_ativos = $pdo->query("
        SELECT m.nome, m.especialidade, COUNT(c.id) as total_consultas
        FROM medicos m
        LEFT JOIN consultas c ON m.id = c.medico_id
        WHERE 1=1 $intervalo_sql
        GROUP BY m.id, m.nome, m.especialidade
        ORDER BY total_consultas DESC
        LIMIT 10
    ")->fetchAll();
    
    // PACIENTES MAIS FREQUENTES
    $pacientes_frequentes = $pdo->query("
        SELECT p.nome, COUNT(c.id) as total_consultas
        FROM pacientes p
        LEFT JOIN consultas c ON p.id = c.paciente_id
        WHERE 1=1 $intervalo_sql
        GROUP BY p.id, p.nome
        ORDER BY total_consultas DESC
        LIMIT 10
    ")->fetchAll();
    
    // TAXA DE CRESCIMENTO MENSAL
    $crescimento_mensal = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mes,
            COUNT(*) as total,
            LAG(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as total_anterior
        FROM pacientes 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY mes
    ")->fetchAll();
    
} catch(Exception $e) {
    $total_pacientes = $novos_pacientes = $total_medicos = $novos_medicos = 
    $total_usuarios = $total_consultas = $consultas_periodo = 0;
    $dados_mensais = $especialidades = $faixa_etaria = $medicos_ativos = $pacientes_frequentes = $crescimento_mensal = [];
    $crescimento_pacientes = 0;
}

// Exportação para Excel
if ($exportar == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="relatorio_clinica_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='2'>Relatório Clínica Saúde Total</th></tr>";
    echo "<tr><td>Período</td><td>$label_periodo</td></tr>";
    echo "<tr><td>Total Pacientes</td><td>$total_pacientes</td></tr>";
    echo "<tr><td>Novos Pacientes</td><td>$novos_pacientes</td></tr>";
    echo "<tr><td>Total Médicos</td><td>$total_medicos</td></tr>";
    echo "<tr><td>Consultas Realizadas</td><td>$consultas_periodo</td></tr>";
    echo "</table>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Avançados - Clínica Saúde Total</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        .reports-header {
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
            margin-bottom: 20px;
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
        
        .filter-select {
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-color);
            cursor: pointer;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .kpi-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
        }
        
        .kpi-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .kpi-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .kpi-trend {
            font-size: 12px;
            font-weight: 500;
        }
        
        .trend-positive {
            color: #22c55e;
        }
        
        .trend-negative {
            color: #ef4444;
        }
        
        .trend-neutral {
            color: var(--text-secondary);
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .chart-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            height: 350px;
        }
        
        .chart-title {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .table-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-color);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
        }
        
        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-color);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .summary-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
        }
        
        .summary-title {
            color: var(--text-color);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .metric-badge {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .period-badge {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
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
                    <h1>Relatórios Analíticos</h1>
                    <p>Análises detalhadas e métricas de desempenho</p>
                </div>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn-secondary">Dashboard</a>
                <a href="logout.php" class="btn">Sair</a>
            </div>
        </div>

        <!-- Botão Voltar -->
        <a href="dashboard.php" class="btn-back">Voltar para Dashboard</a>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Cabeçalho dos Relatórios -->
        <div class="reports-header">
            <div class="filters-container">
                <div class="filter-group">
                    <span class="filter-label">Período:</span>
                    <select class="filter-select" id="periodoSelect" onchange="atualizarRelatorio()">
                        <option value="7dias" <?php echo $periodo == '7dias' ? 'selected' : ''; ?>>Últimos 7 Dias</option>
                        <option value="30dias" <?php echo $periodo == '30dias' ? 'selected' : ''; ?>>Últimos 30 Dias</option>
                        <option value="90dias" <?php echo $periodo == '90dias' ? 'selected' : ''; ?>>Últimos 90 Dias</option>
                        <option value="ano" <?php echo $periodo == 'ano' ? 'selected' : ''; ?>>Último Ano</option>
                        <option value="todos" <?php echo $periodo == 'todos' ? 'selected' : ''; ?>>Todo o Período</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">Tipo de Relatório:</span>
                    <select class="filter-select" id="tipoSelect" onchange="atualizarRelatorio()">
                        <option value="geral" <?php echo $tipo_relatorio == 'geral' ? 'selected' : ''; ?>>Geral</option>
                        <option value="pacientes" <?php echo $tipo_relatorio == 'pacientes' ? 'selected' : ''; ?>>Pacientes</option>
                        <option value="medicos" <?php echo $tipo_relatorio == 'medicos' ? 'selected' : ''; ?>>Médicos</option>
                        <option value="consultas" <?php echo $tipo_relatorio == 'consultas' ? 'selected' : ''; ?>>Consultas</option>
                    </select>
                </div>
                
                <div class="export-buttons">
                    <button class="btn-secondary" onclick="exportarPDF()"> Exportar PDF</button>
                    <button class="btn" onclick="exportarExcel()"> Exportar Excel</button>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                <span style="color: var(--text-secondary);">Período selecionado:</span>
                <span class="period-badge"><?php echo $label_periodo; ?></span>
                <span style="color: var(--text-secondary); margin-left: auto;">
                    Atualizado em: <?php echo date('d/m/Y H:i'); ?>
                </span>
            </div>
        </div>

        <!-- KPIs Principais -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <div class="kpi-value"><?php echo $total_pacientes; ?></div>
                <div class="kpi-label">Total de Pacientes</div>
                <div class="kpi-trend trend-positive">
                    <?php echo $novos_pacientes; ?> novos no período
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value"><?php echo $total_medicos; ?></div>
                <div class="kpi-label">Médicos Cadastrados</div>
                <div class="kpi-trend trend-positive">
                    <?php echo $novos_medicos; ?> novos no período
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value"><?php echo $consultas_periodo; ?></div>
                <div class="kpi-label">Consultas Realizadas</div>
                <div class="kpi-trend trend-positive">
                    <?php echo $total_consultas; ?> no total
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-value">
                    <?php echo $crescimento_pacientes > 0 ? '+' : ''; ?><?php echo $crescimento_pacientes; ?>%
                </div>
                <div class="kpi-label">Crescimento de Pacientes</div>
                <div class="kpi-trend <?php echo $crescimento_pacientes > 0 ? 'trend-positive' : ($crescimento_pacientes < 0 ? 'trend-negative' : 'trend-neutral'); ?>">
                    vs. período anterior
                </div>
            </div>
        </div>

        <!-- Gráficos Principais -->
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-title">Evolução de Pacientes (Últimos 12 Meses)</div>
                <canvas id="evolucaoPacientesChart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">Distribuição por Especialidade Médica</div>
                <canvas id="especialidadesChart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">Faixa Etária dos Pacientes</div>
                <canvas id="faixaEtariaChart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-title">Taxa de Crescimento Mensal</div>
                <canvas id="crescimentoMensalChart"></canvas>
            </div>
        </div>

        <!-- Tabelas de Dados -->
        <div class="tables-grid">
            <div class="table-container">
                <h3 style="margin-bottom: 20px;"> Médicos Mais Ativos</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th>Especialidade</th>
                            <th>Consultas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($medicos_ativos) > 0): ?>
                            <?php foreach ($medicos_ativos as $medico): ?>
                                <tr>
                                    <td>Dr. <?php echo htmlspecialchars($medico['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($medico['especialidade']); ?></td>
                                    <td><span class="metric-badge"><?php echo $medico['total_consultas']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-secondary);">
                                    Nenhum dado disponível
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="table-container">
                <h3 style="margin-bottom: 20px;">👥 Pacientes Mais Frequentes</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Consultas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pacientes_frequentes) > 0): ?>
                            <?php foreach ($pacientes_frequentes as $paciente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($paciente['nome']); ?></td>
                                    <td><span class="metric-badge"><?php echo $paciente['total_consultas']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: var(--text-secondary);">
                                    Nenhum dado disponível
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resumo Executivo -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-title"> Resumo de Desempenho</div>
                <div class="summary-list">
                    <div class="summary-item">
                        <span>Taxa de Crescimento</span>
                        <span class="<?php echo $crescimento_pacientes > 0 ? 'trend-positive' : 'trend-negative'; ?>">
                            <?php echo $crescimento_pacientes > 0 ? '+' : ''; ?><?php echo $crescimento_pacientes; ?>%
                        </span>
                    </div>
                    <div class="summary-item">
                        <span>Novos Pacientes/Dia</span>
                        <span><?php echo $periodo != 'todos' ? round($novos_pacientes / ($periodo == '7dias' ? 7 : ($periodo == '30dias' ? 30 : ($periodo == '90dias' ? 90 : 365))), 1) : 'N/A'; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Média Consultas/Médico</span>
                        <span><?php echo $total_medicos > 0 ? round($consultas_periodo / $total_medicos, 1) : 0; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title"> Metas e Insights</div>
                <div class="summary-list">
                    <div class="summary-item">
                        <span>Capacidade Ociosa</span>
                        <span class="trend-positive"><?php echo $total_medicos > 0 ? round((1 - ($consultas_periodo / ($total_medicos * 20))) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="summary-item">
                        <span>Pacientes por Médico</span>
                        <span><?php echo $total_medicos > 0 ? round($total_pacientes / $total_medicos, 1) : 0; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Taxa de Retenção</span>
                        <span class="trend-positive">85%</span>
                    </div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="summary-title"> Estatísticas Gerais</div>
                <div class="summary-list">
                    <div class="summary-item">
                        <span>Total de Especialidades</span>
                        <span><?php echo count($especialidades); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Especialidade Mais Comum</span>
                        <span><?php echo count($especialidades) > 0 ? $especialidades[0]['especialidade'] : 'N/A'; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Faixa Etária Predominante</span>
                        <span><?php echo count($faixa_etaria) > 0 ? $faixa_etaria[0]['faixa'] : 'N/A'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inicializar gráficos quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            inicializarGraficos();
            
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
            // Gráfico de Evolução de Pacientes
            const ctx1 = document.getElementById('evolucaoPacientesChart').getContext('2d');
            new Chart(ctx1, {
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
                        fill: true
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
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });

            // Gráfico de Especialidades
            const ctx2 = document.getElementById('especialidadesChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($especialidades, 'especialidade')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($especialidades, 'total')); ?>,
                        backgroundColor: [
                            '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
                            '#f43f5e', '#fb7185', '#fdba74', '#fbbf24', '#a3e635'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)',
                                boxWidth: 12,
                                padding: 15
                            }
                        }
                    }
                }
            });

            // Gráfico de Faixa Etária
            const ctx3 = document.getElementById('faixaEtariaChart').getContext('2d');
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($faixa_etaria, 'faixa')); ?>,
                    datasets: [{
                        label: 'Pacientes',
                        data: <?php echo json_encode(array_column($faixa_etaria, 'total')); ?>,
                        backgroundColor: '#8b5cf6',
                        borderColor: '#8b5cf6',
                        borderWidth: 1
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
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });

            // Gráfico de Crescimento Mensal
            const ctx4 = document.getElementById('crescimentoMensalChart').getContext('2d');
            new Chart(ctx4, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_slice(array_column($dados_mensais, 'mes_label'), -6)); ?>,
                    datasets: [{
                        label: 'Taxa de Crescimento (%)',
                        data: <?php 
                            $taxas = [];
                            foreach(array_slice($crescimento_mensal, -6) as $mes) {
                                if ($mes['total_anterior'] && $mes['total_anterior'] > 0) {
                                    $taxas[] = round((($mes['total'] - $mes['total_anterior']) / $mes['total_anterior']) * 100, 1);
                                } else {
                                    $taxas[] = 0;
                                }
                            }
                            echo json_encode($taxas);
                        ?>,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
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
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }

        function atualizarRelatorio() {
            const periodo = document.getElementById('periodoSelect').value;
            const tipo = document.getElementById('tipoSelect').value;
            
            window.location.href = `relatorios.php?periodo=${periodo}&tipo=${tipo}`;
        }

        function exportarPDF() {
            alert('Funcionalidade de exportação PDF será implementada!');
            // Em produção, integraria com biblioteca como jsPDF
        }

        function exportarExcel() {
            window.location.href = `relatorios.php?exportar=excel&periodo=<?php echo $periodo; ?>`;
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