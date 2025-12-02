#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import json
import base64
from datetime import datetime

def calcular_estatisticas(pacientes):
    """Calcula estatísticas dos pacientes"""
    
    total = len(pacientes)
    if total == 0:
        return {'total_pacientes': 0, 'mensagem': 'Nenhum paciente cadastrado'}
    
    # Calcula idades
    idades = []
    for paciente in pacientes:
        try:
            nasc = datetime.strptime(paciente['data_nascimento'], '%Y-%m-%d')
            hoje = datetime.now()
            idade = hoje.year - nasc.year - ((hoje.month, hoje.day) < (nasc.month, nasc.day))
            idades.append(idade)
        except:
            continue
    
    if not idades:
        return {'total_pacientes': total, 'mensagem': 'Não foi possível calcular idades'}
    
    # Estatísticas básicas
    idade_media = sum(idades) / len(idades)
    
    # Distribuição por faixa etária
    criancas = sum(1 for i in idades if i < 12)
    adolescentes = sum(1 for i in idades if 12 <= i < 18)
    adultos_jovens = sum(1 for i in idades if 18 <= i < 30)
    adultos = sum(1 for i in idades if 30 <= i < 60)
    idosos = sum(1 for i in idades if i >= 60)
    
    # Pacientes com dados completos
    completos = sum(1 for p in pacientes if all([
        p.get('cpf'), 
        p.get('email'), 
        p.get('telefone'), 
        p.get('endereco')
    ]))
    
    resultado = {
        'total_pacientes': total,
        'total_idades_calculadas': len(idades),
        'idade_media': round(idade_media, 1),
        'idade_minima': min(idades),
        'idade_maxima': max(idades),
        'distribuicao': {
            'criancas': criancas,
            'adolescentes': adolescentes,
            'adultos_jovens': adultos_jovens,
            'adultos': adultos,
            'idosos': idosos
        },
        'cadastros_completos': completos,
        'percentual_completos': round((completos / total) * 100, 1),
        'data_analise': datetime.now().isoformat()
    }
    
    return resultado

def main():
    """Função principal"""
    try:
        if len(sys.argv) > 1:
            encoded_data = sys.argv[1]
            json_data = base64.b64decode(encoded_data).decode('utf-8')
            dados = json.loads(json_data)
            
            if dados.get('acao') == 'estatisticas':
                pacientes = dados.get('pacientes', [])
                estatisticas = calcular_estatisticas(pacientes)
                print(json.dumps(estatisticas, ensure_ascii=False))
            else:
                print(json.dumps({'erro': 'Ação não reconhecida'}))
                
    except Exception as e:
        print(json.dumps({'erro': str(e)}))

if __name__ == '__main__':
    main()