var app = {
    init: function() {
        this.connection  = websocket.init(WEBSOCKET_ENDPOINT);
        this.chatUid = localStorage.getItem('chatUid') || '';
        this.bindComponents();
        this.bindEvents();
        return this;
    },

    bindComponents: function(){
         this.chatbox = chatbox("chatbox", false);
         this.body = document.getElementsByTagName('body')[0];
    },

    bindEvents: function() {
        this.connection.addAfterConnectionListener(this.connectionCreated.bind(this));
        this.connection.addMessageListener(this.messageArrive.bind(this));
        this.chatbox.addSendMessageListener(this.sendMessageClick.bind(this));

        this.chatbox.addOpenChatListener(this.openChatClick.bind(this));
        this.chatbox.addCloseChatListener(this.closeChatClick.bind(this));


        this.body.addEventListener("webkitAnimationEnd", this.afterAnimations.bind(this), false);
    },

    afterAnimations: function (e){
      if(e.target.classList.contains("chatbox__message--bounce")) {
        e.target.classList.remove("chatbox__message--bounce");
      }
    },

    connectionCreated: function() {       
        this.connection.sendMessage({action:'getUid'});
        this.connection.sendMessage({action:'openChat', chatUid:this.chatUid});
    },
    
    messageArrive: function(data) {       

        if(data.action == "chatUid" && data.chatUid != 'null' && data.chatUid != '' && data.chatUid != null) {
           this.chatUid = data.chatUid;
           this.chatbox.setTitle("Chat");
           localStorage.setItem('chatUid', data.chatUid);
           
           if(data.operatorStatus != "offline") {
              this.chatbox.show();
           }
           
           this.chatbox.clearMesages();

           for(var key in data.chatHistory.messages) {
             var message = data.chatHistory.messages[key];
             
             if (message.role=='operator' && message.type == "message") {
               this.chatbox.addMessageTarget(message.message);
             } 
             
             if (message.role=='client' && message.type == "message") {
               this.chatbox.addMessageSource(message.message);
             }
           }
           
           if(data.operatorStatus == 'online') {
             this.chatbox.addMessageTarget("[Chat is online]");
           } else {
             this.chatbox.addMessageTarget("[Chat is offline]");
           } 
        }
        
        if(data.action == "operatorAddMessageToChat"){
           this.chatbox.open();
           this.chatbox.addMessageTarget(data.message, true);
        }
        
        if(data.action == "clientAddMessageToChat"){
           this.chatbox.addMessageSource(data.message);
        }
        
        if(data.action == "operatorsDisconected" || data.action == "operatorLeft") {
           this.chatbox.addMessageTarget("Chat is offline");
           this.chatbox.hide();
        }

        if(data.action == "operatorConnected") {
           this.chatbox.show();
           this.chatbox.addMessageTarget("Operator is online");
        }
    },
       
    sendMessageClick: function(message) {
    	
        if(message.length>10000){
          return;
        }
        
        var data = {
            action: "addClientMessageToChat",
            chatUid: this.chatUid,
            message: message,
            type: "message"
        }

        this.connection.sendMessage(data);
    },


    openChatClick: function(chatbox) {
        var data = {
            action: "addClientMessageToChat",
            chatUid: this.chatUid,
            message: "[Chat opened]",
            type: "info"
        }

        this.connection.sendMessage(data);
    },

    closeChatClick: function(chatbox) {
        var data = {
            action: "addClientMessageToChat",
            chatUid: this.chatUid,
            message: "[Chat closed]",
            type: "info"
        }

        this.connection.sendMessage(data);
    },
   
}.init();
