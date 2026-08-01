#!/bin/bash

# Criar Branch de desenvolvimento
current_branch=$(git rev-parse --abbrev-ref HEAD)

if [[ $current_branch == "dev_"* ]]; then
    echo "Você já está em um branch de desenvolvimento (${current_branch}). Não será criado um novo branch."
    exit 0
fi

echo "Nome do Novo Branch (ex: login, pagamento):"
read -r nome_branch

if [[ -z "$nome_branch" ]]; then
    echo "Nome do branch não pode ser vazio."
    exit 1
fi

branch_name="dev_$nome_branch"

# Verifica se o branch já existe localmente
if git show-ref --verify --quiet "refs/heads/$branch_name"; then
    echo "O branch '$branch_name' já existe localmente. Nenhuma ação será realizada."
    exit 0
fi

# Cria o novo branch
echo "Criando branch $branch_name..."
if ! git checkout -b "$branch_name"; then
    echo "Erro ao criar o branch."
    exit 1
fi

# Verifica se há mudanças para commitar
if ! git diff --quiet --cached && ! git diff --quiet; then
    echo "Há mudanças no diretório. Adicionando e commitando..."
    git add .
    if git commit -m "Criação do branch $branch_name"; then
        echo "Commit realizado com sucesso."
    else
        echo "Erro ao realizar commit."
        exit 1
    fi
else
    echo "Nenhuma mudança para commitar. Branch criado vazio."
fi

# Envia para o remoto
echo "Enviando branch $branch_name para o remoto..."
if git push -u origin "$branch_name"; then
    echo "✅ Branch '$branch_name' criado e enviado com sucesso para o repositório remoto!"
else
    echo "❌ Erro ao enviar o branch para o remoto."
    echo "Verifique sua conexão, autenticação ou permissões no repositório."
    exit 1
fi