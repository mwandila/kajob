/**
 * Kajob AI Mode — full-page assistant.
 * Dependency-free; talks to the tool-calling agent endpoint in inc/agent.php.
 *
 * Security model: assistant text is rendered as TEXT NODES only (never
 * innerHTML) with a conservative linkifier/bold pass built via DOM APIs.
 * The only HTML ever injected is `cards`, which the server builds from the
 * theme's own kajob_job_card() markup.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-aimode]');
	if (!root) {
		return;
	}

	var cfg = window.kajobAgent || {};
	var i18n = cfg.i18n || {};
	var storeKey = 'kajobAIModeHistory';

	var idle = root.querySelector('[data-ai-idle]');
	var chat = root.querySelector('[data-ai-chat]');
	var log = root.querySelector('[data-ai-log]');
	var historyBox = root.querySelector('[data-ai-history]');
	var forms = root.querySelectorAll('[data-ai-form]');
	var inputs = root.querySelectorAll('[data-ai-input]');
	var consentInput = root.querySelector('[data-ai-consent]');
	var newBtns = root.querySelectorAll('[data-ai-new]');
	var chips = root.querySelectorAll('[data-ai-chip]');

	/**
	 * Conversation: { id, title, messages: [{ role, content }] }
	 */
	var messages = [];
	var conversations = [];
	var busy = false;
	var privacyConsentGiven = false;

	/* ------------------------------------------------------------------ */
	/* Persistence                                                         */
	/* ------------------------------------------------------------------ */

	function loadConversations() {
		try {
			var raw = window.localStorage.getItem(storeKey);
			conversations = raw ? JSON.parse(raw) : [];
			if (!Array.isArray(conversations)) {
				conversations = [];
			}
		} catch (e) {
			conversations = [];
		}
	}

	function saveConversations() {
		try {
			window.localStorage.setItem(storeKey, JSON.stringify(conversations.slice(0, 12)));
		} catch (e) {
			/* Storage unavailable (private mode) — history just won't persist. */
		}
	}

	function conversationTitle() {
		var first = '';
		for (var i = 0; i < messages.length; i++) {
			if (messages[i].role === 'user') {
				first = messages[i].content;
				break;
			}
		}
		return first.length > 42 ? first.slice(0, 42) + '…' : (first || 'New chat');
	}

	function persist() {
		if (!messages.length) {
			return;
		}
		var id = conversationTitle();
		for (var i = 0; i < conversations.length; i++) {
			if (conversations[i].id === id) {
				conversations[i].messages = messages.slice();
				saveConversations();
				return;
			}
		}
		conversations.unshift({ id: id, messages: messages.slice() });
		saveConversations();
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                           */
	/* ------------------------------------------------------------------ */

	function scrollLog() {
		log.scrollTop = log.scrollHeight;
	}

	function el(tag, className, text) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (text) {
			node.textContent = text;
		}
		return node;
	}

	/** Render inline **bold** and bare URLs without parsing untrusted HTML. */
	function renderInline(text, parent) {
		var fragment = document.createDocumentFragment();
		var pattern = /(https?:\/\/[^\s<>"]+)|(\*\*[^*]+\*\*)/g;
		var lastIndex = 0;
		var match;

		while ((match = pattern.exec(text)) !== null) {
			if (match.index > lastIndex) {
				fragment.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
			}

			if (match[1]) {
				var link = document.createElement('a');
				link.href = match[1];
				link.textContent = match[1].replace(/^https?:\/\//, '');
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
				fragment.appendChild(link);
			} else if (match[2]) {
				var strong = document.createElement('strong');
				strong.textContent = match[2].slice(2, -2);
				fragment.appendChild(strong);
			}

			lastIndex = match.index + match[0].length;
		}

		if (lastIndex < text.length) {
			fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
		}

		parent.appendChild(fragment);
	}

	/** Render common Markdown structure using safe DOM nodes only. */
	function renderAssistantText(text) {
		var fragment = document.createDocumentFragment();
		var lines = String(text).replace(/\r\n?/g, '\n').split('\n');
		var paragraph = [];
		var list = null;

		function flushParagraph() {
			if (!paragraph.length) return;
			var p = el('p', 'aimode-markdown__paragraph');
			renderInline(paragraph.join(' '), p);
			fragment.appendChild(p);
			paragraph = [];
		}

		function closeList() {
			if (list) {
				fragment.appendChild(list);
				list = null;
			}
		}

		lines.forEach(function (line) {
			var heading = line.match(/^\s{0,3}(#{1,3})\s+(.+)$/);
			var bullet = line.match(/^\s*[-*•]\s+(.+)$/);
			var numbered = line.match(/^\s*\d+[.)]\s+(.+)$/);
			var item = bullet || numbered;

			if (!line.trim()) {
				flushParagraph();
				closeList();
				return;
			}
			if (heading) {
				flushParagraph();
				closeList();
				var level = Math.min(4, heading[1].length + 2);
				var h = el('h' + level, 'aimode-markdown__heading');
				renderInline(heading[2], h);
				fragment.appendChild(h);
				return;
			}
			if (item) {
				flushParagraph();
				var type = numbered ? 'ol' : 'ul';
				if (list && list.tagName.toLowerCase() !== type) closeList();
				if (!list) list = el(type, 'aimode-markdown__list');
				var li = el('li');
				renderInline(item[1], li);
				list.appendChild(li);
				return;
			}
			closeList();
			paragraph.push(line.trim());
		});

		flushParagraph();
		closeList();
		return fragment;
	}

	function addMessage(role, content, extraClass) {
		var row = el('div', 'aimode-row aimode-row--' + role);

		var avatar = el('span', 'aimode-row__avatar');
		avatar.innerHTML = role === 'user' ? userIcon : botIcon;
		row.appendChild(avatar);

		var body = el('div', 'aimode-row__body');
		var bubble = el('div', 'aimode-row__bubble' + (extraClass ? ' ' + extraClass : ''));

		if (role === 'user') {
			bubble.textContent = content;
		} else {
			bubble.appendChild(renderAssistantText(content));
		}
		body.appendChild(bubble);

		var time = el('span', 'aimode-row__time', timeStamp());
		body.appendChild(time);

		row.appendChild(body);
		log.appendChild(row);
		scrollLog();
		return row;
	}

	function addCards(html) {
		if (!html) {
			return;
		}
		var wrapper = el('div', 'aimode-cards');
		/* Server-generated theme markup (kajob_job_card) — the only trusted HTML. */
		wrapper.innerHTML = html;
		log.appendChild(wrapper);
		scrollLog();
	}

	var thinkingRow = null;

	function showThinking() {
		thinkingRow = el('div', 'aimode-row aimode-row--bot aimode-row--thinking');
		thinkingRow.setAttribute('aria-label', i18n.thinking || 'Thinking…');

		var avatar = el('span', 'aimode-row__avatar');
		avatar.innerHTML = botIcon;
		thinkingRow.appendChild(avatar);

		var body = el('div', 'aimode-row__body');
		var bubble = el('div', 'aimode-row__bubble');
		var spinner = el('span', 'aimode-spinner');
		bubble.appendChild(spinner);
		bubble.appendChild(document.createTextNode(i18n.thinking || 'Thinking…'));
		body.appendChild(bubble);
		thinkingRow.appendChild(body);

		log.appendChild(thinkingRow);
		scrollLog();
	}

	function hideThinking() {
		if (thinkingRow && thinkingRow.parentNode) {
			thinkingRow.parentNode.removeChild(thinkingRow);
		}
		thinkingRow = null;
	}

	function timeStamp() {
		var now = new Date();
		var h = now.getHours() % 12 || 12;
		var m = now.getMinutes();
		return h + ':' + (m < 10 ? '0' + m : m) + (now.getHours() >= 12 ? ' PM' : ' AM');
	}

	var botIcon = '<span class="aimode-avatar aimode-avatar--bot"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.1 5.6 5.6 2.1-5.6 2.1L12 17.9l-2.1-5.6-5.6-2.1 5.6-2.1L12 2.5zm7 12l1 2.7 2.7 1-2.7 1-1 2.7-1-2.7-2.7-1 2.7-1 1-2.7z"/></svg></span>';
	var userIcon = '<span class="aimode-avatar aimode-avatar--user"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2.2c-4.4 0-8 2.3-8 5.1V21h16v-1.7c0-2.8-3.6-5.1-8-5.1z"/></svg></span>';

	/* ------------------------------------------------------------------ */
	/* Views                                                               */
	/* ------------------------------------------------------------------ */

	function showChat() {
		idle.hidden = true;
		chat.hidden = false;
	}

	function showIdle() {
		idle.hidden = false;
		chat.hidden = true;
	}

	function renderHistory() {
		historyBox.textContent = '';

		if (!conversations.length) {
			var empty = el('p', 'aimode__historyempty', 'Your conversations will appear here.');
			historyBox.appendChild(empty);
			return;
		}

		conversations.forEach(function (conversation) {
			var item = el('button', 'aimode__historyitem');
			item.type = 'button';
			item.textContent = conversation.id;
			item.title = conversation.id;
			item.addEventListener('click', function () {
				messages = conversation.messages.slice();
				log.textContent = '';
				messages.forEach(function (message) {
					addMessage(message.role, message.content);
				});
				showChat();
			});
			historyBox.appendChild(item);
		});
	}

	function newChat() {
		messages = [];
		log.textContent = '';
		hideThinking();
		showIdle();
		setBusy(false);
		var firstInput = root.querySelector('[data-ai-idle] [data-ai-input]');
		if (firstInput && window.innerWidth > 940) {
			firstInput.focus();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Request round-trip                                                  */
	/* ------------------------------------------------------------------ */

	function submit(text) {
		var clean = (text || '').trim();

		if (!clean || busy) {
			return;
		}
		if (!privacyConsentGiven) {
			if (!consentInput || !consentInput.checked) {
				if (consentInput) {
					consentInput.focus();
				}
				return;
			}
			privacyConsentGiven = true;
		}

		messages.push({ role: 'user', content: clean });
		if (messages.length > 12) {
			messages = messages.slice(-12);
		}

		showChat();
		addMessage('user', clean);
		persist();
		renderHistory();

		inputs.forEach(function (field) {
			field.value = '';
		});
		setBusy(true);
		showThinking();

		var body = new URLSearchParams();
		body.append('action', 'kajob_agent');
		body.append('nonce', cfg.nonce || '');
		body.append('messages', JSON.stringify(messages));
		body.append('privacy_consent', '1');

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				hideThinking();
				setBusy(false);

				if (json && json.success && json.data) {
					var reply = String(json.data.reply || '');
					messages.push({ role: 'assistant', content: reply });
					if (messages.length > 12) {
						messages = messages.slice(-12);
					}
					addMessage('assistant', reply);
					addCards(json.data.cards || '');
					persist();
				} else {
					var message = (json && json.data && json.data.message) || i18n.error || 'Error.';
					addMessage('assistant', message, 'aimode-row__bubble--error');
				}
			})
			.catch(function () {
				hideThinking();
				setBusy(false);
				addMessage('assistant', i18n.error || 'Error.', 'aimode-row__bubble--error');
			});
	}

	function setBusy(state) {
		busy = state;
		inputs.forEach(function (field) {
			field.disabled = state;
		});
	}

	/* ------------------------------------------------------------------ */
	/* Wiring                                                              */
	/* ------------------------------------------------------------------ */

	forms.forEach(function (form) {
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			var field = form.querySelector('[data-ai-input]');
			submit(field ? field.value : '');
		});
	});

	chips.forEach(function (chip) {
		chip.addEventListener('click', function () {
			submit(chip.getAttribute('data-ai-chip'));
		});
	});

	newBtns.forEach(function (button) {
		button.addEventListener('click', newChat);
	});

	loadConversations();
	renderHistory();
})();
