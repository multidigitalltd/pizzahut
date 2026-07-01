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
	var COMBO_WINDOW = 1500; // מ"ש – פגיעה מהירה מזו ממשיכה קומבו.

	// כיבוד העדפת המשתמש לצמצום אנימציות.
	var REDUCED_MOTION = false;
	try {
		REDUCED_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	} catch (e) { /* מתעלמים */ }

	/* ==================== צלילים (WebAudio, ללא קבצים) ==================== */

	var SoundKit = {
		ctx: null,
		muted: false,

		init: function () {
			try {
				this.muted = window.localStorage.getItem('phsg_muted') === '1';
			} catch (e) { /* מתעלמים */ }
		},

		// יצירת ה-context רק אחרי מחוות משתמש (מדיניות autoplay).
		ensure: function () {
			if (!this.ctx) {
				var AC = window.AudioContext || window.webkitAudioContext;
				if (!AC) {
					return;
				}
				try {
					this.ctx = new AC();
				} catch (e) {
					this.ctx = null;
				}
			}
			if (this.ctx && this.ctx.state === 'suspended') {
				this.ctx.resume();
			}
		},

		setMuted: function (muted) {
			this.muted = muted;
			try {
				window.localStorage.setItem('phsg_muted', muted ? '1' : '0');
			} catch (e) { /* מתעלמים */ }
		},

		/**
		 * השמעת צליל קצר.
		 *
		 * @param {number} freq   תדר בהרץ.
		 * @param {number} dur    משך בשניות.
		 * @param {string} type   צורת גל.
		 * @param {number} vol    עוצמה (0-1).
		 * @param {number} delay  השהיה בשניות.
		 * @param {number} glide  תדר יעד (סליידר) – אופציונלי.
		 */
		tone: function (freq, dur, type, vol, delay, glide) {
			if (this.muted) {
				return;
			}
			this.ensure();
			if (!this.ctx) {
				return;
			}
			var t = this.ctx.currentTime + (delay || 0);
			var osc = this.ctx.createOscillator();
			var gain = this.ctx.createGain();
			osc.type = type || 'sine';
			osc.frequency.setValueAtTime(freq, t);
			if (glide) {
				osc.frequency.exponentialRampToValueAtTime(glide, t + dur);
			}
			gain.gain.setValueAtTime(0.0001, t);
			gain.gain.exponentialRampToValueAtTime(vol || 0.12, t + 0.01);
			gain.gain.exponentialRampToValueAtTime(0.0001, t + dur);
			osc.connect(gain).connect(this.ctx.destination);
			osc.start(t);
			osc.stop(t + dur + 0.05);
		},

		play: function (name, combo) {
			switch (name) {
				case 'hit':
					// גובה עולה עם הקומבו – פידבק מתגמל.
					var base = 430 + Math.min(combo || 0, 12) * 45;
					this.tone(base, 0.09, 'square', 0.08);
					this.tone(base * 1.5, 0.14, 'sine', 0.1, 0.04);
					break;
				case 'combo':
					this.tone(660, 0.08, 'triangle', 0.1);
					this.tone(880, 0.12, 'triangle', 0.1, 0.07);
					this.tone(1174, 0.16, 'triangle', 0.09, 0.14);
					break;
				case 'miss':
					this.tone(150, 0.12, 'sawtooth', 0.05, 0, 90);
					break;
				case 'tick':
					this.tone(520, 0.05, 'square', 0.05);
					break;
				case 'urgent':
					this.tone(700, 0.06, 'square', 0.06);
					break;
				case 'go':
					this.tone(523, 0.12, 'triangle', 0.12);
					this.tone(659, 0.12, 'triangle', 0.12, 0.12);
					this.tone(784, 0.25, 'triangle', 0.14, 0.24);
					break;
				case 'end':
					this.tone(784, 0.15, 'triangle', 0.12);
					this.tone(659, 0.15, 'triangle', 0.12, 0.15);
					this.tone(523, 0.3, 'triangle', 0.12, 0.3);
					break;
			}
		}
	};

	SoundKit.init();

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
		this.fxLayer = root.querySelector('[data-fx]');
		this.comboEl = root.querySelector('[data-combo]');
		this.countdownEl = root.querySelector('[data-countdown]');
		this.confettiEl = root.querySelector('[data-confetti]');
		this.soundBtn = root.querySelector('[data-action="toggle-sound"]');

		this._syncSoundBtn();

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
			timeLeft: GAME_DURATION,
			combo: 0,           // רצף פגיעות מהירות (ויזואלי בלבד).
			bestCombo: 0,
			lastUrgentTick: -1  // השנייה האחרונה שבה הושמע טיק.
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
				e.stopPropagation();
				self._onSliceHit(e);
			});
		}

		if (this.arena) {
			// לחיצה בזירה שלא על המשולש = החטאה (אפקט + צליל בלבד).
			this.arena.addEventListener('pointerdown', function (e) {
				if (!self.state.running) {
					return;
				}
				self._onMiss(e);
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
			case 'toggle-sound':
				SoundKit.setMuted(!SoundKit.muted);
				this._syncSoundBtn();
				if (!SoundKit.muted) {
					SoundKit.play('tick');
				}
				break;
		}
	};

	PizzaHutGame.prototype._syncSoundBtn = function () {
		if (this.soundBtn) {
			this.soundBtn.textContent = SoundKit.muted ? '🔇' : '🔊';
			this.soundBtn.setAttribute('aria-pressed', SoundKit.muted ? 'true' : 'false');
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
		this._updateHud();
		this._clearFx();
		this._show('game');

		var self = this;
		// ספירה לאחור 3-2-1-GO ורק אז מתחילים למדוד זמן.
		this._runCountdown(function () {
			self._beginRound();
		});
	};

	/**
	 * ספירה לאחור מונפשת לפני תחילת הסיבוב.
	 *
	 * @param {Function} done קריאה בסיום.
	 */
	PizzaHutGame.prototype._runCountdown = function (done) {
		var el = this.countdownEl;
		if (!el) {
			done();
			return;
		}

		var numEl = el.querySelector('[data-countdown-num]');
		var steps = ['3', '2', '1', (CFG.i18n && CFG.i18n.go) || 'GO!'];
		var i = 0;
		el.hidden = false;

		var tick = function () {
			if (i >= steps.length) {
				el.hidden = true;
				done();
				return;
			}
			numEl.textContent = steps[i];
			numEl.classList.toggle('is-go', i === steps.length - 1);
			// ריסטרט אנימציית ה-pop.
			numEl.style.animation = 'none';
			void numEl.offsetHeight;
			numEl.style.animation = '';
			SoundKit.play(i === steps.length - 1 ? 'go' : 'tick');
			i++;
			window.setTimeout(tick, i === steps.length ? 550 : 700);
		};
		tick();
	};

	/**
	 * תחילת סיבוב בפועל – אחרי הספירה לאחור.
	 */
	PizzaHutGame.prototype._beginRound = function () {
		this.state.running = true;
		this.state.startTime = Date.now();
		this.state.timeLeft = GAME_DURATION;

		this._updateHud();
		if (this.arena && this.arena.focus) {
			this.arena.focus({ preventScroll: true });
		}

		var self = this;

		// טיימר ספירה לאחור (עדכון תצוגה כל 200ms, דיוק לפי startTime).
		this.state.timerInterval = setInterval(function () {
			var elapsed = (Date.now() - self.state.startTime) / 1000;
			self.state.timeLeft = Math.max(0, GAME_DURATION - elapsed);
			self._updateHud();
			self._updateUrgency();
			if (self.state.timeLeft <= 0) {
				self._endGame();
			}
		}, 200);

		this._spawnSlice();
	};

	/**
	 * מצב "לחוץ" ב-10 השניות האחרונות: הבהוב טיימר + טיק-טוק.
	 */
	PizzaHutGame.prototype._updateUrgency = function () {
		var urgent = this.state.timeLeft <= 10 && this.state.timeLeft > 0;
		var timeItem = this.root.querySelector('.phsg-hud__item--time');
		if (timeItem) {
			timeItem.classList.toggle('is-urgent', urgent);
		}
		this.arena.classList.toggle('is-urgent', urgent);

		if (urgent) {
			var sec = Math.ceil(this.state.timeLeft);
			if (sec !== this.state.lastUrgentTick) {
				this.state.lastUrgentTick = sec;
				SoundKit.play('urgent');
			}
		}
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
	 *
	 * @param {PointerEvent} e אירוע הלחיצה (למיקום האפקטים).
	 */
	PizzaHutGame.prototype._onSliceHit = function (e) {
		if (!this.state.running || this.slice.hidden) {
			return;
		}

		var reaction = Date.now() - this.state.sliceShownAt;
		// שמירת זמן תגובה סביר בלבד (הגנה מפני ערכים חריגים).
		if (reaction >= 0 && reaction <= SLICE_TIMEOUT * 1000) {
			this.state.reactions.push(reaction);
		}

		this.state.score += 1;

		// קומבו – ויזואלי בלבד, לא משנה ניקוד.
		if (reaction <= COMBO_WINDOW) {
			this.state.combo += 1;
		} else {
			this.state.combo = 1;
		}
		this.state.bestCombo = Math.max(this.state.bestCombo, this.state.combo);

		this._updateHud();

		// אפקטים במיקום הלחיצה.
		var pos = this._eventPos(e);
		this._fxBurst(pos.x, pos.y);
		this._fxRing(pos.x, pos.y);
		this._fxFloat(pos.x, pos.y, '+1', 'phsg-float--hit');
		this._fxShake();
		this._showCombo();
		SoundKit.play('hit', this.state.combo);
		if (this.state.combo > 1 && this.state.combo % 5 === 0) {
			SoundKit.play('combo');
		}

		// מעבר מיידי למיקום חדש.
		this._spawnSlice();
	};

	/**
	 * החטאה – לחיצה בזירה שלא על המשולש.
	 *
	 * @param {PointerEvent} e אירוע הלחיצה.
	 */
	PizzaHutGame.prototype._onMiss = function (e) {
		if (e.target && e.target.closest('[data-slice]')) {
			return; // פגיעה – מטופלת בנפרד.
		}

		// שבירת קומבו.
		if (this.state.combo > 1) {
			this._fxFloat(this._eventPos(e).x, this._eventPos(e).y, ((CFG.i18n && CFG.i18n.comboBroken) || 'הקומבו נשבר!'), 'phsg-float--miss');
		}
		this.state.combo = 0;
		this._hideCombo();

		var pos = this._eventPos(e);
		this._fxRing(pos.x, pos.y, true);
		SoundKit.play('miss');
	};

	/**
	 * מיקום אירוע ביחס לזירה.
	 *
	 * @param {PointerEvent} e אירוע.
	 * @return {{x: number, y: number}}
	 */
	PizzaHutGame.prototype._eventPos = function (e) {
		var rect = this.arena.getBoundingClientRect();
		if (e && typeof e.clientX === 'number' && (e.clientX || e.clientY)) {
			return { x: e.clientX - rect.left, y: e.clientY - rect.top };
		}
		// גיבוי: מרכז המשולש.
		return {
			x: parseFloat(this.slice.style.left) || rect.width / 2,
			y: parseFloat(this.slice.style.top) || rect.height / 2
		};
	};

	/* ==================== אפקטים ==================== */

	// צבעי מותג לחלקיקים: אדום, שמנת, קראפט + "מוצרלה".
	var FX_COLORS = ['#F32735', '#F0EFDD', '#B58967', '#FFDD87', '#FFFFFF'];

	PizzaHutGame.prototype._clearFx = function () {
		if (this.fxLayer) {
			this.fxLayer.innerHTML = '';
		}
		this._hideCombo();
	};

	/**
	 * פיצוץ חלקיקים במיקום הפגיעה.
	 *
	 * @param {number} x מיקום X בזירה.
	 * @param {number} y מיקום Y בזירה.
	 */
	PizzaHutGame.prototype._fxBurst = function (x, y) {
		if (!this.fxLayer || REDUCED_MOTION) {
			return;
		}

		for (var i = 0; i < 10; i++) {
			var p = document.createElement('span');
			p.className = 'phsg-particle';
			var size = 5 + Math.random() * 7;
			p.style.width = size + 'px';
			p.style.height = size + 'px';
			p.style.left = x + 'px';
			p.style.top = y + 'px';
			p.style.background = FX_COLORS[Math.floor(Math.random() * FX_COLORS.length)];
			if (Math.random() < 0.3) {
				p.style.borderRadius = '2px'; // "פלפלוני" מרובע פה ושם.
			}
			this.fxLayer.appendChild(p);

			var angle = Math.random() * Math.PI * 2;
			var dist = 34 + Math.random() * 56;
			var dx = Math.cos(angle) * dist;
			var dy = Math.sin(angle) * dist;

			if (p.animate) {
				p.animate([
					{ transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
					{ transform: 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + (dy + 22) + 'px)) scale(0.2) rotate(' + (Math.random() * 240 - 120) + 'deg)', opacity: 0 }
				], { duration: 420 + Math.random() * 240, easing: 'cubic-bezier(0.2, 0.8, 0.4, 1)' }).onfinish = function () {
					this.effect.target.remove();
				};
			} else {
				p.remove();
			}
		}
	};

	/**
	 * גל הדף (טבעת מתרחבת).
	 *
	 * @param {number}  x    מיקום X.
	 * @param {number}  y    מיקום Y.
	 * @param {boolean} miss האם החטאה (טבעת אפורה).
	 */
	PizzaHutGame.prototype._fxRing = function (x, y, miss) {
		if (!this.fxLayer || REDUCED_MOTION) {
			return;
		}
		var ring = document.createElement('span');
		ring.className = 'phsg-ring' + (miss ? ' phsg-ring--miss' : '');
		ring.style.left = x + 'px';
		ring.style.top = y + 'px';
		this.fxLayer.appendChild(ring);
		window.setTimeout(function () { ring.remove(); }, 500);
	};

	/**
	 * טקסט מרחף (+1 / שבירת קומבו).
	 *
	 * @param {number} x    מיקום X.
	 * @param {number} y    מיקום Y.
	 * @param {string} text הטקסט.
	 * @param {string} cls  מחלקת עיצוב.
	 */
	PizzaHutGame.prototype._fxFloat = function (x, y, text, cls) {
		if (!this.fxLayer) {
			return;
		}
		var el = document.createElement('span');
		el.className = 'phsg-float ' + (cls || '');
		el.textContent = text;
		el.style.left = x + 'px';
		el.style.top = y + 'px';
		this.fxLayer.appendChild(el);
		window.setTimeout(function () { el.remove(); }, 800);
	};

	/**
	 * רעידת מסך עדינה.
	 */
	PizzaHutGame.prototype._fxShake = function () {
		if (REDUCED_MOTION) {
			return;
		}
		var stage = this.root.querySelector('.phsg-stage');
		if (!stage) {
			return;
		}
		stage.classList.remove('is-shaking');
		void stage.offsetHeight;
		stage.classList.add('is-shaking');
	};

	PizzaHutGame.prototype._showCombo = function () {
		if (!this.comboEl) {
			return;
		}
		if (this.state.combo < 2) {
			this._hideCombo();
			return;
		}
		var fire = this.state.combo >= 5 ? ' 🔥' : '';
		this.comboEl.textContent = ((CFG.i18n && CFG.i18n.combo) || 'קומבו') + ' x' + this.state.combo + fire;
		this.comboEl.hidden = false;
		this.comboEl.classList.toggle('is-hot', this.state.combo >= 5);
		// ריסטרט אנימציית pop.
		this.comboEl.style.animation = 'none';
		void this.comboEl.offsetHeight;
		this.comboEl.style.animation = '';
	};

	PizzaHutGame.prototype._hideCombo = function () {
		if (this.comboEl) {
			this.comboEl.hidden = true;
		}
	};

	/**
	 * קונפטי חגיגי במסך הסיום.
	 */
	PizzaHutGame.prototype._fxConfetti = function () {
		if (!this.confettiEl || REDUCED_MOTION) {
			return;
		}
		this.confettiEl.innerHTML = '';
		for (var i = 0; i < 36; i++) {
			var c = document.createElement('span');
			c.className = 'phsg-confetti__piece';
			c.style.left = (Math.random() * 100) + '%';
			c.style.background = FX_COLORS[i % FX_COLORS.length];
			c.style.animationDelay = (Math.random() * 0.9) + 's';
			c.style.animationDuration = (1.6 + Math.random() * 1.6) + 's';
			c.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
			this.confettiEl.appendChild(c);
		}
		var el = this.confettiEl;
		window.setTimeout(function () { el.innerHTML = ''; }, 4200);
	};

	/**
	 * ספירת ניקוד עולה במסך הסיום.
	 *
	 * @param {number} target הניקוד הסופי.
	 */
	PizzaHutGame.prototype._countUpScore = function (target) {
		var el = this.root.querySelector('[data-result="score"]');
		if (!el) {
			return;
		}
		if (REDUCED_MOTION || target <= 0) {
			el.textContent = target;
			return;
		}
		var start = null;
		var dur = 900;
		var step = function (ts) {
			if (!start) {
				start = ts;
			}
			var t = Math.min(1, (ts - start) / dur);
			// easing – מאט לקראת הסוף.
			var eased = 1 - Math.pow(1 - t, 3);
			el.textContent = Math.round(eased * target);
			if (t < 1) {
				window.requestAnimationFrame(step);
			}
		};
		window.requestAnimationFrame(step);
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
		this._hideCombo();
		this._clearFx();
		this.arena.classList.remove('is-urgent');
		SoundKit.play('end');

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
		if (data) {
			this._setText('[data-result="score"]', result.score);
		} else {
			// כניסה ראשונה למסך הסיום – חגיגה.
			this._countUpScore(result.score);
			this._fxConfetti();
		}
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
