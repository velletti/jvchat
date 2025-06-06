
var tx_jvchat_pi1_js_chat_instance = null;

function tx_jvchat_pi1_js_chat() {

	/* ==================================================== = = = = = = */
	/* SECTION I:		CONFIGURATION  								*/
	/* --------------------------------------------- - - - - - - */



	this.refreshMessagesTime 	= 5000;
	this.refreshUserlistTime 	= 10000;

	tx_jvchat_pi1_js_chat_instance 	= "chat_instance";
	this.maxActiveRequests		= 3;
	this.popup					= true;
	this.debug                  = true;
	this.userlistPMInfo 		= "Send a private message to \'%s\'";
	this.userlistPRInfo 		= "Open a new room and invite \'%s\'";

	this.allowPrivateMessages	= false;
	this.allowPrivateRooms		= false;
	this.talkToNewRoomName		= 'New Room with %s';
	this.checkFullTime			= 20000;
	this.checkFullStatusElement = $('#tx-jvchat-full-jsstatus');
	this.autoFocus				= false;
	this.chatWindow				= window;

	var globalInstanceName = tx_jvchat_pi1_js_chat_instance;

	this.messageStack = new Array(); // collection of messages that will be send to server
	this.receivedMessages = new Array(); // collection of ids
	this.oldMessage = ""; 	// saves message for avoiding duplicates entries

	var userList = new Array();

	var self = null;
	tx_jvchat_pi1_js_chat_instance = this;

	/* ==================================================== = = = = = = */
	/* SECTION II:		MAIN/COMMON									*/
	/* --------------------------------------------- - - - - - - */


	this.init = function() {
		// apply configuration

		initConfig = $('#tx-jvchat-config') ;
		this.roomId					= initConfig.data('roomid');
		this.pid					= initConfig.data('pid');
		this.userId					= initConfig.data('userid');
		this.usernameSelf			= initConfig.data('username');
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
		this.popupJSWindowParams 	= initConfig.data('popupparams');
		this.talkToNewRoomName 		= initConfig.data('talktonewroomname');

		this.refreshMessagesTime 	= initConfig.data('refreshmessagestime');
		this.refreshUserlistTime 	= initConfig.data('refreshuserlisttime');
		this.allowPrivateMessages	= initConfig.data('allowprivatemessages');
		this.allowPrivateRooms		= initConfig.data('allowprivaterooms');
		this.privateMsgCode			= initConfig.data('privatemsgcode');
		this.privateRoomCode		= initConfig.data('privateroomcode');

		this.inputElement 			= $('#txjvchatnewMessage');
		this.messagesElement 		= $('#tx-jvchat-messages');
		this.userListElement		= $('#tx-jvchat-userlist');
		this.emoticonsElement		= $('#tx-jvchat-emoticons');
		this.toolsElement			= $('#tx-jvchat-tools-container');
		this.storedMessagesElement  = $('#tx-jvchat-storedMessages');
		this.reload                 = Cookie.get('tx_jvchat_reload');



		globalInstanceName = tx_jvchat_pi1_js_chat_instance;

		if(Cookie.get('tx-jvchat-emoticons_visible') != null) {
			var show = (Cookie.get('tx-jvchat-emoticons_visible') == '1');
			this.setEmoticons(show);
		}
		else
			//  this.showEmoticons from flexform ? better start with 0
			this.setEmoticons(0);

		if ( this.showTime ) {
			if(Cookie.get('tx_jvchat_showtime') != null) {
				var show = Cookie.get('tx_jvchat_showtime') == '1';
				this.setAllTime(show);
			}
			else
				this.setAllTime(this.showTime );
		}


		self = this;
		chat_instance = this;

	}
	/**
	 * Run chat
	 */
	this.run = function() {

		// set current id to initial id
		this.id = this.initialId;
		// console.log( "user: " +  this.userId + " Id: " +  this.initialId + " xharset =" + this.charset 	+ " test " +  $('#tx-jvchat-config').data('messagesGlue') ) ;


		$('#txjvchatnewMessage').keypress(function( event ) {
			if ( event.which == 13 || event.which == 10 ) {
				if( event.shiftKey ) {
					self.insertAtCursor(self.inputElement, "\r\n") ;
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
		this.handleImageUpload();
		this.setStart(this.reload);


	}

	this.handleImageUpload = function(){
		if (jQuery('#chat-uploaded-file')) {
			jQuery('#chat-uploaded-file').fileupload({
				url: jQuery('#tx-jvchat-config').data('scripturl') + "&a=pi&r=" + this.roomId ,
				dataType: 'json',

				start: function() {
					showSpinner() ;
					jQuery('#tx-jvchat-upload-container').removeClass('in');
					jQuery('#tx-jvchat-button-imageDirect').attr('area-expanded', false);
				},
				always: function(e) {
					// wait at least 3 seconds to hide the spinner. not perfect, but we need to remove spinner in case of errors
					setTimeout(hideSpinner, 3000)
				},

			});
		}
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
		// $("txjvchatnewMessage").focus();
	}

	this.newWindow = function() {
		tx_jvchat_openNewChatWindow(this.newWindowUrl, this.roomId);
	}



	this.openChatWindow = function(chatId) {
		tx_jvchat_openNewChatWindow(this.newWindowUrl, chatId);
	}


	/* ==================================================== = = = = = = */
	/* SECTION III:		MESSAGES	  								*/
	/* --------------------------------------------- - - - - - - */

	this.getMessages = function(noSetTimeout) {
		jQuery.ajax({
			type:       "GET",
			url:        this.scriptUrl ,
			cache:      false,
			data:       'r=' + this.roomId + '&p=' + this.pid +'&a=gm&t=' + this.id + '&charset=' + this.charset + '&l=' + this.lang+ "&u=" + this.userId	+ "&showJason=0",
			beforeSend:	function() {
			},
			success:    function(result) {
				getMessagesResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});



		if(!noSetTimeout) {
			this.runningto = window.setTimeout("tx_jvchat_pi1_js_chat_instance.getMessages()", this.refreshMessagesTime);
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
					window.setTimeout("tx_jvchat_pi1_js_chat_instance.quit()", 1500);
					break;
				case "stop":
					// alert("command = stop");
					if( this.runningto ) {
						clearTimeout(this.runningto) ;
					}
					if( this.runningTO2 ) {
						clearTimeout(this.runningTO2);
					}
					break;
				case "rest":
					// alert("Command = restart");
					this.runningto = window.setTimeout("tx_jvchat_pi1_js_chat_instance.getMessages(false)", this.refreshMessagesTime);

					break;
				default:
					this.createNewMessageNode(text);
					break;
			}
		}

	}

	this.quit = function() {
		if( this.runningto ) {
			clearTimeout(this.runningto);
		}
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

			if ( messageArray[ii].substr(0,4) == "s://" || messageArray[ii].substr(0,4) == "S://" ) {
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

		var systemsearch = message.match(/<div class=\"(.*?tx-jvchat-system.*?)\"/i);
		var useridsearch = message.match(/tx-jvchat-user tx-jvchat-userid-([0-9]*?)\"\>/i);
		var usernamesearch = message.match(/tx-jvchat-user tx-jvchat-userid-([0-9]*?)\"\>([A-Z]*?)<\/span>/i);


		// notify if not a system message and message from another user
		if(!(systemsearch && systemsearch[1]) && (useridsearch && useridsearch[1] && useridsearch[1] != this.userId))
			this.notifyNewMessage();

		var className = document.createAttribute("class");
		className.nodeValue = "tx-jvchat-entry";
		newMessageNode.setAttributeNode(className);

		$('#tx-jvchat-messages').append(newMessageNode);
		// scroll down
		if($('#tx-jvchat-messages').length)
			$('#tx-jvchat-messages').scrollTop($('#tx-jvchat-messages')[0].scrollHeight - $('#tx-jvchat-messages').height());

		if( this.showTime ) {
			this.setAllTime(this.showTime) ;
		}

	}

	/**
	 * Submits an entered string by call sendMessageToServer()
	 */
	this.submitMessage = function() {

		// get entered message
		var newMessage = $('#txjvchatnewMessage').val() ;

		// clear input field
		$('#txjvchatnewMessage').val('') ;

		if($.trim(newMessage) == "undefined" || $.trim(newMessage) == "") {
			return;
		}

		// send message to server
		self.sendMessageToServer($.trim(newMessage));
		// switch back to Play mode reload was disabled !
		if ( !this.reload  ) {
			this.setStart( 1 );
		}
		this.getMessages(true) ;
		// this.runningTO2 = window.setTimeout("tx_jvchat_pi1_js_chat_instance.getMessages(true)", 500);

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

				data:       "r="+this.roomId+ '&p=' + this.pid+"&a=sm&t="+this.id+"&l="+this.lang+"&m="+ message +"&charset="+this.charset,
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
			this.runningTO3 = window.setTimeout("tx_jvchat_pi1_js_chat_instance.sendMessageToServer()", 500);
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
			data:       "r="+this.roomId+ '&p=' + this.pid +"&t="+this.id+"&a=commit&uid="+uid+ "&showJason=0",

			beforeSend:	function() {
			},
			success:    function(result) {
				getMessagesResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});

		var messageNode = $("#tx-jvchat-entry-"+uid);
		messageNode.getAttributeNode("class").nodeValue = "tx-jvchat-committed";

		this.runningTO4 = window.setTimeout("tx_jvchat_pi1_js_chat_instance.hideEntry(" + uid + ") ", 1000);

	}

	this.hideEntry = function(cidid , uid) {
		if( confirm( "Delete really | Wirklich löschen?") ) {
			jQuery.ajax({
				type:       "get",
				url:        this.scriptUrl ,
				cache:      false,
				data:       "a=del&uid="+uid+  "&showJason=0",

				beforeSend:	function() {
				},
				success:    function(result) {
					getMessagesResponseHandler(result) ;
				} ,
				onFailure: handleAjaxError,
				on404: handleAjax404

			});
			if( $(cidid).length ) {
				$(cidid).parent().remove() ;
			}


			if(this.storedMessagesElement.length) {
				if(this.storedMessagesElement.childNodes) {
					if(this.storedMessagesElement.childNodes.length == 0) {
						this.toogleStoredMessages(false);
					}
				}
			}
		}
	}

	this.storeEntry = function(uid) {

		this.toogleStoredMessages(true);

		var node = $("tx-jvchat-entry-"+uid).parentNode;
		this.storedMessagesElement.style.display = "block";
		this.storedMessagesElement.append(node);

		// remove link storemessage
		node.childNodes[1].remove($("tx-jvchat-storelink-"+uid));

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
		let milliseconds = (new Date()).getTime();
		jQuery.ajax({
			type:       "get",
			url:        this.scriptUrl ,
			cache:      true,
			data:       'r='+this.roomId+ '&p=' + this.pid+'&a=gu&charset='+this.charset+ "&showJason=0&rnd=milliseconds",
			beforeSend:	function() {
			},
			success:    function(result) {
				getUserlistResponseHandler(result) ;
			} ,
			onFailure: handleAjaxError,
			on404: handleAjax404

		});

		this.runningTOul = window.setTimeout("tx_jvchat_pi1_js_chat_instance.getUserlist()", this.refreshUserlistTime);
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
		$('#tx-jvchat-userlist').html('') ;

		var userCount = 0 ;
		// go through all Users and add them to the userlist window by calling createNewUserNode()
		for (i = 0; i<x.childNodes.length; i++) {
			if(!x.childNodes[i])
				continue;
			if(!x.childNodes[i].firstChild)
				continue;
			if(!x.childNodes[i].firstChild.data)
				continue;
			this.createNewUserNode( $.trim(x.childNodes[i].firstChild.data));
			userCount++ ;
		}
		if( $('#tx-jvchat-usercount'))  {
			$('#tx-jvchat-usercount').html(userCount) ;
		}

		this.notifyUserListChange();

	}

	this.clearUserList = function() {
		$('#tx-jvchat-userlist').html('') ;
	}

	this.createNewUserNode = function(value) {

		if(value == "")		// skip empty values
			return;

		var parts = value.split(this.usernameGlue);

		var userObj = parts[0];
		var type = parts[1];
		var id = parts[2];
		var username = parts[3];

		userList["userid-"+id] = value;

		jQuery('<div/>', {
			class: "tx-jvchat-userlist-item tx-jvchat-userlist-" +type ,
			html: userObj,
			id: 'userid-' + id

		}).appendTo( '#tx-jvchat-userlist' );

		if( parseInt( this.userId ) == parseInt(id )) {
			$('#userid-'+ id +" .tx-jvchat-userlist-buttons").hide() ;
		} else {
			$('#userid-'+ id +" .tx-jvchat-userlist-username").bind("click", function(evt) {
				self.setValueToInput(username, true);
			} );
			if(this.allowPrivateMessages ) {
				$('#userid-' + id + " .tx-jvchat-pm-link").bind("click", function(evt) {
					var command = "/msg #" + id + " ";
					self.insertCommand(command);
				});
			}

			if( this.allowPrivateRooms ) {
				$('#userid-' + id + " .tx-jvchat-pr-link").bind("click", function(evt) {
					var name = self.talkToNewRoomName.replace(/\%s/, username) ;
					var command = "/talkTo #"+id+" "+name ;
					self.sendMessage(command);
				});
			}
		}


	}

	this.insertCommand = function(command) {
		$('#txjvchatnewMessage').val( command +  $('#txjvchatnewMessage').val()) ;
		$('#txjvchatnewMessage').focus();
	}


	/* ==================================================== = = = = = = */
	/* SECTION V:		TOOLS										*/
	/* --------------------------------------------- - - - - - - */


	this.toggleEmoticons = function() {
		//this.toggleElement(this.emoticonsElement);
		var status =  Cookie.get("tx-jvchat-emoticons_visible") ;
		if(status == '1' ) {
			this.setEmoticons('0');
		} else {
			this.setEmoticons('1');
		}
	}

	this.setEmoticons = function(on) {
		if(on == '1' ) {
			Cookie.set("tx-jvchat-emoticons_visible", '1' , 100);
			this.emoticonsElement.show() ;
		} else {
			Cookie.set("tx-jvchat-emoticons_visible", '0', 100);
			this.emoticonsElement.hide() ;
		}

		// this.setChatButton('tx-jvchat-button-emoticons', on);
	}


	this.toggleAllTime = function() {
		this.setAllTime(!this.showTime);
	}

	this.setAllTime = function (on) {
		this.showTime = on;
		if(!on) {
			$( ".tx-jvchat-time").hide() ;
			Cookie.set('tx_jvchat_showtime', '0', 100);
		} else {
			$( ".tx-jvchat-time").show() ;
			Cookie.set('tx_jvchat_showtime', '1' , 100);
		}
		// this.setChatButton('tx-jvchat-button-clock', on);
	}




	this.doCheckFull = function() {
		jQuery.ajax({
			type:       "get",
			url:        this.scriptUrl ,
			cache:      false,
			data:       'r='+this.roomId + '&p=' + this.pid+'&a=checkfull&showJason=0',
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

			this.runningTO4 = window.setTimeout("tx_jvchat_pi1_js_chat_instance.checkFull(false)", 1000 );
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
			self.checkFullTimeLeft = tx_jvchat_pi1_js_chat_instance.checkFullTime;
			this.runningTO5 = window.setTimeout("tx_jvchat_pi1_js_chat_instance.checkFull(true)", 1000 );
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
		Cookie.set('tx_jvchat_reload', this.reload ? '1' : '0', 100);
		// this.setChatButton('tx-jvchat-button-start', on);

		if ( !this.reload  ) {
			jQuery("#tx-jvchat-button-start").parent().addClass('hide').addClass('d-none');
			jQuery("#tx-jvchat-button-start-on").parent().removeClass('hide').removeClass('d-none');

			if ( this.runningto) {
				clearTimeout(this.runningto) ;
				this.runningto = false ;
			}
			if ( this.runningTO2) {
				clearTimeout(this.runningTO2) ;
			}
		} else {
			if ( !this.runningto) {
				this.runningto = window.setTimeout("tx_jvchat_pi1_js_chat_instance.getMessages()", this.refreshMessagesTime);
			}
			jQuery("#tx-jvchat-button-start-on").parent().addClass('hide').addClass('d-none');
			jQuery("#tx-jvchat-button-start").parent().removeClass('hide').removeClass('d-none');
		}

	}

	// j.v. 2014 added for image Upload from Album

	this.uploadImg = function() {

		if( jQuery("#tx-jvchat-button-imageFormWrap").length > 0) {
			jQuery("#tx-jvchat-button-imageFormWrap").remove();
			jQuery("#tx-jvchat-button-image").parent().removeClass('tx-jvchat-button-image-action');
			tx_jvchat_resize() ;
		} else {
			jQuery.ajax({
				type:       "GET",
				url:        "/admin/community/gallery.html",
				cache:      true,
				data:       'tx_community[action]=list&tx_community[controller]=Album&tx_community[showJason]=1',
				beforeSend:	function() {
					if ( jQuery("#tx-jvchat-button-imageForm").parent()) {
						jQuery("#tx-jvchat-button-imageForm").parent().remove();
					}
					jQuery("#tx-jvchat-button-image").after("<span  id='tx-jvchat-button-imageLoad' class='fa fa-spinner fa-spin' />");
					jQuery("#tx-jvchat-button-image").addClass('hide');
					jQuery("DIV.tx-community-pi1").remove();
					tx_jvchat_resize() ;
				},
				success:    function(result) {
					jQuery("#tx-jvchat-button-image").removeClass('hide');
					jQuery("#tx-jvchat-button-image").parent().addClass('tx-jvchat-button-image-action');
					if (typeof result === 'object' ) {
						if ( result.count == 0 ) {
							jQuery("#tx-jvchat-button-imageLoad").addClass('hide');
							jQuery("#tx-jvchat-button-image").parent().remove() ;
						} else {
							if ( result.count == 1 ) {
								var albums = result.album ;
								var album = albums[1] ;
								jQuery("#tx-jvchat-button-imageLoad").addClass('hide');
								chat_instance.uploadImgGetalbum(album.id) ;
							} else {
								var Dropdown = '' ;
								var showSelect = false ;
								jQuery.each( result.album , function(idx, obj) {
									Dropdown += "<option value='" + obj.id +  "'>" + obj.name + " (" + obj.count + ")</option>" ;
									showSelect = true ;
								});
								if ( showSelect === true ) {
									jQuery("#tx-jvchat-button-image").after("<div id=\"tx-jvchat-button-imageFormWrap\" ><select id=\"tx-jvchat-button-imageForm\" onchange=\"chat_instance.uploadImgGetalbum(this.value);\">\n<option>" + result.selectText + "</option>\n" + Dropdown  + "</select></div>\n") ;
								}
								jQuery("#tx-jvchat-button-imageLoad").addClass('hide');
							}
						}


					} else {
						jQuery("#tx-jvchat-button-imageLoad").addClass('hide');
					}
					tx_jvchat_resize() ;

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
				jQuery("#tx-jvchat-button-imageLoad").removeClass('hide');
				jQuery("#tx-jvchat-button-image").addClass('hide');
				tx_jvchat_resize() ;

			},
			success:    function(result) {
				this.resizeChatWindow ;
				jQuery("#tx-jvchat-button-imageForm").parent().remove();
				jQuery("#tx-jvchat-button-image").removeClass('hide').after(result) ;
				jQuery("#tx-jvchat-button-imageLoad").remove();

				tx_jvchat_resize() ;
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
				jQuery("#mainContent").before("<div id=\"tx-jvchat-bigimageLayer\" onclick=\"chat_instance.hideChatImg();\" style=\"position:absolute; display:block;height:100%; width:100%; background: rgba(183,183,183,0.7); z-index: 900;overflow: auto\"></div>")

				jQuery("#tx-jvchat-bigimageLayer").css("position", "absolute");


			},
			success:    function(result) {
				jQuery("#tx-jvchat-bigimageLayer").html("<div id=\"tx-jvchat-bigimageWrap\"><img  id=\"tx-jvchat-bigimage\" alt=\"click me\" onclick=\"chat_instance.hideChatImg();\" title=\"click me\" src=\"" + bigImg + "\" /></div>")


				jQuery("#tx-jvchat-bigimageWrap").css("z-index", "999");
				jQuery("#tx-jvchat-bigimageWrap").css("position", "absolute");

				jQuery("#tx-jvchat-bigimage").css("display", "block");
				var marginTop = parseInt((jQuery(window).height() - parseInt( jQuery("#tx-jvchat-bigimageWrap").css("height")) ) / 2 ) ;
				var marginLeft= parseInt((jQuery(window).width() - parseInt(jQuery("#tx-jvchat-bigimageWrap").css( "width") )) / 2 ) ;
				if( marginTop < 0 ) {  marginTop = 0 }
				if( marginLeft < 0 ) {  marginLeft = 0 }

				//jQuery("#tx-jvchat-bigimageWrap").css("top", marginTop +"px");
				//jQuery("#tx-jvchat-bigimageWrap").css("left", marginLeft +"px");
			}
		});
	}
	this.hideChatImg = function() {
		jQuery("#tx-jvchat-bigimageLayer").remove();

	}

	// http://aktuell.de.selfhtml.org/tippstricks/javascript/bbcode/
	this.addSelText = function( aTag, eTag) {
		var CPos = $('#txjvchatnewMessage').caret() ;
		var t = $('#txjvchatnewMessage').range().text ;
		CPos = CPos + aTag.length ;
		$('#txjvchatnewMessage').range(aTag + t + eTag ) ;
		$('#txjvchatnewMessage').focus() ;
		$('#txjvchatnewMessage').caret(parseInt( CPos) ) ;
	}


	this.insertAtCursor = function(myField, myValue) {
		var CPos = $('#txjvchatnewMessage').caret() ;
		$('#txjvchatnewMessage').caret(myValue) ;
		$('#txjvchatnewMessage').focus() ;
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


function tx_jvchat_resize() {
	var totalH= parseInt( $(window).height()) ;
	var otherH = 60 ;
	// if ( $(window).width() < 767 ) {
	// otherH = $('#tx-jvchat-userlist').height() + 60 ;
	// } else {
	// 	$('#tx-jvchat-userlist').addClass("in").attr("aria-expanded" , "true").css('height' , 'auto') ;
	// }
	var inputH = parseInt($('#tx-jvchat-input-container').height()) ;
	if( totalH < 700 ) {
		$('#txjvchatnewMessage').css("height" , "46px") ;
		if ( isNaN(inputH ) ) { inputH = 46 ;}
	} else {
		$('#txjvchatnewMessage').css("height" , "96px") ;
		if ( isNaN(inputH ) ) { inputH = 96 ;}
	}
	var mainnavH = parseInt( $('#mainnavIcons').height()) ;
	if ( isNaN(mainnavH ) )
	{ mainnavH = 0 ;}
	else
	{
		mainnavH = mainnavH + 10 ;
		$(".frame-type-felogin_login").hide();
	}

	var subnavH = parseInt( $('#jvEventsAjaxMenu').height()) ;
	if ( isNaN(subnavH ) ) { subnavH = 0 ;}

	var footerH = parseInt( $('.fixed-bottom').height()) ;
	if ( isNaN(footerH ) ) { footerH = 0 ;} else { footerH = footerH + 8 ;}

	var infoH = parseInt( $('.tx-jvchat-chat-intro').height()) ;
	if ( isNaN(infoH ) ) { infoH = 0 ;}


	var toolsH = parseInt($('#tx-jvchat-tools-container').height()) ;
	if ( isNaN(toolsH ) ) { toolsH = 0 ;}

	var usersH = parseInt($('#tx-jvchat-userlist').height()) ;
	if ( isNaN(usersH ) ) { usersH = 0 ;}

	var messH = parseInt( totalH - mainnavH - subnavH - footerH - otherH - infoH - toolsH - inputH  - usersH - 20 );

	if ( messH < 300  || isNaN(messH ) ) {
		messH = 300 ;
	}
	// debug needed ??
	//	$('#txjvchatnewMessage').val( "TotalH : " + totalH + " | mainnavH: " + mainnavH + " | subnavH: " + subnavH + " | footerH: " + footerH + " | otherH : " + otherH + "  | infoH : " + infoH + " | usersH: " + usersH + " | toolsH: " + toolsH + " | inputH: " + inputH + " | Result: " + messH ) ;
	$('#tx-jvchat-messages').css("height" , messH + "px") ;
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
