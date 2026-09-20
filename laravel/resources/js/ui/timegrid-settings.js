// Show the schedule hour options on the edit event page only when the custom time grid checkbox is on
document.addEventListener('DOMContentLoaded', function()
{
    var settings = document.querySelector('.timegrid-settings');

    if(!settings)
    {
        return;
    }

    var checkbox = settings.querySelector('input[name="custom_timegrid[]"]');
    var hours = settings.querySelector('.timegrid-hours');

    if(!checkbox || !hours)
    {
        return;
    }

    function update()
    {
        hours.classList.toggle('hidden', !checkbox.checked);
    }

    checkbox.addEventListener('change', update);
    update();
});