var app = { 
    init: function() {
        this.chats = [];
        this.logged = false;
        this.clientUid = false;
        this.connection = websocket.init();

        this.bindComponents();
        this.bindEvents();

        this.hideChatContainer();
                        
        return this;
    },

    bindComponents: function(){
        this.chatsContainer = document.getElementById('chats');
        this.loginBox = loginBox('login');
    },

    bindEvents: function() {
        this.loginBox.addLoginClickListener(this.loginToChat.bind(this))
        this.connection.addAfterConnectionListener(this.connectionCreated.bind(this));
        this.connection.addMessageListener(this.messageArrive.bind(this));
    },
    
    loginToChat: function(username, password) {
        this.connection.sendMessage({action:'login', username: username, token: password});
    },
    
    connectionCreated: function() {       
        this.connection.sendMessage({action:'getUid'});
        this.loginToChatWithToken();
    },
    
    loginToChatWithToken: function() {
        var operatorToken =  localStorage.getItem('operatorToken') || null;
        if(operatorToken !== null){
            this.connection.sendMessage({action:'loginWithToken', token: operatorToken});            
        }
    },
    
    messageArrive: function(data) {
        if(data.action == "uid"){
            this.clientUid = data.uid;
        }
        
        if(data.action == "loginSuccess"){
            localStorage.setItem('operatorToken', data.token);
            this.logged = true;
            this.loginBox.hide();
            this.connection.sendMessage({action:'getAllOpenChats'});
        }
        
        if(data.action == "loginWithTokenSuccess"){
            this.logged = true;
            this.loginBox.hide();
            this.connection.sendMessage({action:'getAllOpenChats'});
        }
        
        if(data.action == "allOpenChats") {
            var chats = data.chats;
            
            for (var key in chats) {
                this.createChatbox(chats[key]);
                this.connection.sendMessage({action:'getChatHistory', chatUid:chats[key]});
            }
            
            this.showChatContainer();
        }
        
        if(data.action == "chatHistory"){
           this.chats[data.chatUid].clearMesages();

           for(var key in data.chatHistory.messages){
             var message = data.chatHistory.messages[key];
             
             if (message.type=='operator') {
               this.chats[data.chatUid].addMessageSource(message.message);
             } 
             
             if (message.type=='client') {
               this.chats[data.chatUid].addMessageTarget(message.message);
             }
           }
        }
        
        if(data.action == "clientAddMessageToChat"){
           this.chats[data.chatUid].addMessageTarget(data.message);
        }
        
        if(data.action == "operatorAddMessageToChat"){
           this.chats[data.chatUid].addMessageSource(data.message);
        }

        if(data.action == "chatOpen"){
           this.createChatbox(data.chatUid);
           this.connection.sendMessage({action:'getChatHistory', chatUid:data.chatUid});
        }   
        
        if(data.action == "chatClosed"){
           this.chats[data.chatUid].chatboxWrapper.remove();
           this.chats[data.chatUid] = null;
        }       
    },
    
    createChatbox: function(client){
        this.chatsContainer.appendChild(this.el('<div id="'+client+'" class="chatbox__wrapper"></div>'))
        this.chats[client] = chatbox(client);
        this.chats[client].setTitle(client);
        this.chats[client].addSendMessageListener(this.sendMessageClick.bind(this))
        this.chats[client].show();
    },
       
    sendMessageClick: function(message, chatbox) {
        
        var data = {
            action: "addOperatorMessageToChat",
            chatUid: chatbox.chatboxWrapper.id,
            message: message
        }

        this.connection.sendMessage(data);
    },
    
    removeChatbox: function(client){
        document.getElementById(client).remove();
        delete this.chats[client];
    },

    showChatContainer:function (){
        this.chatsContainer.style.display = 'block';
    },
    
    hideChatContainer:function (){
        this.chatsContainer.style.display = 'none';
    },
    
    el: function(html) {
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      return div.firstChild; 
    }
}.init();
