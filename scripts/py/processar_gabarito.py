import cv2
import numpy as np
import json
import sys
import math
import os
from pyzbar.pyzbar import decode

# Função para processar o gabarito
def processar_gabarito(caminho_imagem, num_questoes, num_alternativas):
    # Validar os parâmetros
    if not (1 <= num_questoes <= 75):
        raise Exception(f"Número de questões inválido: {num_questoes}. Deve estar entre 1 e 75.")
    if num_alternativas not in [4, 5]:
        raise Exception(f"Número de alternativas inválido: {num_alternativas}. Deve ser 4 ou 5.")

    # Carregar a imagem
    imagem = cv2.imread(caminho_imagem)
    if imagem is None:
        raise Exception("Não foi possível carregar a imagem")

    # --- Leitura do QR Code ---
    qr_code_data = None
    try:
        qr_codes = decode(imagem)
        if qr_codes:
            qr_code = qr_codes[0]
            qr_code_data = qr_code.data.decode('utf-8')
        else:
            qr_code_data = "Nenhum QR code encontrado"
    except Exception as e:
        qr_code_data = f"Erro ao ler o QR code: {str(e)}"
        
    # --- Corte das bordas da imagem ---
    altura_imagem, largura_imagem = imagem.shape[:2]
    margem = 0.01
    x_inicio = int(largura_imagem * margem)
    y_inicio = int(altura_imagem * margem)
    x_fim = int(largura_imagem * (1 - margem))
    y_fim = int(altura_imagem * (1 - margem))
    imagem_cortada = imagem[y_inicio:y_fim, x_inicio:x_fim]
    altura_imagem, largura_imagem = imagem_cortada.shape[:2]

    # --- Processamento do Gabarito ---
    percentual_cabecalho = 0.15
    limite_y_cabecalho = int(altura_imagem * percentual_cabecalho)

    # Criar uma cópia da imagem original para marcar o cabeçalho
    imagem_cabecalho = imagem.copy()
    # Desenhar a região do cabeçalho (15%) na imagem original
    cv2.rectangle(
        imagem_cabecalho,
        (x_inicio, y_inicio),  # Canto superior esquerdo
        (x_fim, y_inicio + limite_y_cabecalho),  # Canto inferior direito
        (255, 0, 0),  # Cor azul (BGR)
        2  # Espessura da linha
    )

    # Converter para escala de cinza
    cinza = cv2.cvtColor(imagem_cortada, cv2.COLOR_BGR2GRAY)

    # Aplicar desfoque para reduzir ruído
    desfoque = cv2.GaussianBlur(cinza, (5, 5), 0)

    # Detectar círculos usando HoughCircles
    circulos = cv2.HoughCircles(
        desfoque,
        cv2.HOUGH_GRADIENT,
        dp=1,
        # minDist=20,
        # param1=50,
        # param2=30,
        # minRadius=5,
        # maxRadius=50
        
        minDist=10,  # Reduzir para detectar círculos próximos
        param1=50,
        param2=25,   # Reduzir para ser menos restritivo
        minRadius=5, # Ajustar com base no tamanho dos círculos
        maxRadius=20 # Limitar para evitar círculos grandes

    )

    # Lista para armazenar os círculos detectados
    circulos_lista = []
    if circulos is not None:
        circulos = np.round(circulos[0, :]).astype("int")
        for (x, y, r) in circulos:
            # Ajustar coordenadas para a imagem original
            x_adjusted = x + x_inicio
            y_adjusted = y + y_inicio
            circulos_lista.append((x_adjusted, y_adjusted, r))

    # Ordenar os círculos por posição (y para linhas, x para colunas)
    circulos_lista = sorted(circulos_lista, key=lambda x: (x[1], x[0]))

    # Ignorar círculos do cabeçalho
    if not circulos_lista:
        raise Exception("Nenhum círculo detectado na imagem")

    # Filtrar os círculos, descartando os que estão no cabeçalho
    circulos_questoes = [circ for circ in circulos_lista if circ[1] >= (limite_y_cabecalho + y_inicio)]

    # Criar uma imagem de depuração para desenhar todos os círculos detectados
    imagem_debug = imagem.copy()
    # Desenhar todos os círculos detectados (antes de filtrar o cabeçalho) em vermelho
    for (x, y, r) in circulos_lista:
        cv2.circle(imagem_debug, (x, y), r, (0, 0, 255), 1)  # Vermelho, espessura 1
    # Desenhar os círculos filtrados (após ignorar o cabeçalho) em verde
    for (x, y, r) in circulos_questoes:
        cv2.circle(imagem_debug, (x, y), r, (0, 255, 0), 2)  # Verde, espessura 2

    # Salvar a imagem de depuração
    diretorio_saida = os.path.dirname(caminho_imagem)
    nome_arquivo = os.path.basename(caminho_imagem)
    nome_sem_extensao = os.path.splitext(nome_arquivo)[0]
    caminho_saida_debug = os.path.join(diretorio_saida, f"{nome_sem_extensao}_debug_circulos.png")
    if cv2.imwrite(caminho_saida_debug, imagem_debug):
        print(f"Imagem de depuração com círculos salva em: {caminho_saida_debug}")
    else:
        # Tentar salvar em /tmp como fallback
        caminho_saida_fallback_debug = f"/tmp/{nome_sem_extensao}_debug_circulos.png"
        if cv2.imwrite(caminho_saida_fallback_debug, imagem_debug):
            print(f"Imagem de depuração com círculos salva em: {caminho_saida_fallback_debug} (fallback devido a problema de permissão)")
        else:
            print(f"Erro ao salvar a imagem de depuração em {caminho_saida_debug} e {caminho_saida_fallback_debug}")

    # Verificar o número de círculos após ignorar o cabeçalho
    num_circulos_esperados = num_questoes * num_alternativas
    if len(circulos_questoes) != num_circulos_esperados:
        raise Exception(f"Número de círculos detectados após ignorar o cabeçalho ({len(circulos_questoes)}) não é compatível com {num_questoes} questões e {num_alternativas} alternativas")

    # Usar apenas os círculos das questões
    circulos = circulos_questoes

    # Desenhar os círculos detectados na imagem do cabeçalho
    for (x, y, r) in circulos:
        cv2.circle(imagem_cabecalho, (x, y), r, (0, 255, 0), 2)

    # Salvar a imagem com a marcação do cabeçalho e círculos
    caminho_saida_cabecalho = os.path.join(diretorio_saida, f"{nome_sem_extensao}_cabecalho.png")
    if cv2.imwrite(caminho_saida_cabecalho, imagem_cabecalho):
        print(f"Imagem com cabeçalho e círculos salva em: {caminho_saida_cabecalho}")
    else:
        # Tentar salvar em /tmp como fallback
        caminho_saida_fallback_cabecalho = f"/tmp/{nome_sem_extensao}_cabecalho.png"
        if cv2.imwrite(caminho_saida_fallback_cabecalho, imagem_cabecalho):
            print(f"Imagem com cabeçalho e círculos salva em: {caminho_saida_fallback_cabecalho} (fallback devido a problema de permissão)")
        else:
            raise Exception(f"Erro ao salvar a imagem em {caminho_saida_cabecalho} e {caminho_saida_fallback_cabecalho}")

    # Determinar o número de blocos e questões por bloco
    num_questoes_por_bloco = 25
    num_blocos = math.ceil(num_questoes / num_questoes_por_bloco)

    # Calcular o número de questões por bloco
    questoes_por_bloco = []
    questoes_restantes = num_questoes
    for bloco_idx in range(num_blocos):
        num_questoes_bloco = min(num_questoes_por_bloco, questoes_restantes)
        questoes_por_bloco.append(num_questoes_bloco)
        questoes_restantes -= num_questoes_bloco

    # Dividir os círculos em grupos (um para cada bloco)
    circulos_por_bloco = [[] for _ in range(num_blocos)]
    idx_circulo = 0
    for linha_idx in range(max(questoes_por_bloco)):
        blocos_ativos = []
        for bloco_idx in range(num_blocos):
            if linha_idx < questoes_por_bloco[bloco_idx]:
                blocos_ativos.append(bloco_idx)

        if not blocos_ativos:
            break

        num_circulos_linha = len(blocos_ativos) * num_alternativas
        linha = circulos[idx_circulo:idx_circulo + num_circulos_linha]
        if len(linha) != num_circulos_linha:
            raise Exception(f"Linha {linha_idx + 1} tem {len(linha)} círculos, mas esperava {num_circulos_linha}")

        linha_ordenada = sorted(linha, key=lambda x: x[0])

        for i, bloco_idx in enumerate(blocos_ativos):
            circulos_por_bloco[bloco_idx].extend(linha_ordenada[i * num_alternativas:(i + 1) * num_alternativas])

        idx_circulo += num_circulos_linha

    # Processar cada bloco separadamente
    resultado_gabarito = []
    questao_atual = 1
    # Criar imagem binarizada para verificar preenchimento
    limiar = cv2.adaptiveThreshold(desfoque, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, 
                                   cv2.THRESH_BINARY_INV, 11, 2)
    for bloco_idx, circulos_bloco in enumerate(circulos_por_bloco):
        num_questoes_bloco = questoes_por_bloco[bloco_idx]
        num_circulos_bloco = num_questoes_bloco * num_alternativas
        if len(circulos_bloco) != num_circulos_bloco:
            raise Exception(f"Bloco {bloco_idx + 1} tem {len(circulos_bloco)} círcатели, mas esperava {num_circulos_bloco}")

        # Processar as questões do bloco
        for i in range(0, len(circulos_bloco), num_alternativas):
            opcoes = circulos_bloco[i:i + num_alternativas]
            opcoes = sorted(opcoes, key=lambda x: x[0])
            opcao_marcada = None
            for k, opcao in enumerate(opcoes):
                x, y, r = opcao
                # Ajustar as coordenadas para a imagem cortada
                x_adjusted = x - x_inicio
                y_adjusted = y - y_inicio
                # Criar uma máscara circular
                mask = np.zeros_like(limiar)
                cv2.circle(mask, (x_adjusted, y_adjusted), r, 255, -1)
                roi = cv2.bitwise_and(limiar, limiar, mask=mask)
                proporcao_preenchida = cv2.countNonZero(roi[y_adjusted-r:y_adjusted+r, x_adjusted-r:x_adjusted+r]) / (math.pi * r * r)
                if proporcao_preenchida > 0.5:
                    opcao_marcada = chr(65 + k)
                    break
            resultado_gabarito.append({
                "questao": questao_atual,
                "bloco": f"bloco{bloco_idx + 1}",
                "resposta": opcao_marcada if opcao_marcada else "Nenhuma"
            })
            questao_atual += 1

    # Desenhar os círculos detectados na imagem original
    for (x, y, r) in circulos:
        cv2.circle(imagem, (x, y), r, (0, 255, 0), 2)

    # Salvar a imagem com os círculos desenhados
    caminho_saida = os.path.join(diretorio_saida, f"{nome_sem_extensao}_resultado.png")
    if cv2.imwrite(caminho_saida, imagem):
        print(f"Imagem com círculos salva em: {caminho_saida}")
    else:
        # Tentar salvar em /tmp como fallback
        caminho_saida_fallback = f"/tmp/{nome_sem_extensao}_resultado.png"
        if cv2.imwrite(caminho_saida_fallback, imagem):
            print(f"Imagem com círculos salva em: {caminho_saida_fallback} (fallback devido a problema de permissão)")
        else:
            raise Exception(f"Erro ao salvar a imagem em {caminho_saida} e {caminho_saida_fallback}")

    # Retornar tanto o resultado do QR code quanto do gabarito
    return {
        "qr_code": qr_code_data,
        "gabarito": resultado_gabarito
    }

# Receber os argumentos da linha de comando
if __name__ == "__main__":
    if len(sys.argv) != 4:
        print(json.dumps({"error": "Uso: python script.py <caminho_imagem> <num_questoes> <num_alternativas>"}))
        sys.exit(1)

    caminho_imagem = sys.argv[1]
    try:
        num_questoes = int(sys.argv[2])
        num_alternativas = int(sys.argv[3])
    except ValueError:
        print(json.dumps({"error": "num_questoes e num_alternativas devem ser números inteiros"}))
        sys.exit(1)

    try:
        resultado = processar_gabarito(caminho_imagem, num_questoes, num_alternativas)
        print(json.dumps(resultado))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)