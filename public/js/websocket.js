let socket = new WebSocket("ws://localhost:8080");

socket.onopen = function (e) {
    socket.send('{"message": "require_messages_history", "user": "' + userName + '"}');
    console.log("[open] Connection successful");
};

socket.onmessage = function (event) {
    let json = JSON.parse(event.data);

    if (json.message === 'message') {
        let messages = document.getElementById('message');
        let div = document.createElement('div');


        if (json.user === userName) {
            div.className = 'message outgoing';

            div.innerHTML =
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "<div class=\"message-time\" id=\"time\">" + json.time + "</div>";
        } else {
            div.className = 'message incoming';

            div.innerHTML =
                "<div class=\"message_user\">" + json.user + "</div>" +
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "<div class=\"message-time\" id=\"time\">" + json.time  + "</div>";
        }

        messages.append(div);

        scrollToBottom();
    }

    if(json.message === 'load_history') {
        let messages = document.getElementById('message');
        let div = document.createElement('div');

        if (json.user === userName) {
            div.className = 'message outgoing';

            div.innerHTML =
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "<div class=\"message-time\" id=\"time\">" + json.time + "</div>";
        } else {
            div.className = 'message incoming';

            div.innerHTML =
                "<div class=\"message_user\">" + json.user + "</div>" +
                "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
                "<div class=\"message-time\" id=\"time\">" + json.time  + "</div>";
        }

        messages.prepend(div);

        scrollToBottom();
    }

    if(json.message === 'online') {
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
    socket.send('{"message": "new message", "value": "' + text + '", "user": "' + userName + '", "time": "' + getCurrentTime() + '"}');
}

function getCurrentTime() {
    const currentDate = new Date();

    const hours = currentDate.getHours();
    const minutes = currentDate.getMinutes();

    const formattedHours = hours < 10 ? '0' + hours : hours;
    const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;

    return `${formattedHours}:${formattedMinutes}`;
}

function scrollToBottom() {
    let chatMessages = document.getElementById('message');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
