//>>built
define("dojox/form/_BusyButtonMixin",["dojo/_base/lang","dojo/dom-attr","dojo/dom-class","dojo/dom-construct","dijit/form/Button","dijit/form/DropDownButton","dijit/form/ComboButton","dojo/i18n","dojo/i18n!dijit/nls/loading","dojo/_base/declare"],function(_1,_2,_3,_4,_5,_6,_7,_8,_9,_a){return _a("dojox.form._BusyButtonMixin",null,{isBusy:false,isCancelled:false,busyLabel:"",timeout:null,useIcon:true,postMixInProperties:function(){this.inherited(arguments);if(!this.busyLabel){this.busyLabel=_8.getLocalization("dijit","loading",this.lang).loadingState;}},postCreate:function(){this.inherited(arguments);this._label=this.containerNode.innerHTML;this._initTimeout=this.timeout;if(this.isBusy){this.makeBusy();}},makeBusy:function(){this.isBusy=true;this.isCancelled=false;if(this._disableHandle){this._disableHandle.remove();}this._disableHandle=this.defer(function(){this.set("disabled",true);});this.setLabel(this.busyLabel,this.timeout);},cancel:function(){this.isCancelled=true;if(this._disableHandle){this._disableHandle.remove();}this.set("disabled",false);this.isBusy=false;this.setLabel(this._label);if(this._timeout){clearTimeout(this._timeout);}this.timeout=this._initTimeout;},resetTimeout:function(_b){if(this._timeout){clearTimeout(this._timeout);}if(_b){this._timeout=setTimeout(_1.hitch(this,function(){this.cancel();}),_b);}else{if(_b==undefined||_b===0){this.cancel();}}},setLabel:function(_c,_d){this.label=_c;while(this.containerNode.firstChild){this.containerNode.removeChild(this.containerNode.firstChild);}this.containerNode.appendChild(_4.toDom(this.label));if(this.showLabel==false&&!_2.get(this.domNode,"title")){this.titleNode.title=_1.trim(this.containerNode.innerText||this.containerNode.textContent||"");}if(_d){this.resetTimeout(_d);}else{this.timeout=null;}if(this.useIcon&&this.isBusy){var _e=new Image();_e.src=this._blankGif;_2.set(_e,"id",this.id+"_icon");_3.add(_e,"dojoxBusyButtonIcon");this.containerNode.appendChild(_e);}},_onClick:function(e){if(!this.isBusy){this.inherited(arguments);if(!this.isCancelled){this.makeBusy();}}}});});