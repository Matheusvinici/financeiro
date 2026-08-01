current_branch=$(git rev-parse --abbrev-ref HEAD)

if [[ $current_branch == "dev-"* ]]; then
    echo "Você está em um branch de desenvolvimento. Não será criado um novo branch."
else
    # data e hora do momento
    data_hora=$(date "+%Y%m%d-%H%M%S")
    nome_branch="backup-$data_hora"
    # Verifica se o branch já existe
    if git show-ref --verify --quiet refs/heads/$nome_branch; then
        echo "O branch '$nome_branch' já existe. Nenhuma ação será realizada."
    else
        git branch $nome_branch  
        git push origin $nome_branch
    fi
fi