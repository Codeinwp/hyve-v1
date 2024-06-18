/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';

import { addQueryArgs } from '@wordpress/url';

const clickAudio = new Audio( hyve.audio.click );
const pingAudio = new Audio( hyve.audio.ping );

class App {
	constructor() {
		this.isInitialToggle = true;
		this.messages = [];
		this.threadID = null;
		this.runID = null;

		this.renderUI();
		this.setupListeners();
	}

	add( message, sender, id = null ) {
		message = this.sanitize( message );

		this.messages.push({ time: new Date(), message, sender, id  });
		this.addMessage( message, sender, id );

		if ( 'user' !== sender ) {
			return;
		}

		this.sendRequest( message );
	}

	sanitize( input ) {
		const tempDiv = document.createElement( 'div' );
		tempDiv.textContent = input;
		return tempDiv.innerHTML;
	}

	setThreadID( threadID ) {
		this.threadID = threadID;
	}

	setRunID( runID ) {
		this.runID = runID;
	}

	setLoading( isLoading ) {
		const chatInputText = document.querySelector( '#hyve-text-input' );
		const chatSendButton = document.querySelector( '.hyve-send-button button' );

		chatInputText.disabled = isLoading;
		chatSendButton.disabled = isLoading;
	}

	async getResponse() {
		try {
			const response = await apiFetch({
				path: addQueryArgs( `${ window.hyve.api }/chat`, {
					'thread_id': this.threadID,
					'run_id': this.runID
				}),
				headers: {
					'Cache-Control': 'no-cache'
				}
			});

			if ( response.error ) {
				this.add( 'Sorry, I am not able to process your request at the moment. Please try again.', 'bot' );
				this.removeMessage( this.runID );
				return;
			}

			if ( 'in_progress' === response.status ) {
				setTimeout( async() => {
					await this.getResponse();
				}, 2000 );

				return;
			}

			this.removeMessage( this.runID );

			if ( 'completed' === response.status ) {
				this.add( response.message, 'bot' );
				this.setLoading( false );
			}

			if ( 'failed' === response.status ) {
				this.add( 'Sorry, I am not able to process your request at the moment. Please try again.', 'bot' );
				this.setLoading( false );
			}
		} catch ( error ) {
			this.add( 'Sorry, I am not able to process your request at the moment. Please try again.', 'bot' );
			this.setLoading( false );
		}
	}

	async sendRequest( message ) {
		try {
			this.setLoading( true );

			const response = await apiFetch({
				path: `${ window.hyve.api }/chat`,
				method: 'POST',
				data: {
					message,
					...( null !== this.threadID ? { 'thread_id': this.threadID } : {})
				}
			});

			if ( response.error ) {
				this.add( 'Sorry, I am not able to process your request at the moment. Please try again.', 'bot' );
				this.setLoading( false );
				return;
			}

			if ( response.thread_id !== this.threadID ) {
				this.setThreadID( response.thread_id );
			}

			this.setRunID( response.query_run );

			this.add( 'Typing...', 'bot', response.query_run );

			await this.getResponse();
		} catch ( error ) {
			this.add( 'Sorry, I am not able to process your request at the moment. Please try again.', 'bot' );
			this.setLoading( false );
		}
	}

	addAudioPlayback( audioElement ) {
		audioElement.play();
	}

	addMessage( message, sender, id, sound = true ) {
		const chatMessageBox = document.getElementById( 'hyve-message-box' );

		const messageDiv = this.createElement( 'div', {
			className: `hyve-${sender}-message`,
			innerHTML: `<p>${message}</p><div class="hyve-arrow"></div>`
		});

		if ( null !== id ) {
			messageDiv.id = `hyve-message-${id}`;
		}

		chatMessageBox.appendChild( messageDiv );
		chatMessageBox.scrollTop = chatMessageBox.scrollHeight;

		if ( ! sound ) {
			return;
		}

		this.addAudioPlayback( pingAudio );
	}

	removeMessage( id ) {
		const message = document.getElementById( `hyve-message-${id}` );
		if ( message ) {
			message.remove();
		}
	}

	toggleChatWindow( isOpen ) {
		const elements = [ 'hyve-open', 'hyve-close', 'hyve-window' ].map( id => document.getElementById( id ) );

		if ( isOpen ) {
			elements[0].style.display = 'none';
			elements[1].style.display = 'block';
			elements[2].style.display = 'block';

			const chatMessageBox = document.getElementById( 'hyve-message-box' );
			chatMessageBox.scrollTop = chatMessageBox.scrollHeight;
		} else {
			elements[0].style.display = 'block';
			elements[1].style.display = 'none';
			elements[2].style.display = 'none';
		}

		this.addAudioPlayback( clickAudio );

		if ( window.hyve.welcome && '' !== window.hyve.welcome && this.isInitialToggle ) {
			this.isInitialToggle = false;
			const welcomeMessage = window.hyve.welcome;

			setTimeout( () => {
				this.add( welcomeMessage, 'bot' );
			}, 1000 );
		}
	}

