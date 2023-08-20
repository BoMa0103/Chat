let socket = new WebSocket("ws://localhost:8080");

socket.onopen = function (e) {
    console.log("[open] Connection successful");
};

socket.onmessage = function (event) {
    let json = JSON.parse(event.data);

    if (json.message === 'message') {
        let messages = document.getElementById('message');
        let p = document.createElement('p');

        if (json.user === userName) {
            p.innerHTML =
                "<div class=\"message outgoing\">" +
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "</div>";
        } else {
            p.innerHTML =
                "<div class=\"message incoming\">" +
                "<div class=\"message_user\">" + json.user + "</div>" +
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "</div>";
        }

        messages.append(p);
    }

    if(json.message === 'online') {
        console.log(json);
        let messages = document.getElementById('online');

        messages.innerHTML = "Online " + json.value;
    }
};

socket.onclose = function (event) {
    if (event.wasClean) {
        console.log(`[close] Connection closed successful, code=${event.code} reason=${event.reason}`);
    } else {
        console.log('[close] Connection interrupted');
    }
};

socket.onerror = function (error) {
    console.log(`[error]`);
};

function send() {
    let text = document.getElementById('text').value;
    socket.send('{"message": "new message", "value": "' + text + '", "user": "' + userName + '"}');
}
