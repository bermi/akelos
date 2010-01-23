CodeHighlighter.addStyle("css", {
	comment : {
		exp  : /\/\*[^*]*\*+([^\/][^*]*\*+)*\//
	},
	keywords : {
		exp  : /@\w[\w\s]*/
	},
	selectors : {
		exp  : "([\\w-:\\[.#][^{};>]*)(?={)"
	},
	properties : {
		exp  : "([\\w-]+)(?=\\s*:)"
	},
	units : {
		exp  : /([0-9])(em|en|px|%|pt)\b/,
		replacement : "$1<span class=\"$0\">$2</span>"
	},
	urls : {
		exp  : /url\([^\)]*\)/
	}
 });

CodeHighlighter.addStyle("php",{
	comment : {
		exp  : /(\/\/[^\n]*(\n|$))|(\/\*[^*]*\*+([^\/][^*]*\*+)*\/)/
	},
	brackets : {
		exp  : /\(|\)|\{|\}/
	},
	numbers : {
		exp  : /(\d+)/
	},
	keywords : {
		exp  : /\b(and|include_once|list|abstract|global|private|echo|interface|as|static|endswitch|array|null|if|endwhile|or|const|for|endforeach|self|var|while|isset|public|protected|exit|foreach|throw|elseif|extends|include|__FILE__|empty|require_once|function|do|xor|return|implements|parent|clone|use|__CLASS__|__LINE__|else|break|print|eval|new|catch|__METHOD__|class|case|exception|php_user_filter|default|die|require|__FUNCTION__|enddeclare|final|try|this|switch|continue|endfor|endif|declare|unset)\b/
	},
	variable : {
	  exp : /(\$[A-Za-z0-9_]+)/
	},
	/*
	heredoc : {
	  exp : /(&lt;&lt;&lt;([^\n]+)[^\2]+\2;?\n)/,
	  replacement: "<span class=\"heredoc\">$2</span>"
	},
	*/
	phptag : {
	  exp : /(&lt;\?(php)?|\?&gt;)/
	},
	constant : {
	  exp : /([A-Z0-9_]{2,})/
	},
	akelosclasses : {
	  exp : /(Ak[A-Z]\w+|ActiveRecord|ActiveDocument|AplicationController)/
	},
	string : {
		exp  : /'[^']*'|"[^"]*"/
	}
});

CodeHighlighter.addStyle("html", {
	comment : {
		exp: /&lt;!\s*(--([^-]|[\r\n]|-[^-])*--\s*)&gt;/
	},
	tag : {
		exp: /(&lt;\/?)([a-zA-Z1-9]+\s?)/,
		replacement: "$1<span class=\"$0\">$2</span>"
	},
	string : {
		exp  : /'[^']*'|"[^"]*"/
	},
	attribute : {
		exp: /\b([a-zA-Z-:]+)(=)/,
		replacement: "<span class=\"$0\">$1</span>$2"
	},
	doctype : {
		exp: /&lt;!DOCTYPE([^&]|&[^g]|&g[^t])*&gt;/
	}
});

CodeHighlighter.addStyle("javascript",{
	comment : {
		exp  : /(\/\/[^\n]*(\n|$))|(\/\*[^*]*\*+([^\/][^*]*\*+)*\/)/
	},
	brackets : {
		exp  : /\(|\)/
	},
	string : {
		exp  : /'[^']*'|"[^"]*"/
	},
	keywords : {
		exp  : /\b(arguments|break|case|continue|default|delete|do|else|false|for|function|if|in|instanceof|new|null|return|switch|this|true|typeof|var|void|while|with)\b/
	},
	global : {
		exp  : /\b(toString|valueOf|window|element|prototype|constructor|document|escape|unescape|parseInt|parseFloat|setTimeout|clearTimeout|setInterval|clearInterval|NaN|isNaN|Infinity)\b/
	}
});

CodeHighlighter.addStyle("yaml", {
	keyword : {
		exp  : /\/\*[^*]*\*+([^\/][^*]*\*+)*\//
	},
	value : {
		exp  : /@\w[\w\s]*/
	},
});
