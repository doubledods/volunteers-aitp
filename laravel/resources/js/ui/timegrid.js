var $ = require('wetfish-basic');
const Highlight = require('./highlight');

// Convert "HH:MM" or "HH:MM:SS" into seconds since midnight
function timeToSeconds(time)
{
    var parts = String(time || '').split(':');
    var seconds = 0;

    seconds += (parseInt(parts[0], 10) || 0) * 60 * 60; // Hours
    seconds += (parseInt(parts[1], 10) || 0) * 60; // Minutes
    seconds += (parseInt(parts[2], 10) || 0); // Seconds

    return seconds;
}

// Find the time grid that belongs to an element (slot, shift, etc.)
function findGrid(element)
{
    var wrap = element.closest('.shift-wrap');
    return wrap ? wrap.querySelector('.timegrid') : null;
}

// Read the hours covered by a time grid, falling back to the full day
function gridRange(grid)
{
    var start = parseInt(grid && grid.getAttribute('data-start-hour'), 10);
    var end = parseInt(grid && grid.getAttribute('data-end-hour'), 10);

    if(isNaN(start)) start = 0;
    if(isNaN(end) || end <= start) end = 24;

    return {start: start * 60 * 60, end: end * 60 * 60};
}

$(window).on('resize', calculateSlotSizes);

$(document).ready(function()
{
    calculateSlotSizes();

    $('.shift-wrap').each(function()
    {
        new Highlight(this);
    });
});

function calculateSlotSizes()
{
    // If we're using a desktop resolution
    if($('.desktop').style('display') != "none")
    {
        // Set the height of the shift based on the number of rows
        $('.days .shift.row').each(function()
        {
            var rows = parseInt($(this).data('rows'));
            var height = (2 * rows) + 'em';

            $(this).find('.title').style({'height': height});
        });

        // Set the position and size of the slots so they line up with the hour lines of the grid
        $('.days .slot-wrap').each(function()
        {
            var grid = findGrid(this);
            var hours = grid ? grid.querySelector('.times .hours') : null;
            var container = this.offsetParent || this.parentNode;

            if(!hours || !container)
            {
                return;
            }

            var range = gridRange(grid);
            var span = range.end - range.start;
            var start = timeToSeconds($(this).data('start'));
            var end = start + timeToSeconds($(this).data('duration'));
            var row = parseInt($(this).data('row'));

            // Keep slots inside the grid. Shifts are expected to fit in a single day, but if one
            // doesn't, clip it at the edge instead of letting it spill across the page.
            var startFraction = Math.min(Math.max((start - range.start) / span, 0), 1);
            var endFraction = Math.min(Math.max((end - range.start) / span, 0), 1);

            var gridBox = hours.getBoundingClientRect();
            var containerBox = container.getBoundingClientRect();

            // Absolute positions are measured from the inside of the container's border
            var left = gridBox.left - containerBox.left - container.clientLeft + (startFraction * gridBox.width);
            var width = (endFraction - startFraction) * gridBox.width;
            var offsetTop = 2 * (row - 1);

            $(this).style({position: 'absolute', left: left + 'px', top: offsetTop + 'em', width: width + 'px'});
        });

        // Set the height of the grid backgrounds
        $('.days .shift-wrap').each(function()
        {
            var height = $(this).find('.department-wrap').height();
            $(this).find('.timegrid .background').style({'height': height + 'px'});
        });
    }
    else
    {
        $('.days .slot-wrap').attr('style', false);
    }
}

module.exports = {
    calculateSlotSizes
}