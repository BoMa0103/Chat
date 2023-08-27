let socket = new WebSocket("ws://localhost:8080");

socket.onopen = function(e) {
    console.log("[open] Connection successful");
};

socket.onmessage = function(event) {
    let json = JSON.parse(event.data);

    if(json.message === 'message'){
        let messages = document.getElementById('message');
        let p = document.createElement('p');
        p.innerHTML = "<div class=\"message incoming\"> <div class=\"message-content\" id=\"message\">" + json.value + "</div> </div>";
        messages.append(p);
    }
};

socket.onclose = function(event) {
    if (event.wasClean) {
        console.log(`[close] Connection closed successful, code=${event.code} причина=${event.reason}`);
    } else {
        console.log('[close] Connection interrupted');
    }
};

socket.onerror = function(error) {
    console.log(`[error]`);
};

function send() {
    let text = document.getElementById('text').value;
    socket.send('{"message": "new message", "value": "' + text + '"}');
}
