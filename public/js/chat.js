const DEFAULT_MESSAGES_COUNT_LOAD = 20;
let load_messages_count = 0;
let previousScrollHeight = 0;

function sendMessage() {
    let text = document.getElementById('text');
    socket.send('{"message": "new_message", "value": "' + text.value + '", "time": "' + getCurrentTime() + '"}');
    text.value = '';

    document.getElementById('send').setAttribute('disabled', 'disabled');
}

function changeLastMessage(json) {
    let selectedItem = document.getElementsByClassName('selected')[0];
    selectedItem.getElementsByClassName('chat-last-message')[0].innerText = json.value;
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

function selectChat(chatId) {
    socket.send('{"message": "select_chat", "chat_id": "' + chatId + '"}');

    document.getElementById('messages').innerText = '';

    load_messages_count = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
}

function showMessage(json) {
    let div = document.createElement('div');

    if (json.user.id === parseInt(user_id)) {
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

        if (user.id === parseInt(user_id)) {
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
    noChat.style.display = 'flex';
    chat.style.display = 'none';
}

function chatSelected(json) {
    let chat = document.getElementById('chat');
    let noChat = document.getElementById('no-chat');

    noChat.setAttribute('hidden', 'true');
    noChat.style.display = 'none';
    chat.removeAttribute('hidden');
    chat.style.display = 'flex';
}

function loadChats(json) {
    let chats = document.getElementById('chat-list');

    chats.innerText = '';

    let chatOrder = 0;

    json.value.forEach(chat => {
        let li = document.createElement('li');

        chatOrder = chatOrder + 1;

        li.onclick = function () {

            chats.querySelectorAll('.chat-item').forEach((item) => {
                item.classList.remove('selected');
            });

            markSelectedChat(li);

            selectChat(chat.id);
        }

        li.innerHTML = "<a> " +
                "<img class=\"w-7 h-7 mr-6 rounded-full\" src=\"/images/alexander-hipp-iEEBWgY_6lA-unsplash.jpg\" alt=\"User image\">" +
                "<div>" +
                    "<p class=\"chat-name\">" + json.chat_names_list[chat.id] + "</p>" +
                    "<p class=\"chat-last-message\" id=\"chat-last-message\">" + json.chats_last_message_list[chat.id] + "</p>" +
                "</div>" +
            "</a>";

        li.className = "chat-item";

        if (chatOrder === 1) {
            li.classList.add('firstChat');
        } else {
            li.classList.add('nChat');
        }

        if (json.current_chat_id === chat.id) {
            markSelectedChat(li);
        }

        chats.append(li);
    });
}

function markSelectedChat(li) {
    li.classList.add('selected');

    if (li.classList.contains('firstChat')) {
        document.getElementById('concave-left').style.display = 'none';
        document.getElementById('messages').style.borderTopLeftRadius = '0';
    } else {
        document.getElementById('concave-left').style.display = 'flex';
    }
}

function getOrCreateNewChat(userId) {
    socket.send('{"message": "new_chat", "user_id": "' + userId + '"}');

    document.getElementById('messages').innerText = '';

    load_messages_count = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
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

/* Events */

window.addEventListener("DOMContentLoaded", (event) => {
    let chatMessages = document.getElementById('messages');
    let textArea = document.getElementById('text');
    let sendButton = document.getElementById('send');

    chatMessages.addEventListener('scroll', function () {
        const scrollTop = chatMessages.scrollTop;
        const scrollHeight = chatMessages.scrollHeight;


        if (scrollTop === 0 && load_messages_count !== 0) {
            previousScrollHeight = scrollHeight;
            socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
        }
    });

    textArea.addEventListener('input', function () {
        if (textArea.value.trim() !== '') {
            sendButton.removeAttribute('disabled');
        } else {
            sendButton.setAttribute('disabled', 'disabled');
        }
    });
});



