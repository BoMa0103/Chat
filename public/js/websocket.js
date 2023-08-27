const socket = new WebSocket("ws://localhost:8080");

socket.onopen = function (e) {
    console.log('open');
    socket.send('{"message": "connection_identify", "user_id": "' + user_id + '"}');
    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
    console.log("[open] Connection successful");
};

socket.onmessage = function (event) {
    let json = JSON.parse(event.data);

    if (json.message === 'message') {
        showNewMessage(json);
    } else if (json.message === 'load_history') {
        showMessagesHistory(json);
    } else if (json.message === 'online_users_count') {
        showOnlineUsersCount(json);
    } else if (json.message === 'online_users_list') {
        showOnlineUsersList(json);
    } else if (json.message === 'load_chats') {
        loadChats(json);
    } else if (json.message === 'require_select_chat') {
        requireSelectChat(json);
    } else if (json.message === 'chat_selected') {
        chatSelected(json);
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
