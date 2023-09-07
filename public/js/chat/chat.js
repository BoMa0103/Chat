const DEFAULT_MESSAGES_COUNT_LOAD = 20;

let loadMessagesCount = 0;
let previousScrollHeight = 0;

function sendMessage() {
    let text = document.getElementById('text');

    socket.send('{"message": "new_message", "value": "' + text.value + '", "time": "' + getCurrentTime() + '"}');
    text.value = '';

    document.getElementById('send').setAttribute('disabled', 'disabled');
}

function markMessagesAsRead() {
    let messages = document.getElementsByClassName('unread');

    let messageArray = Array.from(messages);

    for (let message of messageArray) {
        message.classList.remove('unread');

        message.getElementsByTagName('svg')[0].outerHTML = "<svg style=\"position: relative; top: 4px;\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 30\" width=\"24\" height=\"24\" fill=\"none\">" +
            "       <path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M16.7071 8.20711C17.0976 7.81658 17.0976 7.18342 16.7071 6.79289C16.3166 6.40237 15.6834 6.40237 15.2929 6.79289L9.5 12.5858L8.70711 11.7929C8.31658 11.4024 7.68342 11.4024 7.29289 11.7929C6.90237 12.1834 6.90237 12.8166 7.29289 13.2071L8.08579 14L7 15.0858L3.70711 11.7929C3.31658 11.4024 2.68342 11.4024 2.29289 11.7929C1.90237 12.1834 1.90237 12.8166 2.29289 13.2071L6.29289 17.2071C6.68342 17.5976 7.31658 17.5976 7.70711 17.2071L9.5 15.4142L11.2929 17.2071C11.6834 17.5976 12.3166 17.5976 12.7071 17.2071L21.7071 8.20711C22.0976 7.81658 22.0976 7.18342 21.7071 6.79289C21.3166 6.40237 20.6834 6.40237 20.2929 6.79289L12 15.0858L10.9142 14L16.7071 8.20711Z\" fill=\"#4b5563\"/>" +
            "</svg>";
    }
}

function changeLastMessage(json) {
    let chat = document.getElementById(json.chat_id);

    if (!chat) {
        return;
    }

    chat.getElementsByClassName('chat-last-message')[0].innerText = json.value;
}

function clearHistory() {
    document.getElementById('messages').innerText = '';
}

function showNewMessage(json) {
    let messages = document.getElementById('messages');

    let div = showMessage(json);

    messages.append(div);

    if (document.visibilityState !== "visible") {
        notifyMe(json);
    }

    scrollToBottom();
}

function showMessagesHistory(json) {
    let messages = document.getElementById('messages');

    let div = showMessage(json);

    messages.prepend(div);

    loadMessagesCount++;

    if (loadMessagesCount === DEFAULT_MESSAGES_COUNT_LOAD) {
        scrollToBottom();
    } else {
        scrollToCurrentMessage();
    }
}

