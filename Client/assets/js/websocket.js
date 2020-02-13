var websocket = {
    messageListeners:[],
    afterConnectionListeners:[],
    afterDisconnectionListeners:[],
    
    init: function(chatboxId) {
        this.createConnection();
        this.bindEvents();
        return this;
    },
    
    createConnection: function() {
        this.conn = new WebSocket('ws://127.0.0.1:8080');
        //this.conn = new WebSocket('wss://socket.pekand.doc:8080');
        //this.conn = new WebSocket('wss://socket.pekand.com:8080');
        this.conn.onopen = this.connectionOpen.bind(this);
        this.conn.onclose = this.connectionClose.bind(this);
        this.conn.onerror = this.connectionError.bind(this);
        this.conn.onmessage = this.getMessage.bind(this);
    },

    connectionOpen: function() {
        L("Connection is open...");
        
        for (var listener of this.afterConnectionListeners) {
            if (listener && typeof(listener) === "function") {
                listener();
            }
        }
    },

    connectionClose: function() {
        L("Connection is closed...");
        
        for (var listener of this.afterDisconnectionListeners) {
            if (listener && typeof(listener) === "function") {
                listener();
            }
        }
    },

    connectionError: function() {
        L('Error: ' + error.message);
    },

    getMessage: function(e) {
        L("Message from server:" + e.data);

        var data = JSON.parse(e.data);

        if (data.action == 'ping') {
            this.sendMessage({ action: "pong" });
            return;
        }
        
        for (var listener of this.messageListeners) {
            if (listener && typeof(listener) === "function") {
                listener(data);
            }
        }
    },
    
    addMessageListener: function(callback) {
        this.messageListeners.push(callback);
    },
    
    addAfterConnectionListener: function(callback) {
        this.afterConnectionListeners.push(callback);
    },
    
    addAfterDisconnectionListener: function(callback) {
        this.afterDisconnectionListeners.push(callback);
    },
    
    sendMessage: function(data) {
        var message = JSON.stringify(data);
        L("Message to server:" + message);

        if (this.conn.readyState !== WebSocket.CLOSED) {
            this.conn.send(message);
            return true;
        }
        
        return false;
    },
    
    bindEvents: function() {
        window.addEventListener("beforeunload", this.windowUnload.bind(this));
        setInterval(this.connectionCheck.bind(this), 1000);
    },
    
    windowUnload: function(e) {
        if (this.conn.readyState !== WebSocket.CLOSED) {
            this.sendMessage({ action: "close" });
            this.conn.close();
        }
    },
    
    connectionCheck: function() {
        if (this.conn.readyState === WebSocket.CLOSED) {
            this.createConnection();
        }
    },
}
