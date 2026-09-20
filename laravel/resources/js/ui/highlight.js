// Class to highlight the current time based on your mouse position
var Highlight = function(wrap)
{
    this.wrap = wrap;
    this.bind();
}

// Bind events
Highlight.prototype.bind = function()
{
    // Save the current scope
    var current = this;

    this.wrap.addEventListener('mouseenter', function(event)
    {
        current.cursor = event.clientX;

        current.clear();
        current.check();
    });

    this.wrap.addEventListener('mousemove', function(event)
    {
        current.cursor = event.clientX;

        current.clear();
        current.check();
    });

    this.wrap.addEventListener('mouseleave', function()
    {
        current.clear();
    });
}

// Clear any previously active times
Highlight.prototype.clear = function()
{
    var active = this.wrap.querySelectorAll('.time.active');

    for(var i = 0; i < active.length; i++)
    {
        active[i].classList.remove('active');
    }
}

// Figure out which hour the cursor is over by checking where each hour label sits on the page.
// This works no matter how many hours the grid shows.
Highlight.prototype.check = function()
{
    var labels = this.wrap.querySelectorAll('.times .time');
    var backgrounds = this.wrap.querySelectorAll('.background .time');

    for(var index = 0; index < labels.length; index++)
    {
        var box = labels[index].getBoundingClientRect();

        if(this.cursor >= box.left && this.cursor < box.right)
        {
            labels[index].classList.add('active');

            if(backgrounds[index])
            {
                backgrounds[index].classList.add('active');
            }

            return;
        }
    }
}

module.exports = Highlight;