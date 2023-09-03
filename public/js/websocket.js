const socket = new WebSocket("ws://localhost:8080");

socket.onopen = function (e) {
    console.log('open');
    socket.send('{"message": "connection_identify", "user_id": "' + user_id + '"}');
    socket.send('{"message": "load_data"}');
    socket.send('{"message": "require_messages_history", "load_messages_count": "' + load_messages_count + '", "default_messages_count_load": "' + DEFAULT_MESSAGES_COUNT_LOAD + '"}');
    console.log("[open] Connection successful");
};

socket.onmessage = function (event) {
    let json = JSON.parse(event.data);

    switch (json.message) {
        case 'message':
            showNewMessage(json);
            break;
        case 'change_last_message':
            changeLastMessage(json);
            break;
        case 'load_history':
            showMessagesHistory(json);
            break;
        case 'online_users_count':
            showOnlineUsersCount(json);
            break;
        case 'online_users_list':
            showOnlineUsersList(json);
            break;
        case 'load_chats':
            loadChats(json);
            break;
        case 'require_select_chat':
            requireSelectChat();
            break;
        case 'select_chat':
            selectChatFromOnlineUsers(json);
            break;
        case 'chat_selected':
            chatSelected();
            break;
        case 'mark_messages_as_read':
            markMessagesAsRead();
            break;
        case 'show_unread_messages_count':
            showUnreadMessagesCount(json);
            break;
        case 'mark_chat_as_online':
            markChatAsOnline(json);
            break;
        case 'mark_chat_as_offline':
            markChatAsOffline(json);
            break;
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
