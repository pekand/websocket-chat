var app = { 
    init: function() {
        this.chats = [];
        this.logged = false;
        this.clientUid = false;
        this.connection = websocket.init(WEBSOCKET_ENDPOINT);

        this.bindComponents();
        this.bindEvents();

        this.hideChatContainer();
                        
        return this;
    },

    bindComponents: function(){
        this.chatsContainer = document.getElementById('chats');
        this.loginPage = document.getElementById('login-page');
        this.chatsPage = document.getElementById('chats-page');
        this.loginBox = loginBox('login');
        this.toolbar = toolbar('toolbar');
        this.toolbar.addButton('logout', this.logoutClick.bind(this), []);
        this.toolbar.addButton('shutdown', this.shutdownClick.bind(this), []);
    },

    logoutClick: function(){
        this.connection.sendMessage({action:'logout'});                 
    },
    
    shutdownClick: function(){
        this.connection.sendMessage({action:'shutdown'});                 
    },
    
    bindEvents: function() {
        this.loginBox.addLoginClickListener(this.loginToChat.bind(this))
        this.connection.addAfterConnectionListener(this.connectionCreated.bind(this));
        this.connection.addMessageListener(this.messageArrive.bind(this));
    },
    
    loginToChat: function(username, password) {
        if(username.length>0 && password.length>0) {
            this.connection.sendMessage({action:'login', username: username, password: password});
        } else {
            this.loginBox.shake();
        }
    },
    
    connectionCreated: function() {       
        this.connection.sendMessage({action:'getUid'});
        this.loginToChatWithToken();
    },
    
    loginToChatWithToken: function() {
        var operatorToken =  localStorage.getItem('operatorToken') || null;
        if(operatorToken !== null){
            this.connection.sendMessage({action:'loginWithToken', token: operatorToken});            
        } else {
            this.showLoginPage();
        }
    },
    
    messageArrive: function(data) {
        if(data.action == "uid"){
            this.clientUid = data.uid;
        }
        
        if(data.action == "loginSuccess"){
            localStorage.setItem('operatorToken', data.token);
            history.replaceState({success:true}, 'Websocket Console', "/");
            this.logged = true;
            this.connection.sendMessage({action:'getAllOpenChats'});
        }
        
        if(data.action == "logoutSuccess"){
            localStorage.removeItem('operatorToken');
            this.showLoginPage();
        }

        if(data.action == "loginFailed"){
            this.loginBox.shake();
        }
        
        if(data.action == "loginWithTokenSuccess"){
            this.logged = true;
            this.connection.sendMessage({action:'getAllOpenChats'});
        }
        
        if(data.action == "loginWithTokenFailed"){
            this.showLoginPage();
        }
        
        if(data.action == "allOpenChats") {
            var chats = data.chats;
            
            for (var key in chats) {
                this.createChatbox(chats[key]);
                this.connection.sendMessage({action:'getChatHistory', chatUid:chats[key]});
            }
            
            this.showChatContainer();
            this.showChatsPage();
        }
        
        if(data.action == "chatHistory"){
           this.chats[data.chatUid].clearMesages();

           for(var key in data.chatHistory.messages){
             var message = data.chatHistory.messages[key];
             
             if (message.role=='operator') {
               this.chats[data.chatUid].addMessageSource(message.message);
             } 
             
             if (message.role=='client') {
               this.chats[data.chatUid].addMessageTarget(message.message);
             }
           }
        }
        
        if(data.action == "clientAddMessageToChat"){
            if(this.chats[data.chatUid] !== undefined) {
                this.chats[data.chatUid].unhide();
                this.chats[data.chatUid].addMessageTarget(data.message, true);
            }
        }
        
        if(data.action == "operatorAddMessageToChat"){
            if(this.chats[data.chatUid] !== undefined) {
                this.chats[data.chatUid].unhide();
                this.chats[data.chatUid].addMessageSource(data.message);
            }
        }

        if(data.action == "chatOpen"){
           this.createChatbox(data.chatUid);
           this.connection.sendMessage({action:'getChatHistory', chatUid:data.chatUid});
        }   
        
        if(data.action == "chatClosed"){ 
            if(this.chats[data.chatUid] !== undefined) {
               this.chats[data.chatUid].addMessageTarget("[Client left chat]", true);
            }
        }       
    },
    
    createChatbox: function(client){
        this.chatsContainer.appendChild(this.el('<div id="'+client+'" class="chatbox__wrapper"></div>'))
        this.chats[client] = chatbox(client, true);
        this.chats[client].setTitle(client);
        this.chats[client].addCloseChatListener(this.closeButtonClick.bind(this));
        this.chats[client].addSendMessageListener(this.sendMessageClick.bind(this));
        this.chats[client].show();
    },
       
    closeButtonClick: function(chatbox) {
        this.chats[chatbox.chatboxId].hide();
    },

    sendMessageClick: function(message, chatbox) {
        
        if(message.length>10000){
          return;
        }

        var data = {
            action: "addOperatorMessageToChat",
            chatUid: chatbox.chatboxWrapper.id,
            message: message,
            type: "message"
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
    
    
    hidePages:function (){
        this.loginPage.style.display = 'none';
        this.chatsPage.style.display = 'none';
    },
    
    showLoginPage:function (){
        this.hidePages();
        this.loginPage.style.display = 'block';
    },
    
    showChatsPage:function (){
      this.hidePages();  
      this.chatsPage.style.display = 'block';
    },
    
    el: function(html) {
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      return div.firstChild; 
    }
}.init();