	setupListeners() {
		const chatOpen = document.getElementById( 'hyve-open' );
		const chatClose = document.getElementById( 'hyve-close' );
		const chatInputText = document.getElementById( 'hyve-text-input' );
		const chatSendButton = document.getElementById( 'hyve-send-button' );

		chatOpen.addEventListener( 'click', () => this.toggleChatWindow( true ) );
		chatClose.addEventListener( 'click', () => this.toggleChatWindow( false ) );

		chatInputText.addEventListener( 'keydown', ( event ) => {
			if ( 13 === event.keyCode ) {
				if ( '' !== chatInputText.value.trim() ) {
					this.add( chatInputText.value, 'user' );
					chatInputText.value = '';
				}
			}
		});

		chatSendButton.addEventListener( 'click', () => {
			if ( '' !== chatInputText.value.trim() ) {
				this.add( chatInputText.value, 'user' );
				chatInputText.value = '';
			}
		});
	}

	createElement( tag, props, ...children ) {
		const element = document.createElement( tag );
		Object.assign( element, props );
		children.forEach( child => {
			if ( 'string' === typeof child ) {
				element.appendChild( document.createTextNode( child ) );
			} else {
				element.appendChild( child );
			}
		});
		return element;
	}

	renderUI() {
		const chatOpenButton = this.createElement( 'button', {
			className: 'collapsible open',
			innerText: '💬'
		});

		const chatOpen = this.createElement( 'div', { className: 'hyve-bar-open', id: 'hyve-open' }, chatOpenButton );

		const chatCloseButton = this.createElement( 'button', {
			className: 'collapsible close',
			innerHTML: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>'
		});

		const chatClose = this.createElement( 'div', { className: 'hyve-bar-close', id: 'hyve-close' }, chatCloseButton );

		const chatWindow = this.createElement( 'div', { className: 'hyve-window', id: 'hyve-window' });
		const chatMessageBox = this.createElement( 'div', { className: 'hyve-message-box', id: 'hyve-message-box' });

		const chatInputBox = this.createElement( 'div', { className: 'hyve-input-box' });
		const chatCreditsAnchor = this.createElement( 'a', {
			href: 'https://themeisle.com/plugins/hyve/',
			target: '_blank'
		}, 'Powered by Hyve' );
		const chatCredits = this.createElement( 'div', { className: 'hyve-credits' });
		const chatWrite = this.createElement( 'div', { className: 'hyve-write' });

		const chatInputText = this.createElement( 'input', {
			className: 'hyve-input-text',
			type: 'text',
			id: 'hyve-text-input',
			placeholder: 'Write a reply...'
		});

		const chatSendButton = this.createElement(
			'div',
			{
				className: 'hyve-send-button',
				id: 'hyve-send-button'
			},
			this.createElement( 'button', { className: 'hyve-send-message', innerHTML: '<svg viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M31.083 16.589c0.105-0.167 0.167-0.371 0.167-0.589s-0.062-0.421-0.17-0.593l0.003 0.005c-0.030-0.051-0.059-0.094-0.091-0.135l0.002 0.003c-0.1-0.137-0.223-0.251-0.366-0.336l-0.006-0.003c-0.025-0.015-0.037-0.045-0.064-0.058l-28-14c-0.163-0.083-0.355-0.132-0.558-0.132-0.691 0-1.25 0.56-1.25 1.25 0 0.178 0.037 0.347 0.104 0.5l-0.003-0.008 5.789 13.508-5.789 13.508c-0.064 0.145-0.101 0.314-0.101 0.492 0 0.69 0.56 1.25 1.25 1.25 0 0 0 0 0.001 0h-0c0.001 0 0.002 0 0.003 0 0.203 0 0.394-0.049 0.563-0.136l-0.007 0.003 28-13.999c0.027-0.013 0.038-0.043 0.064-0.058 0.148-0.088 0.272-0.202 0.369-0.336l0.002-0.004c0.030-0.038 0.060-0.082 0.086-0.127l0.003-0.006zM4.493 4.645l20.212 10.105h-15.88zM8.825 17.25h15.88l-20.212 10.105z"></path></svg>' })
		);

		chatWindow.appendChild( chatMessageBox );
		chatWindow.appendChild( chatCredits );
		chatCredits.appendChild( chatCreditsAnchor );
		chatWrite.appendChild( chatInputText );
		chatInputBox.appendChild( chatWrite );
		chatInputBox.appendChild( chatSendButton );
		chatWindow.appendChild( chatInputBox );
		document.body.appendChild( chatWindow );
		document.body.appendChild( chatOpen );
		document.body.appendChild( chatClose );
	}
};

export default App;
