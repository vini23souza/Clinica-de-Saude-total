#!/usr/bin/env python3

import json
import base64
import sys
from datetime import datetime, timedelta

def gerar_relatorio_html(dados):
    """Gera relatório em HTML formatado"""
    
    filtros = dados.get('filtros', {})
    periodo = filtros.get('periodo', 'mensal')
    
    html = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {{ font-family: Arial, sans-serif; margin: 20px; }}
            .header {{ background: #2c3e50; color: white; padding: 20px; }}
            .section {{ margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; }}
            .alert {{ background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; }}
            .stats {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }}
            .stat-card {{ background: #f8f9fa; padding: 15px; border-radius: 5px; }}
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📊 Relatório Clínico</h1>
            <p>Gerado em: {datetime.now().strftime('%d/%m/%Y %H:%M')}</p>
            <p>Período: {periodo}</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>👥 Pacientes Ativos</h3>
                <p style="font-size: 24px; color: #2c3e50;">142</p>
            </div>
            <div class="stat-card">
                <h3>🏥 Consultas Realizadas</h3>
                <p style="font-size: 24px; color: #27ae60;">328</p>
            </div>
            <div class="stat-card">
                <h3>📅 Agendamentos</h3>
                <p style="font-size: 24px; color: #e74c3c;">45</p>
            </div>
        </div>
        
        <div class="section">
            <h2>📈 Tendências</h2>
            <p>• Aumento de 15% em consultas de pediatria</p>
            <p>• Redução de 8% em cancelamentos</p>
            <p>• Tempo médio de espera: 18 minutos</p>
        </div>
        
        <div class="section alert">
            <h2>⚠️ Alertas</h2>
            <p>• 3 pacientes com exames pendentes há mais de 30 dias</p>
            <p>• 2 medicamentos com estoque baixo</p>
            <p>• 1 médico com agenda sobrecarregada</p>
        </div>
    </body>
    </html>
    """
    
    return {
        'sucesso': True,
        'formato': 'html',
        'conteudo': html,
        'tamanho': len(html)
    }

def main():
    if len(sys.argv) > 1:
        dados_encoded = sys.argv[1]
        dados_json = base64.b64decode(dados_encoded).decode('utf-8')
        dados = json.loads(dados_json)
        
        resultado = gerar_relatorio_html(dados)
        print(json.dumps(resultado))

if __name__ == '__main__':
    main()