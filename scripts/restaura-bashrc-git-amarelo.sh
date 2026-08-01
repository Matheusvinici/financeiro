#!/bin/bash

echo "========================================"
echo "Restaurando ~/.bashrc com branch Git em amarelo"
echo "Mantendo visual 100% padrão do Ubuntu"
echo "========================================"
echo

# Faz backup do .bashrc atual
cp ~/.bashrc ~/.bashrc.backup-$(date +%Y%m%d-%H%M%S) 2>/dev/null
echo "Backup do ~/.bashrc atual salvo com data/hora"

# Escreve o novo conteúdo completo no ~/.bashrc
cat > ~/.bashrc << 'EOL'
# ~/.bashrc: executed by bash(1) for non-login shells.
# see /usr/share/doc/bash/examples/startup-files for examples

# If not running interactively, don't do anything
case $- in
    *i*) ;;
      *) return;;
esac

# don't put duplicate lines or lines starting with space in the history
HISTCONTROL=ignoreboth

# append to the history file, don't overwrite it
shopt -s histappend

# for setting history length see HISTSIZE and HISTFILESIZE in bash(1)
HISTSIZE=1000
HISTFILESIZE=2000

# check the window size after each command and update LINES/COLUMNS
shopt -s checkwinsize

# make less more friendly for non-text input files
[ -x /usr/bin/lesspipe ] && eval "$(SHELL=/bin/sh lesspipe)"

# set variable identifying the chroot you work in (used in the prompt below)
if [ -z "${debian_chroot:-}" ] && [ -r /etc/debian_chroot ]; then
    debian_chroot=$(cat /etc/debian_chroot)
fi

# set a fancy prompt (non-color, unless we know we want color)
case "$TERM" in
    xterm-color|*-256color) color_prompt=yes;;
esac

# force color prompt
force_color_prompt=yes

if [ -n "$force_color_prompt" ]; then
    if [ -x /usr/bin/tput ] && tput setaf 1 >&/dev/null; then
        color_prompt=yes
    else
        color_prompt=
    fi
fi

# === Configuração da branch Git em amarelo (padrão Ubuntu) ===
# Carrega o script oficial do Git para mostrar a branch
if [ -f /usr/lib/git-core/git-sh-prompt ]; then
    source /usr/lib/git-core/git-sh-prompt
elif [ -f /usr/share/git/completion/git-prompt.sh ]; then
    source /usr/share/git/completion/git-prompt.sh
fi

# Indicadores de status da branch
export GIT_PS1_SHOWDIRTYSTATE=1          # * = arquivos modificados
export GIT_PS1_SHOWUNTRACKEDFILES=1      # % = arquivos não rastreados
export GIT_PS1_SHOWSTASHSTATE=1          # $ = stash existente
export GIT_PS1_SHOWUPSTREAM="auto"       # < > = atrás/adiantado do remote

# Prompt com cores padrão + branch em amarelo
if [ "$color_prompt" = yes ]; then
    PS1='${debian_chroot:+($debian_chroot)}\[\033[01;32m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[01;33m\]$(__git_ps1 " (%s)")\[\033[00m\]\$ '
else
    PS1='${debian_chroot:+($debian_chroot)}\u@\h:\w$(__git_ps1 " (%s)")\$ '
fi
unset color_prompt force_color_prompt
# === Fim da configuração Git ===

# set title of xterm/rxvt window
case "$TERM" in
xterm*|rxvt*)
    PS1="\[\e]0;${debian_chroot:+($debian_chroot)}\u@\h: \w\a\]$PS1"
    ;;
*)
    ;;
esac

# enable color support of ls and handy aliases
if [ -x /usr/bin/dircolors ]; then
    test -r ~/.dircolors && eval "$(dircolors -b ~/.dircolors)" || eval "$(dircolors -b)"
    alias ls='ls --color=auto'
    alias dir='dir --color=auto'
    alias vdir='vdir --color=auto'
    alias grep='grep --color=auto'
    alias fgrep='fgrep --color=auto'
    alias egrep='egrep --color=auto'
fi

# some useful ls aliases
alias ll='ls -alF'
alias la='ls -A'
alias l='ls -CF'

# Add an "alert" alias for long running commands
alias alert='notify-send --urgency=low -i "$([ $? = 0 ] && echo terminal || echo error)" "$(history|tail -n1|sed -e '\''s/^\s*[0-9]\+\s*//;s/[;&|]\s*alert$//'\'')"'

# load aliases from separate file if exists
if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

# enable programmable completion features
if ! shopt -oq posix; then
  if [ -f /usr/share/bash-completion/bash_completion ]; then
    . /usr/share/bash-completion/bash_completion
  elif [ -f /etc/bash_completion ]; then
    . /etc/bash_completion
  fi
fi
EOL

echo
echo "Arquivo ~/.bashrc restaurado com sucesso!"
echo "Para aplicar agora no terminal atual:"
echo "    source ~/.bashrc"
echo
echo "Da próxima vez, basta executar:"
echo "    ~/restaura-bashrc-git-amarelo.sh"
echo "e depois 'source ~/.bashrc'"
