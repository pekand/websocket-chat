function chatbox(chatboxId, closeButton){return {
    sendMessageListeners:[],
    openChatListeners:[],
    closeChatListeners:[],
    
    init: function(chatboxId, closeButton) {
        this.chatboxId = chatboxId;
        this.closeButton = closeButton;
        this.bindComponents(chatboxId);
        this.bindEvents();

        return this;
    },

    bindComponents: function(chatboxId){
        this.chatboxWrapper = document.getElementById(chatboxId);
        
        this.chatboxWrapper.appendChild(this.el('<div class="chatbox chatbox-hidden"><div class="chatbox__header"><div class="chatbox__title">Mesasges</div><div class="chatbox__closebutton '+(!this.closeButton?"hide":"")+'">x</div></div><div  class="chatbox__messages"></div><div class="chatbox__footer"><input class="chatbox__newmessage" type="text" placeholder="Type message..."><button class="chatbox__send">Send</button></div></div>'));
       
        this.chatbox = this.chatboxWrapper.getElementsByClassName("chatbox")[0];
       
        this.chatboxHeader = this.chatbox.getElementsByClassName("chatbox__header")[0];
        this.chatboxTitle = this.chatboxHeader.getElementsByClassName("chatbox__title")[0];
        this.chatboxCloseButton = this.chatboxHeader.getElementsByClassName("chatbox__closebutton")[0];
        this.chatboxMessges = this.chatbox.getElementsByClassName("chatbox__messages")[0];
        this.chatboxFooter = this.chatbox.getElementsByClassName("chatbox__footer")[0];
        this.newMesageInput = this.chatboxFooter.getElementsByClassName("chatbox__newmessage")[0];
        this.sendButton = this.chatboxFooter.getElementsByClassName("chatbox__send")[0];
    },

    bindEvents: function() {
        if(!this.closeButton) this.chatboxTitle.addEventListener("click", this.titleClick.bind(this));
        if(this.closeButton) this.chatboxCloseButton.addEventListener("click", this.closeButtonClick.bind(this));
        this.sendButton.addEventListener("click", this.sendMessageClick.bind(this));
        this.newMesageInput.addEventListener("keyup", this.messageInputKeyUpClick.bind(this));
    },

    titleClick: function() {
        if(this.chatbox.classList.contains("chatbox-closed")) {
            this.chatbox.classList.remove("chatbox-closed");
            this.chatbox.classList.add("chatbox-open");
            this.chatboxMessges.scrollTop = this.chatboxMessges.scrollHeight;

            for (var listener of this.openChatListeners) {
                if (listener && typeof(listener) === "function") {
                    listener(this);
                }
            }

        } else
        if(this.chatbox.classList.contains("chatbox-open")) {
            this.chatbox.classList.remove("chatbox-open");
            this.chatbox.classList.add("chatbox-closed");

            for (var listener of this.closeChatListeners) {
                if (listener && typeof(listener) === "function") {
                    listener(this);
                }
            }
        }
    },

    closeButtonClick: function() {
        for (var listener of this.closeChatListeners) {
            if (listener && typeof(listener) === "function") {
                listener(this);
            }
        }
    },

    sendMessageClick: function() {
        var message = this.newMesageInput.value.trim();
        
        if (message == ""){
            return;    
        }
        
        for (var listener of this.sendMessageListeners) {
            if (listener && typeof(listener) === "function") {
                listener(message, this);
            }
        }
        
        this.addMessageSource(message, true);
        
        this.newMesageInput.value = '';
        this.newMesageInput.focus();
    },
    
    addMessageSource: function(message, animation) {
        if (typeof animation === 'undefined'){
            animation = false;    
        }
        
        var bounce = '';
        if (animation){
            bounce = 'chatbox__message--bounce';    
        }

        var messageEl = this.el('<div class="chatbox__message"><div class="chatbox__message__text chatbox__message__text--right '+bounce+'">'+message+'</div></div>');       
        this.chatboxMessges.appendChild(messageEl);
        this.chatboxMessges.scrollTop = this.chatboxMessges.scrollHeight;
    },
    
    addMessageTarget: function(message, animation) {
        if (typeof animation === 'undefined'){
            animation = false;    
        }
        
        var bounce = '';
        if (animation){
            bounce = 'chatbox__message--bounce';    
        }
        
         var messageEl = this.el('<div class="chatbox__message"><div class="chatbox__message__text '+bounce+'">'+message+'</div></div>')
         this.chatboxMessges.appendChild(messageEl);
         this.chatboxMessges.scrollTop = this.chatboxMessges.scrollHeight;
    },
    
    messageInputKeyUpClick: function(e) {
        e.preventDefault();
        if (e.keyCode === 13) {
            this.sendMessageClick();
        }
    },
    
    addSendMessageListener: function(callback) {
        this.sendMessageListeners.push(callback);
    },

    addOpenChatListener: function(callback) {
        this.openChatListeners.push(callback);
    },

    addCloseChatListener: function(callback) {
        this.closeChatListeners.push(callback);
    },
    
    setTitle: function(title) {
        this.chatboxTitle.innerHTML = '';
        this.chatboxTitle.appendChild(document.createTextNode(title));
    },
    
    clearMesages: function(title) {
        this.chatboxMessges.innerHTML = '';
    },
    
    show:function (){
        if(this.chatbox.classList.contains("chatbox-hidden")) {
            this.chatbox.classList.remove("chatbox-hidden");
            this.chatbox.classList.add("chatbox-closed");
        }
    },

    open:function (){
        if(this.chatbox.classList.contains("chatbox-closed")) {
            this.chatbox.classList.remove("chatbox-closed");
            this.chatbox.classList.add("chatbox-open");
            this.chatboxMessges.scrollTop = this.chatboxMessges.scrollHeight;
        }
    },
    
    hide:function (){
        if(this.chatbox.classList.contains("chatbox-closed") || this.chatbox.classList.contains("chatbox-open")) {
            this.chatbox.classList.add("chatbox-hidden");
            this.chatbox.classList.remove("chatbox-closed");
            this.chatbox.classList.remove("chatbox-open");
        }
    },

    unhide:function (){
        if(this.chatbox.classList.contains("chatbox-hidden")) {
            this.chatbox.classList.remove("chatbox-hidden");
            this.chatbox.classList.add("chatbox-open");
        }
    },

    remove:function (){
        this.chatboxWrapper.remove();
    },
    
    el: function(html) {
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      return div.firstChild;
    }

}.init(chatboxId, closeButton)};
