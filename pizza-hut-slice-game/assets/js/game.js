/**
 * Pizza Hut Slice Game – מנוע המשחק (Vanilla JS).
 *
 * זרימה: פתיחה ← טופס ← משחק ← סיום.
 * המשולש מופיע במיקום אקראי, נשאר עד 10 שניות או עד לחיצה,
 * כל לחיצה = נקודה + מעבר מיידי למיקום חדש. משך המשחק 60 שניות.
 */
(function () {
	'use strict';

	if (typeof window.PHSG_DATA === 'undefined') {
		return;
	}

	var CFG = window.PHSG_DATA;
	var GAME_DURATION = parseInt(CFG.gameDuration, 10) || 60; // שניות.
	var SLICE_TIMEOUT = parseInt(CFG.sliceTimeout, 10) || 10; // שניות.

	/**
	 * מופע משחק בודד (תומך במספר שורטקודים בעמוד).
	 *
	 * @param {HTMLElement} root שורש האפליקציה.
	 */
	function PizzaHutGame(root) {
		this.root = root;
		this.screens = {};
		root.querySelectorAll('[data-screen]').forEach(function (el) {
			this.screens[el.getAttribute('data-screen')] = el;
		}, this);

		this.arena = root.querySelector('[data-arena]');
		this.slice = root.querySelector('[data-slice]');
		this.loader = root.querySelector('[data-loader]');
		this.form = root.querySelector('.phsg-form');

		// מצב משחק.
		this.state = this._freshState();
		this.utm = this._captureUtm();
		this.participant = null;

		this._bind();
	}

	PizzaHutGame.prototype._freshState = function () {
		return {
			running: false,
			score: 0,
			reactions: [],      // זמני תגובה במ"ש.
			startTime: 0,
			endTime: 0,
			sliceShownAt: 0,
			timerInterval: null,
			sliceTimeout: null,
			rafId: null,
			timeLeft: GAME_DURATION
		};
	};

	/**
	 * חיווט אירועים.
	 */
	PizzaHutGame.prototype._bind = function () {
		var self = this;

		this.root.querySelectorAll('[data-action]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				var action = btn.getAttribute('data-action');
				if (action === 'start-game') {
					return; // מטופל ב-submit של הטופס.
				}
				e.preventDefault();
				self._onAction(action);
			});
		});

		if (this.form) {
			this.form.addEventListener('submit', function (e) {
				e.preventDefault();
				self._onFormSubmit();
			});
		}

		if (this.slice) {
			// pointerdown לתגובה מהירה יותר ממ-click.
			this.slice.addEventListener('pointerdown', function (e) {
				e.preventDefault();
				self._onSliceHit();
			});
		}
	};

	PizzaHutGame.prototype._onAction = function (action) {
		switch (action) {
			case 'go-form':
				this._show('form');
				break;
			case 'play-again':
				this._resetToForm();
				break;
		}
	};

	/**
	 * הצגת מסך בודד.
	 *
	 * @param {string} name שם המסך.
	 */
	PizzaHutGame.prototype._show = function (name) {
		Object.keys(this.screens).forEach(function (key) {
			var el = this.screens[key];
			var active = key === name;
			el.hidden = !active;
			el.classList.toggle('is-active', active);
		}, this);
	};

	/* ==================== טופס ==================== */

	PizzaHutGame.prototype._onFormSubmit = function () {
		var data = this._readForm();
		var errors = this._validateForm(data);

		this._clearErrors();
		if (Object.keys(errors).length > 0) {
			this._showErrors(errors);
			return;
		}

		this.participant = data;
		this._startGame();
	};

	PizzaHutGame.prototype._readForm = function () {
		var f = this.form;
		return {
			full_name: (f.querySelector('[name="full_name"]').value || '').trim(),
			phone: (f.querySelector('[name="phone"]').value || '').replace(/[^0-9]/g, ''),
			email: (f.querySelector('[name="email"]').value || '').trim(),
			consent: f.querySelector('[name="consent"]').checked
		};
	};

	PizzaHutGame.prototype._validateForm = function (d) {
		var errors = {};
		var i18n = CFG.i18n || {};

		if (!d.full_name || d.full_name.length < 2) {
			errors.full_name = i18n.required || 'שדה חובה';
		}
		if (!d.phone || !/^[0-9]{9,15}$/.test(d.phone)) {
			errors.phone = i18n.invalidPhone || 'טלפון לא תקין';
		}
		if (!d.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) {
			errors.email = i18n.invalidEmail || 'אימייל לא תקין';
		}
		if (!d.consent) {
			errors.consent = i18n.consentNeeded || 'יש לאשר את תנאי ההשתתפות';
		}
		return errors;
	};

	PizzaHutGame.prototype._clearErrors = function () {
		this.form.querySelectorAll('.phsg-field').forEach(function (field) {
			field.classList.remove('has-error');
		});
		this.form.querySelectorAll('[data-error-for]').forEach(function (span) {
			span.textContent = '';
		});
	};

	PizzaHutGame.prototype._showErrors = function (errors) {
		var self = this;
		Object.keys(errors).forEach(function (name) {
			var span = self.form.querySelector('[data-error-for="' + name + '"]');
			if (span) {
				span.textContent = errors[name];
				var field = span.closest('.phsg-field');
				if (field) {
					field.classList.add('has-error');
				}
			}
		});
	};

	/* ==================== משחק ==================== */

	PizzaHutGame.prototype._startGame = function () {
		this.state = this._freshState();
		this.state.running = true;
		this.state.startTime = Date.now();
		this.state.timeLeft = GAME_DURATION;

		this._updateHud();
		this._show('game');
		if (this.arena && this.arena.focus) {
			this.arena.focus();
		}

		var self = this;

		// טיימר ספירה לאחור (עדכון תצוגה כל 200ms, דיוק לפי startTime).
		this.state.timerInterval = setInterval(function () {
			var elapsed = (Date.now() - self.state.startTime) / 1000;
			self.state.timeLeft = Math.max(0, GAME_DURATION - elapsed);
			self._updateHud();
			if (self.state.timeLeft <= 0) {
				self._endGame();
			}
		}, 200);

		this._spawnSlice();
	};

	/**
	 * מיקום המשולש במקום אקראי בתוך הזירה.
	 */
	PizzaHutGame.prototype._spawnSlice = function () {
		if (!this.state.running) {
			return;
		}

		clearTimeout(this.state.sliceTimeout);

		var arenaRect = this.arena.getBoundingClientRect();
		var sliceSize = this.slice.offsetWidth || 74;
		var pad = sliceSize / 2 + 6;

		var maxX = Math.max(pad, arenaRect.width - pad);
		var maxY = Math.max(pad, arenaRect.height - pad);
		var x = pad + Math.random() * (maxX - pad);
		var y = pad + Math.random() * (maxY - pad);

		this.slice.style.left = x + 'px';
		this.slice.style.top = y + 'px';
		this.slice.hidden = false;
		// אתחול אנימציית pop.
		this.slice.style.animation = 'none';
		/* eslint-disable no-unused-expressions */
		this.slice.offsetHeight;
		/* eslint-enable no-unused-expressions */
		this.slice.style.animation = '';

		this.state.sliceShownAt = Date.now();

		var self = this;
		// אם לא נלחץ תוך SLICE_TIMEOUT שניות – מעבר למיקום חדש (החטאה).
		this.state.sliceTimeout = setTimeout(function () {
			self._spawnSlice();
		}, SLICE_TIMEOUT * 1000);
	};

	/**
	 * טיפול בפגיעה במשולש.
	 */
	PizzaHutGame.prototype._onSliceHit = function () {
		if (!this.state.running || this.slice.hidden) {
			return;
		}

		var reaction = Date.now() - this.state.sliceShownAt;
		// שמירת זמן תגובה סביר בלבד (הגנה מפני ערכים חריגים).
		if (reaction >= 0 && reaction <= SLICE_TIMEOUT * 1000) {
			this.state.reactions.push(reaction);
		}

		this.state.score += 1;
		this._updateHud();

		// מעבר מיידי למיקום חדש.
		this._spawnSlice();
	};

	PizzaHutGame.prototype._updateHud = function () {
		var scoreEl = this.root.querySelector('[data-hud="score"]');
		var timeEl = this.root.querySelector('[data-hud="time"]');
		if (scoreEl) {
			scoreEl.textContent = this.state.score;
		}
		if (timeEl) {
			timeEl.textContent = Math.ceil(this.state.timeLeft);
		}
	};

	PizzaHutGame.prototype._endGame = function () {
		if (!this.state.running) {
			return;
		}
		this.state.running = false;
		this.state.endTime = Date.now();

		clearInterval(this.state.timerInterval);
		clearTimeout(this.state.sliceTimeout);
		this.slice.hidden = true;

		var duration = (this.state.endTime - this.state.startTime) / 1000;
		var reactions = this.state.reactions;
		var avgReaction = 0;
		if (reactions.length > 0) {
			var sum = reactions.reduce(function (a, b) { return a + b; }, 0);
			avgReaction = sum / reactions.length;
		}

		this._submitScore({
			score: this.state.score,
			duration: Math.round(duration * 100) / 100,
			avg_reaction: Math.round(avgReaction * 100) / 100
		});
	};

	/* ==================== הגשה לשרת ==================== */

	PizzaHutGame.prototype._submitScore = function (result) {
		var self = this;
		this._show('end');
		this._fillResult(result, null);
		this._toggleLoader(true);

		var body = new URLSearchParams();
		body.append('action', 'phsg_submit_score');
		body.append('nonce', CFG.nonce);
		body.append('full_name', this.participant.full_name);
		body.append('phone', this.participant.phone);
		body.append('email', this.participant.email);
		body.append('consent', this.participant.consent ? '1' : '0');
		body.append('score', result.score);
		body.append('duration', result.duration);
		body.append('avg_reaction', result.avg_reaction);
		body.append('utm_source', this.utm.utm_source);
		body.append('utm_medium', this.utm.utm_medium);
		body.append('utm_campaign', this.utm.utm_campaign);
		body.append('utm_term', this.utm.utm_term);
		body.append('utm_content', this.utm.utm_content);

		fetch(CFG.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
			credentials: 'same-origin'
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				self._toggleLoader(false);
				if (json && json.success && json.data) {
					self._fillResult(result, json.data);
					self._renderLeaderboard(json.data.leaderboard, json.data.display_name);
				} else {
					var msg = (json && json.data && json.data.message) || (CFG.i18n && CFG.i18n.saveError);
					self._showResultMessage(msg || 'שגיאה');
				}
			})
			.catch(function () {
				self._toggleLoader(false);
				self._showResultMessage((CFG.i18n && CFG.i18n.saveError) || 'שגיאה');
			});
	};

	PizzaHutGame.prototype._fillResult = function (result, data) {
		this._setText('[data-result="score"]', result.score);
		this._setText('[data-result="time"]', Math.round(result.duration));

		if (data) {
			this._setText('[data-result="rank"]', data.rank);
			var msg = 'דירוג #' + data.rank;
			if (data.total) {
				msg += ' ' + ((CFG.i18n && CFG.i18n.rankOf) || 'מתוך') + ' ' + data.total;
			}
			this._showResultMessage(msg);
		}
	};

	PizzaHutGame.prototype._showResultMessage = function (msg) {
		this._setText('[data-result="msg"]', msg);
	};

	PizzaHutGame.prototype._renderLeaderboard = function (rows, youName) {
		var board = this.root.querySelector('[data-leaderboard]');
		if (!board || !rows) {
			return;
		}

		var i18n = CFG.i18n || {};
		if (!rows.length) {
			board.innerHTML = '<p class="phsg-board__empty">עדיין אין תוצאות – היו הראשונים!</p>';
			return;
		}

		var youMarked = false;
		var html = '<div class="phsg-board__head">' +
			'<span>דירוג</span><span>שחקן/ית</span><span>ניקוד</span><span>זמן</span></div>';

		rows.forEach(function (row) {
			var medal = '';
			if (row.rank === 1) { medal = ' phsg-board__row--gold'; }
			else if (row.rank === 2) { medal = ' phsg-board__row--silver'; }
			else if (row.rank === 3) { medal = ' phsg-board__row--bronze'; }

			// סימון השחקן הנוכחי (פעם אחת) לפי שם התצוגה.
			var you = '';
			if (!youMarked && youName && row.display_name === youName) {
				you = ' is-you';
				youMarked = true;
			}

			html += '<div class="phsg-board__row' + medal + you + '">' +
				'<span class="phsg-board__rank">' + esc(row.rank) + '</span>' +
				'<span class="phsg-board__name">' + esc(row.display_name) + '</span>' +
				'<span class="phsg-board__score">' + esc(row.score) + '</span>' +
				'<span class="phsg-board__time">' + esc(Math.round(row.duration)) + '"</span>' +
				'</div>';
		});

		board.innerHTML = html;
	};

	PizzaHutGame.prototype._resetToForm = function () {
		// סיבוב נוסף עם אותו משתתף.
		this._startGame();
	};

	PizzaHutGame.prototype._toggleLoader = function (show) {
		if (this.loader) {
			this.loader.hidden = !show;
		}
	};

	PizzaHutGame.prototype._setText = function (selector, value) {
		var el = this.root.querySelector(selector);
		if (el) {
			el.textContent = value;
		}
	};

	/* ==================== UTM ==================== */

	PizzaHutGame.prototype._captureUtm = function () {
		var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
		var out = {};
		var params;
		try {
			params = new URLSearchParams(window.location.search);
		} catch (e) {
			params = null;
		}

		keys.forEach(function (k) {
			var val = '';
			if (params && params.get(k)) {
				val = params.get(k);
			} else {
				// גיבוי מ-sessionStorage (למקרה שהמשתמש ניווט מהעמוד הראשון).
				try {
					val = window.sessionStorage.getItem('phsg_' + k) || '';
				} catch (e2) {
					val = '';
				}
			}
			out[k] = (val || '').substring(0, 120);
			// שמירה להמשך הסשן.
			try {
				if (out[k]) {
					window.sessionStorage.setItem('phsg_' + k, out[k]);
				}
			} catch (e3) { /* מתעלמים */ }
		});

		return out;
	};

	/**
	 * escaping בסיסי לפני הזרקה ל-HTML.
	 *
	 * @param {*} v ערך.
	 * @return {string}
	 */
	function esc(v) {
		return String(v).replace(/[&<>"']/g, function (c) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;'
			}[c];
		});
	}

	/**
	 * אתחול – לאחר הגדרת כל מתודות ה-prototype.
	 */
	function init() {
		document.querySelectorAll('.phsg-app').forEach(function (root) {
			new PizzaHutGame(root);
		});
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
