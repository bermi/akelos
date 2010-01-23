var debugWindow = null;
function debug(text, reverse) {
	if (! $('debug'))
 		return;

	time = "-"; //new Date();
	reverse = true
	if (reverse)
		$('debug').innerHTML = time + " " + text + "<br>"+ 	$('debug').innerHTML;
	else
		$('debug').innerHTML +=  time + " " + text + "<br>";
}

function hideDebug() {
	debugWindow.close();
	debugWindow.destroy();
	debugWindow = null;
}

function showDebug() {
	if (debugWindow == null) {
		debugWindow = new Window('debug_window', {height:100, top:300, left:500, zIndex:1000, opacity:0.6, showEffect: Element.show, resizable: true, title: "Debug"})
		debugWindow.getContent().innerHTML = "<div id='debug' style='padding:3px'></div>";
	}
	debugWindow.show()
}

function clearDebug() {
	if (! $('debug'))
 		return;
	$('debug').innerHTML = "";
}

