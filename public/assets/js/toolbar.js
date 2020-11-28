function toolbar(tolBarId){return {
    init: function(tolBarId) {        
        this.bindComponents(tolBarId);
        this.bindEvents();
        this.buttons = [];

        return this;
    },

    bindComponents: function(tolBarId){
        this.toolBarWrapper = document.getElementById(tolBarId);
        this.toolBarWrapper.appendChild(this.el('<div class="toolbar"></div>'));
        this.toolBar = this.toolBarWrapper.getElementsByClassName("toolbar")[0];
    },

    bindEvents: function() {
        
    },
    
    addButton: function(title, callback, options){
        var button =this.el('<button class="toolbar__button"></button>');
        button.appendChild(document.createTextNode(title));
        button.addEventListener('click', callback, false);
        this.toolBar.appendChild(button);
        this.buttons.push(button);
    },
    
    el: function(html) {
      var div = document.createElement('div');
      div.innerHTML = html.trim();
      return div.firstChild; 
    }
}.init(tolBarId)};