function showMessage(json) {
    let div = document.createElement('div');

    if (json.user.id === parseInt(user_id)) {
        div.className = 'message outgoing';

        let svg;

        if(json.read_status === 1) {
            svg = "<svg style=\"position: relative; top: 4px;\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 30\" width=\"24\" height=\"24\" fill=\"none\">" +
                "       <path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M16.7071 8.20711C17.0976 7.81658 17.0976 7.18342 16.7071 6.79289C16.3166 6.40237 15.6834 6.40237 15.2929 6.79289L9.5 12.5858L8.70711 11.7929C8.31658 11.4024 7.68342 11.4024 7.29289 11.7929C6.90237 12.1834 6.90237 12.8166 7.29289 13.2071L8.08579 14L7 15.0858L3.70711 11.7929C3.31658 11.4024 2.68342 11.4024 2.29289 11.7929C1.90237 12.1834 1.90237 12.8166 2.29289 13.2071L6.29289 17.2071C6.68342 17.5976 7.31658 17.5976 7.70711 17.2071L9.5 15.4142L11.2929 17.2071C11.6834 17.5976 12.3166 17.5976 12.7071 17.2071L21.7071 8.20711C22.0976 7.81658 22.0976 7.18342 21.7071 6.79289C21.3166 6.40237 20.6834 6.40237 20.2929 6.79289L12 15.0858L10.9142 14L16.7071 8.20711Z\" fill=\"#4b5563\"/>" +
                "</svg>";
        } else {
            div.classList.add('unread');
            svg = "<svg style=\"position: relative; top: 5px; padding: 1px; margin-right: 4px;\" xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 100 125\">" +
                "<g transform=\"translate(0,-952.36218)\"><path style=\"text-indent:0;text-transform:none;direction:ltr;block-progression:tb;baseline-shift:baseline;color:#4b5563;enable-background:accumulate;\" d=\"m 88.98041,971.33516 a 6.0006,6.0006 0 0 0 -4.1562,1.7187 l -48.1876,46.34384 -21.875001,-17.6875 a 6.0102958,6.0102958 0 1 0 -7.5623997,9.3437 l 26.0000007,21 a 6.0006,6.0006 0 0 0 7.9374,-0.3437 l 51.999997,-50.00004 a 6.0006,6.0006 0 0 0 -4.156197,-10.375 z\" fill=\"#4b5563\" fill-opacity=\"1\" stroke=\"none\"  visibility=\"visible\" display=\"inline\" /></g>" +
                "</svg>"
        }

        div.innerHTML =
            "<div class=\"message-content\" id=\"message\"><pre>" + json.value + "</pre></div>" +
            "<div class=\"message-meta\">" +
                "<div class=\"message-time\" id=\"time\">" + json.time + "</div>" +
                svg +
            "</div>";
    } else {
        div.className = 'message incoming';

        div.innerHTML =
            "<div class=\"message_user\">" + json.user.name + "</div>" +
            "<div class=\"message-content\" id=\"message\"><pre>" + json.value + "</pre></div>" +
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

function showUnreadMessagesCount(json) {
    let chat = document.getElementById(json.chat_id);

    if (!chat) {
        notifyMe(json);
        return;
    }

    let chatUnreadMessagesCount = chat.getElementsByClassName('chat-unread-messages-count')[0];

    if (!chatUnreadMessagesCount) {
        let div = document.createElement('div');
        div.className = 'chat-unread-messages-count';
        div.innerText = json.unread_messages_count;
        chat.append(div);
    } else {
        chatUnreadMessagesCount.innerText = json.unread_messages_count;
    }

    notifyMe(json);
}

function clearUnreadMessagesCount(chatId) {
    let chat = document.getElementById(chatId);
    let chatUnreadMessagesCount = chat.getElementsByClassName('chat-unread-messages-count')[0];

    if (chatUnreadMessagesCount) {
        chatUnreadMessagesCount.remove();
    }
}

function requireSelectChat() {
    let chat = document.getElementById('chat');
    let noChat = document.getElementById('no-chat');

    chat.setAttribute('hidden', 'true');
    noChat.removeAttribute('hidden');
    noChat.style.display = 'flex';
    chat.style.display = 'none';
}

function chatSelected() {
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

        if (json.chats_unread_messages_count_list[chat.id] === 0) {
            li.innerHTML = "<div> " +
                "<div class='chat-info' id=\"" + chat.id + "\">" +
                "<img class=\"w-7 h-7 mr-6 rounded-full\" src=\"/images/alexander-hipp-iEEBWgY_6lA-unsplash.jpg\" alt=\"User image\">" +
                "<div class='online-circle'></div>" +
                "<div class='chat-name-last-message'>" +
                "<p class=\"chat-name\">" + json.chat_names_list[chat.id] + "</p>" +
                "<p class=\"chat-last-message\" id=\"chat-last-message\">" + json.chats_last_message_list[chat.id] + "</p>" +
                "</div>" +
                "</div>";
        } else {
            li.innerHTML = "<div> " +
                "<div class='chat-info' id=\"" + chat.id + "\"> " +
                "<img class=\"w-7 h-7 mr-6 rounded-full\" src=\"/images/alexander-hipp-iEEBWgY_6lA-unsplash.jpg\" alt=\"User image\">" +
                "<div class='online-circle'></div>"+
                "<div class='chat-name-last-message'>" +
                "<p class=\"chat-name\">" + json.chat_names_list[chat.id] + "</p>" +
                "<p class=\"chat-last-message\" id=\"chat-last-message\">" + json.chats_last_message_list[chat.id] + "</p>" +
                "</div>" +
                "<div class=\"chat-unread-messages-count\">" + json.chats_unread_messages_count_list[chat.id] + "</div>" +
                "</div>";
        }

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

function markChatAsOnline(json) {
    let chat = document.getElementById(json.chat_id);

    if (!chat) {
        return;
    }

    let circleDiv = chat.getElementsByClassName('online-circle')[0];

    circleDiv.style.display = 'block';
}

function markChatAsOffline(json) {
    let chat = document.getElementById(json.chat_id);
    let circleDiv = chat.getElementsByClassName('online-circle')[0];

    circleDiv.style.display = 'none';
}

function selectChat(chatId) {
    socket.send('{"message": "select_chat", "chat_id": "' + chatId + '"}');

    document.getElementById('messages').innerText = '';

    loadMessagesCount = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + loadMessagesCount + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
    socket.send('{"message": "mark_messages_as_read", "chat_id": "' + chatId + '"}');

    clearUnreadMessagesCount(chatId);
}

function getOrCreateNewChat(userId) {
    socket.send('{"message": "select_or_create_new_chat", "user_id": "' + userId + '"}');
}

function selectChatFromOnlineUsers(json) {
    let selectedChat = document.getElementsByClassName('selected')[0];
    selectedChat.classList.remove('selected');

    let chat = document.getElementById(json.chat_id);

    markSelectedChat(chat.parentNode.parentNode);

    document.getElementById('messages').innerText = '';

    loadMessagesCount = 0;

    socket.send('{"message": "require_messages_history", "load_messages_count": "' + loadMessagesCount + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
    socket.send('{"message": "mark_messages_as_read", "chat_id": "' + json.chat_id + '"}');

    clearUnreadMessagesCount(json.chat_id);
}



function getCurrentTime() {
    const currentDate = new Date();

    const hours = currentDate.getHours();
    const minutes = currentDate.getMinutes();

    const formattedHours = hours < 10 ? '0' + hours : hours;
    const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;

    return `${formattedHours}:${formattedMinutes}`;
}


/* Scroll */


function scrollToBottom() {
    let chatMessages = document.getElementById('messages');

    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function scrollToCurrentMessage() {
    let chatMessages = document.getElementById('messages');

    chatMessages.scrollTop = chatMessages.scrollHeight - previousScrollHeight;
}


/* Notifications */


function notifyMe(json) {
    if (!("Notification" in window)) {
        alert("This browser does not support desktop notification");
    } else if (Notification.permission === "granted") {
        const notification = new Notification("Chat", {
            body: 'New message from ' + json.user.name,
            icon: '/images/free-icon-chat-bubble-6068634.png',
        });
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then((permission) => {
            if (permission === "granted") {
                const notification = new Notification("Chat", {
                    body: 'New message from ' + json.user.name,
                    icon: '/images/free-icon-chat-bubble-6068634.png',
                });
            }
        });
    }
}


/* Events */


window.addEventListener("DOMContentLoaded", (event) => {
    let chatMessages = document.getElementById('messages');
    let textArea = document.getElementById('text');
    let sendButton = document.getElementById('send');
    let search = document.getElementById('default-search');

    chatMessages.addEventListener('scroll', function () {
        const scrollTop = chatMessages.scrollTop;
        const scrollHeight = chatMessages.scrollHeight;


        if (scrollTop === 0 && loadMessagesCount !== 0) {
            previousScrollHeight = scrollHeight;
            socket.send('{"message": "require_messages_history", "load_messages_count": "' + loadMessagesCount + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
        }
    });

    textArea.addEventListener('input', function () {
        if (textArea.value.trim() !== '') {
            sendButton.removeAttribute('disabled');
        } else {
            sendButton.setAttribute('disabled', 'disabled');
        }
    });

    search.addEventListener('input', function () {
        if (search.value.trim() !== '') {
            socket.send('{"message": "search_chats", "value": "' + search.value + '"}');
        } else {
            socket.send('{"message": "load_data"}');
        }
    })
});



