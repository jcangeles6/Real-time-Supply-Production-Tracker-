// live_time.js
function updateTime() {
    const now = new Date();
    const options = {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    };
    const liveTimeElem = document.getElementById("live-time");
    if (liveTimeElem) {
        liveTimeElem.innerHTML = "⏰ " + now.toLocaleString('en-US', options);
    }
}

setInterval(updateTime, 1000);
updateTime();
