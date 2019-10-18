
var tx_vjchat_pi1_js_chat_instance = null;

function tx_vjchat_pi1_js_chat() {

	/* ==================================================== = = = = = = */
	/* SECTION I:		CONFIGURATION  								*/
	/* --------------------------------------------- - - - - - - */



	this.refreshMessagesTime 	= 5000;
	this.refreshUserlistTime 	= 10000;

	tx_vjchat_pi1_js_chat_instance 	= "chat_instance";
	this.maxActiveRequests		= 3;
	this.popup					= true;
	this.debug                  = true;
	this.userlistPMContent		= "PM";
	this.userlistPRContent		= "PR";
	this.userlistPMInfo 		= "Send a private message to \'%s\'";
	this.userlistPRInfo 		= "Open a new room and invite \'%s\'";

		talkToNewRoomName:	"###TALK_TO_ROOM_NAME###",

	this.allowPrivateMessages	= false;
	this.allowPrivateRooms		= false;
	this.talkToNewRoomName		= 'New Room with %s';
	this.checkFullTime			= 20000;
	this.checkFullStatusElement = $('#tx-vjchat-full-jsstatus');
	this.tooltipOffsetX			= 20;
	this.tooltipOffsetY			= 10;
	this.autoFocus				= false;
	this.chatbuttonson 			= new Array();
	this.chatbuttonsoff 		= new Array();
	this.chatbuttonskeys 		= new Array();
	this.chatWindow				= window;

	var globalInstanceName = tx_vjchat_pi1_js_chat_instance;

	this.messageStack = new Array(); // collection of messages that will be send to server
	this.receivedMessages = new Array(); // collection of ids
	this.oldMessage = ""; 	// saves message for avoiding duplicates entries

	var userList = new Array();

	var self = null;
	tx_vjchat_pi1_js_chat_instance = this;

	/* ==================================================== = = = = = = */
	/* SECTION II:		MAIN/COMMON									*/
	/* --------------------------------------------- - - - - - - */


	this.init = function() {
		// apply configuration

		initConfig = $('#tx-vjchat-config') ;
		this.roomId					= initConfig.data('roomid');
		this.userId					= initConfig.data('userid');
		this.scriptUrl 				= initConfig.data('scripturl');
		this.leaveUrl 				= initConfig.data('leaveurl');
		this.newWindowUrl 			= initConfig.data('newwindowurl');
		this.initialId 				= initConfig.data('initialid');
		this.charset 				= 'utf-8';
		this.lang 					= initConfig.data('lang');

		this.usernameGlue 			= initConfig.data('usernameglue');
		this.usernamesFieldGlue 	= initConfig.data('usernamesfieldglue');
		this.messagesGlue 			= initConfig.data('messagesglue');
		this.idGlue 				= initConfig.data('idglue');
		this.showTime 				= initConfig.data('showtime');
		this.showEmoticons 			= initConfig.data('showemoticons');
		this.showStyles 			= initConfig.data('showstyles');
		this.popupJSWindowParams 	= initConfig.data('popupparams');
		this.talkToNewRoomName 		= initConfig.data('talktonewroomname');

		this.refreshMessagesTime 	= initConfig.data('refreshmessagestime');
		this.refreshUserlistTime 	= initConfig.data('refreshuserlisttime');
		this.allowPrivateMessages	= initConfig.data('allowprivatemessages');
		this.allowPrivateRooms		= initConfig.data('allowprivaterooms');

		this.inputElement 			= $('#txvjchatnewMessage');
		this.messagesElement 		= $('#tx-vjchat-messages');
		this.userListElement		= $('#tx-vjchat-userlist');
		this.emoticonsElement		= $('#tx-vjchat-emoticons');
		this.stylesElement			= $('#tx-vjchat-style');
		this.toolsElement			= $('#tx-vjchat-tools-container');
		this.storedMessagesElement  = $('#tx-vjchat-storedMessages');



		globalInstanceName = tx_vjchat_pi1_js_chat_instance;


		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			//alert('on: '+this.chatbuttonskeys[i]+' : '+name+ ' : '+this.chatbuttonson[i]);
			var name = this.chatbuttonskeys[i];
			var containerName = "#" + name+'-container';
			this.chatbuttonsoff[i] = $(containerName) ? $(containerName).html() : '';
		}


		if(Cookie.get('tx-vjchat-emoticons_visible') != null) {
			var show = (Cookie.get('tx-vjchat-emoticons_visible') == '1');
			this.setEmoticons(show);
		}
		else
			this.setEmoticons(this.showEmoticons);

		if(Cookie.get('tx-vjchat-style_visible') != null) {
			var show = (Cookie.get('tx-vjchat-style_visible') == '1');
			this.setStyle(show);
		}
		else
			this.setStyle(this.showStyles);

		if(Cookie.get('tx_vjchat_showtime') != null) {
			var show = Cookie.get('tx_vjchat_showtime') == '1';
			this.setAllTime(show);
		}
		else
			this.setAllTime(this.showTime );


		if(Cookie.get('tx_vjchat_autofocus') != null) {
			var show = Cookie.get('tx_vjchat_autofocus') == '1';
			this.setAutoFocus(show);
		}
		else
			this.setAutoFocus(this.autoFocus);

		 ;
		self = this;
		chat_instance = this;

	}

	/**
	 * Run chat
	 */
	this.run = function() {

		// set current id to initial id
		this.id = this.initialId;
		// console.log( "user: " +  this.userId + " Id: " +  this.initialId + " xharset =" + this.charset 	+ " test " +  $('#tx-vjchat-config').data('messagesGlue') ) ;


		$('#txvjchatnewMessage').keypress(function( event ) {
			if ( event.which == 13 || event.which == 10 ) {
				if( event.shiftKey ) {
					this.insertAtCursor(self.inputElement, "\r\n") ;
				} else {
					return self.submitMessage();
				}

			}

		}) ;

		this.messagesElement.show()
		this.toolsElement.show() ;
		this.inputElement.focus();


		// get messages
		this.getMessages(false);

		// get userlist
		this.getUserlist();



	}


	var handleAjaxError = function(t) {
		alert('Error ' + t.status + ' -- ' + t.statusText);
	}

	var handleAjax404 = function(t) {
		alert('Error 404: location "' + t.statusText + '" was not found.');
	}


	this.setValueToInput = function(value, addAtIfFirst) {

		if((this.inputElement.value == "") && (addAtIfFirst)) {
			this.insertAtCursor(this.inputElement, "@"+value+": ");
			return;
		}

		this.insertAtCursor(this.inputElement, value);

		// set focus
		// $("txvjchatnewMessage").focus();
	}

	this.newWindow = function() {
		tx_vjchat_openNewChatWindow(this.newWindowUrl, this.roomId);
	}






	this.openChatWindow = function(chatId) {
		tx_vjchat_openNewChatWindow(this.newWindowUrl, chatId);
	}

	this.helpInNewWindow = function() {
		var message = encodeURI("/help");
		var url = this.scriptUrl+'?r='+this.roomId+'&a=sm&charset='+this.charset+'&l='+this.lang+'&m='+message;

		var vHWindow = window.open(url,"helpwindow", this.popupJSWindowParams);
		vHWindow.focus();
	}

	/* ==================================================== = = = = = = */
	/* SECTION III:		MESSAGES	  								*/
	/* --------------------------------------------- - - - - - - */

	this.getMessages = function(noSetTimeout) {
		jQuery.ajax({
			type:       "GET",
			url:        this.scriptUrl ,
			cache:      false,
			data:       'r=' + this.roomId + '&a=gm&t=' + this.id + '&charset=' + this.charset + '&l=' + this.lang+ "&showJason=0",
			beforeSend:	function() {
				},
			success:    function(result) {
				getMessagesResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});



		if(!noSetTimeout) {
			this.runningto = window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages()", this.refreshMessagesTime);
		}
	}

	var getMessagesResponseHandler = function(t) {
		self.parseMessages(t);
	}


	this.htmlspecialchars = function(str,typ) {
		if(typeof str=="undefined") str="";
		if(typeof typ!="number") typ=2;
		typ=Math.max(0,Math.min(3,parseInt(typ)));
		var from=new Array(/&/g,/</g,/>/g);
		var to=new Array("&amp;","&lt;","&gt;");
		if(typ==1 || typ==3) {from.push(/'/g); to.push("&#039;");}
		if(typ==2 || typ==3) {from.push(/"/g); to.push("&quot;");}
		for(var i in from) str=str.replace(from[i],to[i]);
		return str;
	}

	this.parseString = function(string) {
		if(string == null )
			return null;

		return string.documentElement  ;

	}

	this.parseMessages = function(string) {
		if(string == this.oldMessage)
			return;
		var x = this.parseString(string );
		if(x == null) {
			return;
		}
		this.oldMessage = string;
		if(x.attributes == null) {
			return ;
		}
		var newid = 0;

		if(x.attributes[0])
			newid = x.attributes[0].nodeValue;

		if(newid) {
			if(newid == this.id)
				return;

			if(newid > 0) {
				this.id = newid;
			}

		}

		for (i = 0; i<x.childNodes.length; i++) {
			if(!x.childNodes[i])
				continue;
			if(!x.childNodes[i].firstChild)
				continue;
			if(!x.childNodes[i].firstChild.data)
				continue;

			var text = $.trim(x.childNodes[i].firstChild.data );
			var command = text.replace( /<.*?>/g, '' ); ;
			command = command.substr(1,4 ) ;
			switch (command ) {
				case "quit":
					window.setTimeout("tx_vjchat_pi1_js_chat_instance.quit()", 1500);
					break;
				case "stop":
					// alert("command = stop");
					clearTimeout(this.runningto) ;
					clearTimeout(this.runningTO2) ;
					break;
				case "rest":
					// alert("Command = restart");
					this.runningto = window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages(false)", this.refreshMessagesTime);

					break;
				default:
					this.createNewMessageNode(text);
					break;
			}
		}

	}

	this.quit = function() {
		clearTimeout(this.runningto) ;
	}

	this.notifyNewMessage = function() {
		if(this.autoFocus && this.popup) {
			this.chatWindow.focus();
		}
	}

	this.notifyUserListChange = function() {
	}



	/**
	 * This function adds a node to the chat window (element: "messages")
	 * It adds a "<div>" and put the message as HTML into it
	 */
	this.createNewMessageNode = function(message) {

		var idsearch = message.match(/<div id="([a-z0-9]*)\"/i);

		if(idsearch && idsearch[1]) {

			var id = idsearch[1];

			if(this.receivedMessages.inArray(id))
				return;
			else
				this.receivedMessages[this.receivedMessages.length] = id;
		}

		if(message == "" )		// skip empty values
			return;
		var mustReadDivs = false ;
		// + "</span></div></div>"; am ende abschneiden
		if( message.substr( message.length -19 , 19 ) == "</span></div></div>") {
			message = message.substr(0 , message.length -19);
			mustReadDivs = true ;
		}


		var messageArray = message.split("http") ;
		if( messageArray.length == 1 ) {
			messageArray = message.split("Http") ;
		}
		if( messageArray.length == 1 ) {
			messageArray = message.split("HTTP") ;
		}
		var messageParsed = '' ;
		var start = 0 ;
		var end = 99999 ;
		for (ii=0;ii<messageArray.length;ii++) {
			start = 0 ;
			end = messageArray[ii].indexOf(" ") ;
			length = messageArray[ii].length ;
			if (end < 1 ) {
				end = length ;
			}
			if ( messageArray[ii].substr(0,3) == "://") {
				start = 3 ;
			}

			if ( messageArray[ii].substr(0,4) == "s://") {
				start = 4;
			}

			if (start > 0 ) {
				messageParsed += '<a target="_blank" class="chatlink" href="http' + messageArray[ii].substr( 0 , end)  + '">' +  messageArray[ii].substr( start, (end-start) ) + '</a>' ;
				messageParsed += messageArray[ii].substr(end)  ;
			} else {
				messageParsed += messageArray[ii] ;
			}

		}
		if ( mustReadDivs === true ) {
			message = messageParsed + "</span></div></div>";
		}
		var newMessageNode = document.createElement("div");
		newMessageNode.innerHTML = message;

		var systemsearch = message.match(/<div class=\"(.*?tx-vjchat-system.*?)\"/i);
		var useridsearch = message.match(/tx-vjchat-user tx-vjchat-userid-([0-9]*?)\"\>/i);
		var usernamesearch = message.match(/tx-vjchat-user tx-vjchat-userid-([0-9]*?)\"\>([A-Z]*?)<\/span>/i);


		// notify if not a system message and message from another user
		if(!(systemsearch && systemsearch[1]) && (useridsearch && useridsearch[1] && useridsearch[1] != this.userId))
			this.notifyNewMessage();

		var className = document.createAttribute("class");
		className.nodeValue = "tx-vjchat-entry";
		newMessageNode.setAttributeNode(className);

		$('#tx-vjchat-messages').append(newMessageNode);
		// scroll down
		if($('#tx-vjchat-messages').length)
			$('#tx-vjchat-messages').scrollTop($('#tx-vjchat-messages')[0].scrollHeight - $('#tx-vjchat-messages').height());

		if( this.showTime ) {
			this.setAllTime(this.showTime) ;
		}

	}

	/**
	 * Submits an entered string by call sendMessageToServer()
	 */
	this.submitMessage = function() {

		// get entered message
		var newMessage = $('#txvjchatnewMessage').val() ;

		// clear input field
		$('#txvjchatnewMessage').val('') ;
		// toDO check why this was needed. .
		// Element.cleanWhitespace(self.inputElement);

		if($.trim(newMessage) == "undefined" || $.trim(newMessage) == "") {
			return;
		}

		// send message to server
		self.sendMessageToServer($.trim(newMessage));

		this.runningTO2 = window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages(true)", 500);

		return false;
	}

	/**
	 * Send a string to server
	 * It is possible that some error-report will be returned, so the XMLHttpRequest uses the same callback function as above.
	 * Therefore results will be treated like simple chat messages
	 */
	this.sendMessageToServer = function(message) {


		if(message) {
			message = encodeURIComponent(message) ;
			jQuery.ajax({
				type:       "post",
				url:        this.scriptUrl ,
				cache:      false,

				data:       "r="+this.roomId+"&a=sm&t="+this.id+"&l="+this.lang+"&m="+ message +"&charset="+this.charset,
				beforeSend:	function() {
				},
				success:    function(result) {
					getMessagesResponseHandler(result) ;
				} ,
				onFailure: handleAjaxError,
				on404: handleAjax404

			});
		}

		// call function again if stack has at least one element
		if(this.messageStack.length > 0) {
			this.runningTO3 = window.setTimeout("tx_vjchat_pi1_js_chat_instance.sendMessageToServer()", 500);
			return;
		}

	}
	this.sendMessage = function(message) {
		this.sendMessageToServer(message);
	}

	this.commitEntry = function(uid) {
		jQuery.ajax({
			type:       "get",
			url:        this.scriptUrl ,
			cache:      false,
			data:       "r="+this.roomId+"&t="+this.id+"&a=commit&uid="+uid+ "&showJason=0",

			beforeSend:	function() {
			},
			success:    function(result) {
				getMessagesResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});

		var messageNode = $("#tx-vjchat-entry-"+uid);
		messageNode.getAttributeNode("class").nodeValue = "tx-vjchat-committed";

		this.runningTO4 = window.setTimeout("tx_vjchat_pi1_js_chat_instance.hideEntry(" + uid + ") ", 1000);

	}

	this.hideEntry = function(uid) {
		var node = $("tx-vjchat-entry-"+uid).parentNode;
		node.parentNode.removeChild(node);

		if(this.storedMessagesElement.childNodes.length == 0) {
			this.toogleStoredMessages(false);
		}

	}

	this.storeEntry = function(uid) {

		this.toogleStoredMessages(true);

		var node = $("tx-vjchat-entry-"+uid).parentNode;
		this.storedMessagesElement.style.display = "block";
		this.storedMessagesElement.append(node);

		// remove link storemessage
		node.childNodes[1].remove($("tx-vjchat-storelink-"+uid));

		// scroll down
		this.storedMessagesElement.scrollTop = this.storedMessagesElement.scrollHeight;

	}


	this.toogleStoredMessages = function(show) {

		var storedMessages = this.storedMessagesElement;
		var messages = this.messagesElement;

		var isVisible = (storedMessages.style.display == "block");

		if(isVisible == show)
			return;

		var heightStyle = messages.style.height;

		result = heightStyle.match(/([0-9]*?)([a-z]{2}|\%)/i);
		var height = result[1];
		var hunit = result[2];

		if(!show) {

			var newHeight = Math.round(height * 2);

			storedMessages.style.display = "none";
			messages.style.height = newHeight + hunit;
			messages.style.top = 0;
		}
		else {

			var newHeight = Math.round(height / 2);
			storedMessages.style.height = newHeight-1 + hunit;
			storedMessages.style.display = "block";

			messages.style.height = newHeight + hunit;
			messages.style.top = newHeight + hunit;

		}

	}

	/* ==================================================== = = = = = = */
	/* SECTION IV:		USERLIST	  								*/
	/* --------------------------------------------- - - - - - - */

	this.getUserlist = function() {
		jQuery.ajax({
			type:       "get",
			url:        this.scriptUrl ,
			cache:      true,
			data:       'r='+this.roomId+'&a=gu&charset='+this.charset+ "&showJason=0",
			beforeSend:	function() {
			},
			success:    function(result) {
				getUserlistResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});

		this.runningTOul = window.setTimeout("tx_vjchat_pi1_js_chat_instance.getUserlist()", this.refreshUserlistTime);
	}

	var getUserlistResponseHandler = function(t) {
		self.parseUserlist(t);
	}

	this.lastULResponse = "";

	this.parseUserlist = function(string) {

		// update only if something has changed
		if(string == this.lastULResponse)
			return;

		this.lastULResponse = string;
		var x = this.parseString(string);

		if(x == null)
			return;


		// remove previous userlist
		$('#tx-vjchat-userlist').html('') ;

		// go through all Users and add them to the userlist window by calling createNewUserNode()
		for (i = 0; i<x.childNodes.length; i++) {
			if(!x.childNodes[i])
				continue;
			if(!x.childNodes[i].firstChild)
				continue;
			if(!x.childNodes[i].firstChild.data)
				continue;
			this.createNewUserNode( $.trim(x.childNodes[i].firstChild.data));
		}

		this.notifyUserListChange();

	}

	this.clearUserList = function() {
		$('#tx-vjchat-userlist').html('') ;
	}

	this.createNewUserNode = function(value) {

		if(value == "")		// skip empty values
			return;

		var parts = value.split(this.usernameGlue);

		var username = parts[0];

		var type = parts[1];
		var id = parts[2];
		var style = parts[3];

		userList["userid-"+id] = value;
		/*
		 create a node like:
		 <div class="tx-vjchat-userlist-item tx-vjchat-userlist-[moderator|user|expert|superuse]">
		 <span id="userid-[id]">[username]</span> <span class="tx-vjchat-pm-link">[PM]</span> <span class="tx-vjchat-pr-link">[PR]</span>
		 </div>

		 */

		var userObj =  '<span class="tx-vjchat-username">'
			+ parts[4] +'</span>' ;
		if(this.userId != id) {
			if(this.allowPrivateMessages ) {
				userObj += ' <span class="tx-vjchat-pm-link">[PM]</span> ' ;
			}
			if( this.allowPrivateRooms) {
				userObj += ' <span class="tx-vjchat-pr-link">[PR]</span> ' ;
			}
		}



		jQuery('<div/>', {
			class: "tx-vjchat-userlist-item tx-vjchat-userlist-" +type ,
			html: userObj,
			id: 'userid-' + id

		}).appendTo( '#tx-vjchat-userlist' );


		$('#userid-'+ id +" .tx-vjchat-username").bind("click", function(evt) {
			self.setValueToInput(username, true);
		} );
		if(this.allowPrivateMessages ) {
			$('#userid-' + id + " .tx-vjchat-pm-link").bind("click", function(evt) {
				var command = "/msg " + username + " ";
				self.insertCommand(command);
			});
		}

		if( this.allowPrivateRooms ) {
			$('#userid-' + id + " .tx-vjchat-pr-link").bind("click", function(evt) {
				var name = self.talkToNewRoomName.replace(/\%s/, username);
				var command = "/newroom "+name ;
				self.sendMessage(command);
				command = "/recentinvite "+username+" ";
				self.sendMessage(command);
			});
		}

	}

	this.insertCommand = function(command) {
		$('#txvjchatnewMessage').val( command +  $('#txvjchatnewMessage').val()) ;
		$('#txvjchatnewMessage').focus();
	}


	/* ==================================================== = = = = = = */
	/* SECTION V:		TOOLS										*/
	/* --------------------------------------------- - - - - - - */

	this.setMessageStyle = function(number) {

		var element = $("tx-vjchat-style-btn-"+number);

		if(!element)
			return;

		var container = $("tx-vjchat-style");

		for(var i=0; i<container.childNodes.length;i++) {
			container.childNodes[i].style.border = "none";
		}

		element.style.border = "1px solid black";

		this.sendMessageToServer("/setstyle "+number);


	}

	this.toggleEmoticons = function() {
		//this.toggleElement(this.emoticonsElement);
		var status =  Cookie.get("tx-vjchat-emoticons_visible") ;
		if(status == '1' ) {
			this.setEmoticons('0');
		} else {
			this.setEmoticons('1');
		}
	}

	this.setEmoticons = function(on) {
		if(on == '1' ) {
			Cookie.set("tx-vjchat-emoticons_visible", '1' , 100);
			this.emoticonsElement.show() ;
		} else {
			Cookie.set("tx-vjchat-emoticons_visible", '0', 100);
			this.emoticonsElement.hide() ;
		}

		this.setChatButton('tx-vjchat-button-emoticons', on);
	}

	this.toggleStyle = function() {
		this.setStyle(this.stylesElement.css('visibility') == 'hidden');
	}

	this.setStyle = function(on) {
		if(on && this.stylesElement.css('visibility') == 'hidden')
			this.stylesElement.css('visibility' ,'visible' );

		if(!on && this.stylesElement.css('visibility') == 'visible' ) {
			this.stylesElement.css('visibility' , 'hidden' );
		}
		Cookie.set("tx-vjchat-style_visible", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-styles', on);
	}

	this.toggleAllTime = function() {
		this.setAllTime(!this.showTime);
	}

	this.setAllTime = function (on) {
		this.showTime = on;
		if(!on) {
			$( ".tx-vjchat-time").hide() ;
			Cookie.set('tx_vjchat_showtime', '0', 100);
		} else {
			$( ".tx-vjchat-time").show() ;
			Cookie.set('tx_vjchat_showtime', '1' , 100);
		}
		this.setChatButton('tx-vjchat-button-clock', on);
	}

	this.toggleAutoFocus = function() {
		this.setAutoFocus(!this.autoFocus);
	}

	this.setAutoFocus = function(on) {

		this.autoFocus = on;
		Cookie.set("tx_vjchat_autofocus", on ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-autofocus', on);

	}

	this.setChatButton = function(name, on) {
		if(on)
			this.setChatButtonOn(name);
		else
			this.setChatButtonOff(name);
	}

	this.setChatButtonOn = function(name) {

		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			// alert('on: '+this.chatbuttonskeys[i]+' : '+name+ ' : '+this.chatbuttonson[i]);
			if(this.chatbuttonskeys[i] == name) {
				var containerName = "#" + name+'-container';
				if($(containerName)) {
					$(containerName).html( this.chatbuttonson[i] );
				}
				break;
			}
		}
	}

	this.setChatButtonOff = function(name) {
		for(var i = 0;i<this.chatbuttonskeys.length;i++) {
			// alert('off: '+this.chatbuttonskeys[i]+' : '+name+ ' : '+this.chatbuttonsoff[i]);
			if(this.chatbuttonskeys[i] == name) {
				var containerName = "#" + name+'-container';
				if($(containerName)) {
					$(containerName).html( this.chatbuttonsoff[i] ) ;
				}
				break;
			}
		}
	}

	this.doCheckFull = function() {
		jQuery.ajax({
			type:       "get",
			url:        this.scriptUrl ,
			cache:      false,
			data:       'r='+this.roomId+'&a=checkfull&showJason=0',
			beforeSend:	function() {
			},
			success:    function(result) {
				checkFullResponse(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});
	}

	this.checkFull = function(newTry) {

		if(this.checkFullTimeLeft <= 0) {

			if(this.checkFullStatusElement)
				this.checkFullStatusElement.html ( "Checking..." );

			this.doCheckFull();

		}
		else {

			if(!this.checkFullTimeLeft)
				this.checkFullTimeLeft = this.checkFullTime;

			this.checkFullTimeLeft = this.checkFullTimeLeft - 1000;

			if(this.checkFullStatusElement)
				this.checkFullStatusElement.html( Math.round(this.checkFullTimeLeft / 1000) + " s" );

			this.runningTO4 = window.setTimeout("tx_vjchat_pi1_js_chat_instance.checkFull(false)", 1000 );
		}
	}

	var checkFullResponse = function(t) {
		if( $.trim(t.responseText) == "notfull") {
			if(self.checkFullStatusElement)
				self.checkFullStatusElement.html( "Free - reloading..." );
			window.location.reload();
		}
		else {
			if(self.checkFullStatusElement) {
				self.checkFullStatusElement.html( "Still full" );
			}
			self.checkFullTimeLeft = tx_vjchat_pi1_js_chat_instance.checkFullTime;
			this.runningTO5 = window.setTimeout("tx_vjchat_pi1_js_chat_instance.checkFull(true)", 1000 );
		}
	}


	// ######################   Nemetschek Addings #####################

	// j.v. Added for start / stop reloading
	this.start = function() {
		this.setStart(!this.reload);
	}
	this.setStart = function(on) {
		//
		this.reload = on ;

		Cookie.set('tx_vjchat_reload', this.reload ? '1' : '0', 100);
		this.setChatButton('tx-vjchat-button-start', on);

		if ( this.reload  ) {
			if ( this.runningto) {
				clearTimeout(this.runningto) ;
			}
			if ( this.runningto) {
				clearTimeout(this.runningTO2) ;
			}
		} else {
			this.runningto = window.setTimeout("tx_vjchat_pi1_js_chat_instance.getMessages()", this.refreshMessagesTime);
		}

	}

	// j.v. 2014 added for image Upload from Album

	this.uploadImg = function() {

		if( jQuery("#tx-vjchat-button-imageFormWrap").length > 0) {
			jQuery("#tx-vjchat-button-imageFormWrap").remove();
			tx_vjchat_resize() ;
		} else {
			jQuery.ajax({
				type:       "GET",
				url:        "/admin/community/gallery.html",
				cache:      true,
				data:       'tx_community[action]=list&tx_community[controller]=Album&tx_community[showJason]=1',
				beforeSend:	function() {
					if ( jQuery("#tx-vjchat-button-imageForm").parent()) {
						jQuery("#tx-vjchat-button-imageForm").parent().remove();
					}
					jQuery("#tx-vjchat-button-image").after("<img id=\"tx-vjchat-button-imageLoad\" src=\"/fileadmin/templates_2015/img/loading.gif\" alt=\"loading...\" style='height:27px; width:27px;'></div>");
					jQuery("#tx-vjchat-button-image").addClass('hide');
					jQuery("DIV.tx-community-pi1").remove();
					tx_vjchat_resize() ;
				},
				success:    function(result) {
					jQuery("#tx-vjchat-button-image").removeClass('hide');
					if (typeof result === 'object' ) {
						if ( result.count == 0 ) {
							jQuery("#tx-vjchat-button-imageLoad").removeClass('hide');
						} else {
							if ( result.count == 1 ) {
								var albums = result.album ;
								var album = albums[1] ;
								jQuery("#tx-vjchat-button-imageLoad").addClass('hide');
								chat_instance.uploadImgGetalbum(album.id) ;
							} else {
								var Dropdown = '' ;
								var showSelect = false ;
								jQuery.each( result.album , function(idx, obj) {
									Dropdown += "<option value='" + obj.id +  "'>" + obj.name + " (" + obj.count + ")</option>" ;
									showSelect = true ;
								});
								if ( showSelect === true ) {
									jQuery("#tx-vjchat-button-image").after("<div id=\"tx-vjchat-button-imageFormWrap\" ><select id=\"tx-vjchat-button-imageForm\" onchange=\"chat_instance.uploadImgGetalbum(this.value);\">\n<option>" + result.selectText + "</option>\n" + Dropdown  + "</select></div>\n") ;
								}
								jQuery("#tx-vjchat-button-imageLoad").addClass('hide');
							}
						}


					} else {
						jQuery("#tx-vjchat-button-imageLoad").addClass('hide');
					}
					tx_vjchat_resize() ;

				}
			});
		}
	}


	this.uploadImgGetalbum = function(album) {
		jQuery.ajax({
			type:       "GET",
			url:        "/admin/community/gallery.html",
			cache:      false,
			data:       'tx_community[action]=show&tx_community[controller]=Album&tx_community[album]=' + album+'&tx_community[showHtml]=1',
			beforeSend:	function() {
				jQuery("#tx-vjchat-button-imageLoad").removeClass('hide');
				jQuery("#tx-vjchat-button-image").addClass('hide');
				tx_vjchat_resize() ;

			},
			success:    function(result) {
				this.resizeChatWindow ;
				jQuery("#tx-vjchat-button-imageForm").parent().remove();
				jQuery("#tx-vjchat-button-image").removeClass('hide').after(result) ;
				jQuery("#tx-vjchat-button-imageLoad").remove();
				tx_vjchat_resize() ;
			}
		});

	}
	this.addImage = function(bigImg, smallImg) {

		smallImg	= smallImg.replace(location.protocol+'//'+location.hostname, '');
		bigImg		= bigImg.replace(location.protocol+'//'+location.hostname, '');
		this.addSelText( "[img=/" + bigImg+ "]" + smallImg + "[/img]" , '');

	}

	this.showChatImg = function(bigImg) {
			jQuery.ajax({
				type:       "GET",
				url:        bigImg ,
				cache:      true,
				data:       '',
				beforeSend:	function() {
					jQuery("#wrap").before("<div id=\"tx-vjchat-bigimageLayer\" onclick=\"chat_instance.hideChatImg();\" style=\"position:absolute; display:block;height:100%; width:100%; background: rgba(183,183,183,0.7); z-index: 900;\"></div>")

					jQuery("#tx-vjchat-bigimageLayer").css("position", "absolute");


				},
				success:    function(result) {
					jQuery("#tx-vjchat-bigimageLayer").html("<div id=\"tx-vjchat-bigimageWrap\"><img  id=\"tx-vjchat-bigimage\" alt=\"click me\" onclick=\"chat_instance.hideChatImg();\" title=\"click me\" src=\"" + bigImg + "\" /></div>")


					jQuery("#tx-vjchat-bigimageWrap").css("z-index", "999");
					jQuery("#tx-vjchat-bigimageWrap").css("position", "absolute");

					jQuery("#tx-vjchat-bigimage").css("display", "block");
					var marginTop = parseInt((jQuery(window).height() - parseInt( jQuery("#tx-vjchat-bigimageWrap").css("height")) ) / 2 ) ;
					var marginLeft= parseInt((jQuery(window).width() - parseInt(jQuery("#tx-vjchat-bigimageWrap").css( "width") )) / 2 ) ;
					if( marginTop < 0 ) {  marginTop = 0 }
					if( marginLeft < 0 ) {  marginLeft = 0 }

					//jQuery("#tx-vjchat-bigimageWrap").css("top", marginTop +"px");
					//jQuery("#tx-vjchat-bigimageWrap").css("left", marginLeft +"px");
				}
			});
	}
	this.hideChatImg = function() {
		jQuery("#tx-vjchat-bigimageLayer").remove();

	}

	// http://aktuell.de.selfhtml.org/tippstricks/javascript/bbcode/
	this.addSelText = function( aTag, eTag) {
		var CPos = $('#txvjchatnewMessage').caret() ;
		var t = $('#txvjchatnewMessage').range().text ;
		CPos = CPos + aTag.length ;
		$('#txvjchatnewMessage').range(aTag + t + eTag ) ;
		$('#txvjchatnewMessage').focus() ;
		$('#txvjchatnewMessage').caret(parseInt( CPos) ) ;
	}


	this.insertAtCursor = function(myField, myValue) {
		var CPos = $('#txvjchatnewMessage').caret() ;
		$('#txvjchatnewMessage').caret(myValue) ;
		$('#txvjchatnewMessage').focus() ;
	}

}


// http://gorondowtl.sourceforge.net/wiki/Cookie
var Cookie = {
	set: function(name, value, daysToExpire) {
		var expire = '';
		if (daysToExpire != undefined) {
			var d = new Date();
			d.setTime(d.getTime() + (86400000 * parseFloat(daysToExpire)));
			expire = '; expires=' + d.toGMTString();
		}
		return (document.cookie = encodeURI(name) + '=' + encodeURI(value || '') + expire);
	},
	get: function(name) {
		var cookie = document.cookie.match(new RegExp('(^|;)\\s*' + encodeURI(name) + '=([^;\\s]*)'));
		return (cookie ? decodeURI(cookie[2]) : null);
	},
	erase: function(name) {
		var cookie = Cookie.get(name) || true;
		Cookie.set(name, '', -1);
		return cookie;
	},
	accept: function() {
		if (typeof navigator.cookieEnabled == 'boolean') {
			return navigator.cookieEnabled;
		}
		Cookie.set('_test', '1');
		return (Cookie.erase('_test') === '1');
	}
};

function openChatWindow(id) {
	chat_instance.openChatWindow(id);
}

function setValueToInput(value) {
	chat_instance.setValueToInput(value);
}


function tx_vjchat_resize() {
	var totalH= $(window).height() ;
	var otherH = 60 ;
	if ( $(window).width() < 767 ) {
		otherH = $('#tx-vjchat-userlist').height() + 60 ;
	} else {
		$('#tx-vjchat-userlist').addClass("in").attr("aria-expanded" , "true").css('height' , 'auto') ;
	}
	if( totalH < 400 ) {
		$('#txvjchatnewMessage').css("height" , "46px") ;
	} else {
		$('#txvjchatnewMessage').css("height" , "92px") ;
	}
	var infoH = $('.tx-vjchat-chat-intro').height() ;
	var toolsH = $('#tx-vjchat-tools-container').height() ;
	var usersH = $('#tx-vjchat-showUserlist').height() ;
	var inputH = $('#tx-vjchat-input-container').height() ;
	var messH = totalH - infoH - toolsH - inputH - otherH - usersH ;
	// debug needed ??
	// $('#txvjchatnewMessage').val( "TotalH : " + totalH + " | infoH : " + infoH + " | toolsH: " + toolsH + "  inputH: " + inputH + " | Result: " + messH ) ;
	$('#tx-vjchat-messages').css("height" , messH + "px") ;
}


Array.prototype.inArray = function (value)
// Returns true if the passed value is found in the
// array.  Returns false if it is not.
{
	var i;
	for (i=0; i < this.length; i++) {
		// Matches identical (===), not just similar (==).
		if (this[i] === value) {
			return true;
		}
	}
	return false;
};
