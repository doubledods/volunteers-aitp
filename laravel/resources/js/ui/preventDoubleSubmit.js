document.addEventListener('submit', function(e)
{
    var btn = e.target.querySelector('button[type="submit"]');

    if(!btn) return;

    if(btn.disabled)
    {
        e.preventDefault();
        return;
    }

    btn.disabled = true;
});