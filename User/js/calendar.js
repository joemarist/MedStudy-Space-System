document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    window.myCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        contentHeight: 'auto',
        expandRows: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: [
            
        ]
    });

    window.myCalendar.render();
});
