current_branch=$(git rev-parse --abbrev-ref HEAD)

if [[ $current_branch == "dev-"* ]]; then
    echo "Você está em uma branch de desenvolvimento. Não será realizado esta ação."
else

echo "Branchs locais:"
        git branch --no-color | awk '/^  backup-/{print $1}'

read -p "Digite o nome do branch de backup: " nome_branch

git reset --hard $nome_branch