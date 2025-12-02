#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import json
import base64
from datetime import datetime

def calcular_idade(data_nascimento):
    """Calcula idade a partir da data de nascimento"""
    try:
        nasc = datetime.strptime(data_nascimento, '%Y-%m-%d')
        hoje = datetime.now()
        idade = hoje.year - nasc.year - ((hoje.month, hoje.day) < (nasc.month, nasc.day))
        return idade
    except:
        return 0

def analisar_paciente(dados):
    """Analisa dados de um paciente"""
    
    paciente = dados.get('paciente', {})
    
    # Informações básicas
    idade = calcular_idade(paciente.get('data_nascimento', ''))
    
    # Verifica dados completos
    tem_cpf = bool(paciente.get('cpf'))
    tem_email = bool(paciente.get('email'))
    tem_telefone = bool(paciente.get('telefone'))
    tem_endereco = bool(paciente.get('endereco'))
    
    # Categoriza por idade
    if idade < 18:
        faixa_etaria = 'infantil'
        prioridade = 'alta'
    elif idade < 30:
        faixa_etaria = 'jovem_adulto'
        prioridade = 'normal'
    elif idade < 60:
        faixa_etaria = 'adulto'
        prioridade = 'normal'
    else:
        faixa_etaria = 'idoso'
        prioridade = 'alta'
    
    # Tipo de cadastro
    if all([tem_email, tem_telefone, tem_endereco]):
        tipo_cadastro = 'completo'
    elif tem_email or tem_telefone:
        tipo_cadastro = 'parcial'
    else:
        tipo_cadastro = 'basico'
    
    # Gera alertas
    alertas = []
    if not tem_email:
        alertas.append('Paciente não possui e-mail cadastrado')
    if not tem_telefone:
        alertas.append('Paciente não possui telefone cadastrado')
    
    # Calcula risco (simulação)
    if idade > 60:
        nivel_risco = 'alto'
        pontuacao = 7
    elif idade > 40:
        nivel_risco = 'moderado'
        pontuacao = 4
    else:
        nivel_risco = 'baixo'
        pontuacao = 2
    
    # Gera recomendações baseadas na idade
    recomendacoes = []
    if idade >= 60:
        recomendacoes.extend([
            'Agendar check-up geriátrico',
            'Monitorar pressão arterial regularmente'
        ])
    elif idade >= 40:
        recomendacoes.extend([
            'Realizar check-up preventivo anual',
            'Manter atividade física regular'
        ])
    
    # Resultado da análise
    resultado = {
        'paciente_id': paciente.get('id', 0),
        'nome': paciente.get('nome', 'Desconhecido'),
        'idade': idade,
        'data_analise': datetime.now().isoformat(),
        'estatisticas': {
            'tem_cpf': tem_cpf,
            'tem_email': tem_email,
            'tem_telefone': tem_telefone,
            'tem_endereco': tem_endereco
        },
        'categorias': {
            'faixa_etaria': faixa_etaria,
            'prioridade': prioridade,
            'tipo_cadastro': tipo_cadastro
        },
        'alertas': alertas,
        'risco_saude': {
            'nivel_risco': nivel_risco,
            'pontuacao': pontuacao,
            'recomendacao_risco': f'Necessita acompanhamento {nivel_risco}'
        },
        'recomendacoes': recomendacoes
    }
    
    return resultado

def main():
    """Função principal"""
    try:
        if len(sys.argv) > 1:
            # Decodifica dados
            encoded_data = sys.argv[1]
            json_data = base64.b64decode(encoded_data).decode('utf-8')
            dados = json.loads(json_data)
            
            if dados.get('acao') == 'analisar_paciente':
                resultado = analisar_paciente(dados)
                print(json.dumps({'analise': resultado}, ensure_ascii=False))
            else:
                print(json.dumps({'erro': 'Ação não reconhecida'}))
                
    except Exception as e:
        print(json.dumps({'erro': str(e), 'tipo': 'Python Error'}))

if __name__ == '__main__':
    main()