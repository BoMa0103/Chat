const DEFAULT_MESSAGES_COUNT_LOAD = 20;
let load_messages_count = 0;
let previousScrollHeight = 0;

function sendMessage() {
    let text = document.getElementById('text').value;
    document.getElementById('text').value = '';
    socket.send('{"message": "new_message", "value": "' + text + '", "user_id": "' + user_id + '", "time": "' + getCurrentTime() + '"}');
}

function getOrCreateNewChat(userId) {
    socket.send('{"message": "new_chat", "user_id": "' + userId + '"}');

    document.getElementById('messages').innerText = '';

    load_messages_count = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
}

function selectChat(chatId) {
    socket.send('{"message": "select_chat", "chat_id": "' + chatId + '"}');

    document.getElementById('messages').innerText = '';

    load_messages_count = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
}

function showNewMessage(json) {
    let messages = document.getElementById('messages');

    let div = showMessage(json);

    messages.append(div);

    scrollToBottom();
}

function showMessagesHistory(json) {
    let messages = document.getElementById('messages');

    let div = showMessage(json);

    messages.prepend(div);

    load_messages_count++;

    if (load_messages_count === DEFAULT_MESSAGES_COUNT_LOAD) {
        scrollToBottom();
    } else {
        scrollToCurrentMessage();
    }
}

function showMessage(json) {
    let div = document.createElement('div');

    if (json.user.id == user_id) {
        div.className = 'message outgoing';

        div.innerHTML =
            "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
            "<div class=\"message-time\" id=\"time\">" + json.time + "</div>";
    } else {
        div.className = 'message incoming';

        div.innerHTML =
            "<div class=\"message_user\">" + json.user.name + "</div>" +
            "<div class=\"message-content\" id=\"message\">" + json.value + "</div>" +
            "<div class=\"message-time\" id=\"time\">" + json.time + "</div>";
    }

    return div;
}

function showOnlineUsersCount(json) {
    let online = document.getElementById('online');

    online.innerHTML = "Online " + json.value;
}

function showOnlineUsersList(json) {
    let users = document.getElementById('user-list');
    users.innerHTML = null;

    json.value.forEach(user => {
        let li = document.createElement('li')
        li.innerHTML = "<p onclick=\"getOrCreateNewChat(" + user.id + ")\">" + user.name + "</p>";
        if(user.id == user_id) {
            li.innerHTML = "<p>" + user.name + " (you)</p>";
        }
        users.append(li);
    });
}

function requireSelectChat(json) {
    let chat = document.getElementById('chat');
    let noChat = document.getElementById('no-chat');

    chat.setAttribute('hidden', 'true');
    noChat.removeAttribute('hidden');
    noChat.style.display = 'block';
}

function chatSelected(json) {
    let chat = document.getElementById('chat');
    let noChat = document.getElementById('no-chat');

    noChat.setAttribute('hidden', 'true');
    noChat.style.display = 'none';
    chat.removeAttribute('hidden');
}

function loadChats(json) {
    let chats = document.getElementById('chat-list');

    chats.innerText = '';

    json.value.forEach(chat => {
        let li = document.createElement('li');
        li.onclick = function () {
            chats.querySelectorAll('.chat-item').forEach((item) => {
                item.classList.remove('selected');
            });

            li.classList.add('selected');

            selectChat(chat.id);
        }
        li.innerHTML = "<p>" + json.chat_names_list[chat.id] + "</p>";
        li.className = "chat-item";
        chats.append(li);
    });
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
    let chatMessages = document.getElementById('messages');

    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function scrollToCurrentMessage() {
    let chatMessages = document.getElementById('messages');

    chatMessages.scrollTop = chatMessages.scrollHeight - previousScrollHeight;
}


window.addEventListener("DOMContentLoaded", (event) => {
    let chatMessages = document.getElementById('messages');
    console.log('DOMContentLoaded');


    chatMessages.addEventListener('scroll', function () {
        const scrollTop = chatMessages.scrollTop;
        const scrollHeight = chatMessages.scrollHeight;


        if (scrollTop === 0 && load_messages_count !== 0) {
            previousScrollHeight = scrollHeight;
            console.log('scroll');
            socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
        }
    });
});
