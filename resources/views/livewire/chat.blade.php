<div class="chat-container">
    <div class="chat-list">
        <div class="chat-list-header">
            Chat list
        </div>

        @foreach($chats as $chat)
            <ul id="chat-list">
                <li class="chat-item" wire::click="selectChat">
                    <p> {{$chat}} </p>
                </li>
            </ul>
        @endforeach
    </div>
    <div class="chat" id="chat">
        <div class="chat-header">
            Chat
        </div>

        <div class="chat-messages" id="messages">

        </div>

        <div class="chat-input">
            <x-input placeholder="Type your message..." id="text" inline>
                <x-slot:append>
                    <x-button icon="o-paper-airplane" class="btn-primary rounded-l-none" id="send" onclick="sendMessage()"/>
                </x-slot:append>
            </x-input>
        </div>
    </div>
    <div class="no-chat" id="no-chat">
        <p>Select a chat to start messaging</p>
    </div>
    <div class="online-users">
        <div class="online-users-header">
            <div class="chat-online" id="online">

            </div>
        </div>
        @foreach($users as $user)
            <x-list-item :item="$user" link="/docs/installation" id="user-list"/>
        @endforeach
    </div>
</div>
