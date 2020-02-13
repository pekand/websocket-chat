function loginBox(loginBoxId){return {
    init: function(loginBoxId) {
        this.loginClickListeners = [];
        this.bindComponents(loginBoxId);
        this.bindEvents();

        return this;
    },

    bindComponents: function(loginBoxId){
        this.loginBoxWrapper = document.getElementById(loginBoxId);
        
        this.loginBoxWrapper.appendChild(this.el('<div class="login"><div class="login__title">Login To Your Account</div><div class="login__message"></div><form><input type="text" placeholder="username" class="login__username" /><input type="password" placeholder="password" class="login__password"/><button class="login__send">login</button></form></div>'));
       
        this.loginBox = this.loginBoxWrapper.getElementsByClassName("login")[0];
        this.loginBoxTitle = this.loginBox.getElementsByClassName("login__title")[0];
        this.loginBoxMessage = this.loginBox.getElementsByClassName("login__message")[0];
        this.loginBoxForm = this.loginBox.getElementsByTagName("form")[0];
        this.usernameInput = this.loginBox.getElementsByClassName("login__username")[0];
        this.paswordInput = this.loginBox.getElementsByClassName("login__password")[0];
        this.sendButton = this.loginBox.getElementsByClassName("login__send")[0];
    },

    bindEvents: function() {
        this.sendButton.addEventListener("click", this.sendLoginClick.bind(this));
        this.loginBoxForm.addEventListener("submit", this.formSubmit.bind(this));
    },

    sendLoginClick: function() {
        var username = this.usernameInput.value.trim();
        var password = this.paswordInput.value;
               
        for (var listener of this.loginClickListeners) {
            if (listener && typeof(listener) === "function") {
                listener(username, password, this);
            }
        }
    },
    
    formSubmit: function(e) {
        e.preventDefault();
    },
        
    addLoginClickListener: function(callback) {
        this.loginClickListeners.push(callback);
    },
    
    shake:function (){
        
        this.usernameInput.value = '';
        this.paswordInput.value = '';
        this.usernameInput.focus();
        
        this.loginBox.classList.add("login--shake");
        this.loginBox.addEventListener("webkitAnimationEnd", function(){
            this.loginBox.classList.remove("login--shake");    
        }.bind(this), false);
    },
    
    el: function(html) {
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      return div.firstChild; 
    }
}.init(loginBoxId)};
