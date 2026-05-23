(function(){
    var canvas = document.getElementById('roomBookingChart');
    if (!canvas) return;

    var labels = [];
    var data = [];
    try { labels = JSON.parse(canvas.dataset.labels || '[]'); } catch(e) { labels = []; }
    try { data = JSON.parse(canvas.dataset.data || '[]'); } catch(e) { data = []; }

    var ctx = canvas.getContext('2d');
    var roomBookingChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Bookings',
                data: data,
                backgroundColor: ['#facc15', '#34d399', '#3b82f6', '#f87171', '#a855f7', '#f43f5e'],
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return context.dataset.label + ': ' + context.raw; }
                    }
                }
            },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
})();
