Makelos tasks are command line utilities for scripting utilities.

You can list available tasks by running

    ./makelos

if you want to make your own, Akelos gives you a great way to do this
by running:

    ./makelos generate task



Autocompletion on bash prompts
--------------------------------

You can add bash autocompletion support to Makelos

First you'll need to have installed bash-completion

    Mac OS: sudo port install bash-completion
    Debian: apt-get install bash-completion

Add to the very bottom of your bash profile (Nice post by
Todd Werth http://blog.infinitered.com/entries/show/4 on
the subject)

    Mac OS ~/.profile:


    if [ -f /opt/local/etc/bash_completion ]; then
        . /opt/local/etc/bash_completion
    fi

    Debian ~/.bashrc:


    if [ -f /etc/bash_completion ]; then
        . /etc/bash_completion
    fi


Create the file

    Mac OS: /opt/local/etc/bash_completion.d/makelos
    Debian: /etc/bash_completion.d/makelos

with the following code

    _makelos()
    {
       local cur colonprefixes arguments
       COMPREPLY=()
       cur=${COMP_WORDS[COMP_CWORD]}
       # Work-around bash_completion issue where bash
       # interprets a colon
       # as a separator.
       # Work-around borrowed from the darcs/Maven2
       # work-around for the same issue.
       colonprefixes=${cur%"${cur##*:}"}
       arguments=("${COMP_WORDS[@]:1}")
       COMPREPLY=( $(compgen -W '$(./makelos makelos:autocomplete \
       ${arguments[@]})'  -- $cur))
       local i=${#COMPREPLY[*]}
       while [ $((--i)) -ge 0 ]; do
          COMPREPLY[$i]=${COMPREPLY[$i]#"$colonprefixes"}
       done
       return 0
    } &&

complete -o bashdefault -o default -F _makelos ./makelos 2>/dev/null \
    || complete -o default -F _makelos ./makelos


cd to your app dir in a new prompt and enjoy makelos autocompletion.