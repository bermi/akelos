{?guide}
<div id="guide">
    <div class="blue-banner rounded">
        <div class="inner-blue-banner">
            <%= render_exerpt guide %>
        </div>
    </div>
    <div id="guide-content">
        <%= render_doc guide %>
    </div>
</div>
{else}
    <h2>_{Ooops! Can't find the guide you're looking for}</h2>
{end}


