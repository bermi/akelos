<div class="wide-content">
    <h1>_{Akelos Terminal for <strong>%application_name</strong>}</h1>
    {?enabled}
    <p>_{The Akelos terminal is running as user <strong>%user</strong>.}</p>
    {else}
    <%= flash_warning _("In order to use the Akelos Terminal you need to define('AK_ENABLE_TERMINAL_ON_DEV', true); on your \nconfig/environments/development.php file") %>
    {end}
</div>

<div class="cls"></div>

<div id="terminal_canvas" class="terminal radius_5">
    <form onsubmit="return false" action="<%= url_for 'action' => 'web_terminal' %>">
    <div id="output_panel" class="terminal_output"></div>
    <div class="prompt"><span onclick="$('cmd').focus();">{user} $ </span><input onkeyup="handleKeystroke(event)" id="cmd" type="text" /></div>
    </form>
</div>

<script type="text/javascript" language="javascript">
var command_history = new Array();
var history_counter;
$("cmd").focus();
function displayResponse(response_text) {
    var output_panel = $("output_panel");
    var user_command = $("cmd").value;
    output_panel.insert({bottom:'<pre class="history"><span>{user} $ </span>'+user_command+'</pre><br />'});

    $A(response_text.split("\n\n")).each(function(line){
        output_panel.insert({bottom:'<pre>'+line+'</pre><br />'});
    });
    //output_panel.insert({bottom:'{user} $ '});
    output_panel.scrollTop = output_panel.scrollHeight;
    $("cmd").value = '';
    $("cmd").focus();
}
function clearPanel() {
    var output_panel = $("output_panel");
    output_panel.innerHTML = '';
    output_panel.scrollTop = output_panel.scrollHeight;
    $("cmd").value = '';
    $("cmd").focus();
}

function runUserCommand(command){
    var user_command = command || $("cmd").value;
    $("cmd").value = user_command;
    if (user_command) {
        command_history[command_history.length] = user_command;
        history_counter = command_history.length;
        if(user_command == 'clear' || user_command == 'cls'){
            clearPanel();
        }else{
            new Ajax.Request('<%= url_for 'action' => 'web_terminal', :controller => 'akelos_dashboard' %>', {
                parameters: {cmd: user_command},
                onSuccess: function (e){
                    displayResponse(e.responseText);
                }
            });
        }
    }
}

function handleKeystroke(keystroke) {
    switch (keystroke.keyCode) {
        case 13:
        runUserCommand();
        break;
        case 38:
        if (history_counter > 0) {
            history_counter--;
            $("cmd").value = command_history[history_counter];
        }
        break;
        case 40:
        if (history_counter < command_history.length - 1) {
            history_counter++;
            $("cmd").value = command_history[history_counter];
        }
        break;
        default:
        break;
    }
}
{?params-command}
runUserCommand('{\params-command}');
{end}
</script>

<br />
<br />
{?enabled}
<%= flash_notice _("To disable the terminal, remove AK_ENABLE_TERMINAL_ON_DEV from your config.php file") %>
{end}