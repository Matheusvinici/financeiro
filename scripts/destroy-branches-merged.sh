# confirmar se realmente deseja excluir as branches mescladas
read -p "Deseja excluir as branches mescladas? (s/n) " resposta

if [[ $resposta == "s" ]]; then
    git branch -r --list "origin/merged-*" | sed 's#^[[:space:]]*origin/##' | xargs -r git push origin --delete
    git branch -d $(git branch --list "merged-*")
fi

