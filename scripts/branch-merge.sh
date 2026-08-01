# merge branch
current_branch=$(git rev-parse --abbrev-ref HEAD)

if [[ $current_branch != "main" && $current_branch != "master" ]]; then
    echo "Você está na branch de desenvolvimento '$current_branch', não será possível fazer o merge."
    # bloqueia aqui (ex: exit 1 em um hook)
    exit 1
else        
        echo "Mensagem que deseja adicionar para este Merge:"
        read mensagem
        
        echo "Branch locais:"
        git branch --no-color | awk '/^  /{print $1}'

        echo "Qual o branch que deseja fazer o Merge:"
        read nome_branch

        if [[ $nome_branch =~ (?i)teste ]]; then
            echo "A branch de merge é uma branch de teste, não será feito o merge."
            exit 1
        fi

        if [[ $current_branch == 'main' ]]; then
            # Solicita confirmação do usuário
            echo "Você está na branch 'main'. Deseja continuar com o merge? (s/n)"
            read -r response
            if [[ ($response != "s" && $response != "S") 
            # || $nome_branch != 'homologacao' 
            ]]; then
                echo "Merge cancelado."
                exit 1
            fi
        fi

        git merge $nome_branch -m "$mensagem"

    if [ $? -eq 0 ]; then
        # # Se o merge foi bem-sucedido, exclui o branch
        # git branch -d $nome_branch
        # git push origin --delete $nome_branch

        #se bem sucedido renomear a branch
        git branch -m $nome_branch merged-$nome_branch
        git push origin merged-$nome_branch
        git push origin --delete $nome_branch
        # bash atualizar.sh

        echo "Feito o MERGE do Branch $nome_branch, o mesmo foi renomeado."
        echo "Pronto agora para fazer o push para o repositório remoto utilizando o comando: bash atualizar.sh"
    else
        echo "O merge não foi bem-sucedido. verifique o que aconteceu."
        echo "Caso tenha ocorrido um conflito, resolva-o, utilize "git add ." e faça o commit."
        echo "Após resolver o conflito, e feito o commit, execute o comando: bash atualizar.sh"
    fi
fi
