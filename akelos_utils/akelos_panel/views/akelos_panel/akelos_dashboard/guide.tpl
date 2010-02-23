{?guide}
<div id="guide">
    <div id="guide-content">
        <div id="prologue"><%= render_prologue guide %></div>
        <%= render_doc guide %>
    </div>
</div>
{else}
    <h2>_{Ooops! Can't find the guide you're looking for}</h2>
{end}


