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
	var SLICE_TIMEOUT = parseInt(CFG.sliceTimeout, 10) || 10; // שניות – חסם עליון (אנטי-רמייה).
	var COMBO_WINDOW = 1500; // מ"ש – פגיעה מהירה מזו ממשיכה קומבו.

	// קושי מדורג: המשולש מתחיל "איטי וגדול" ונהיה מהיר וקטן עם הניקוד.
	var BASE_TIMEOUT_S = Math.min(3, SLICE_TIMEOUT); // זמן שהות התחלתי.
	var MIN_TIMEOUT_S = 1.1;                          // זמן שהות מינימלי.
	var TIMEOUT_STEP_S = 0.08;                        // קיצור לכל נקודה.
	var BASE_SIZE_PX = 74;                            // גודל התחלתי.
	var MIN_SIZE_PX = 46;                             // גודל מינימלי.
	var SIZE_STEP_PX = 1.2;                           // הקטנה לכל נקודה.

	// משולשים מיוחדים.
	var GOLD_CHANCE = 0.12;      // סיכוי למשולש זהב (אחרי 5 נקודות).
	var GOLD_MIN_SCORE = 5;      // ניקוד מינימלי להופעת זהב.
	var GOLD_POINTS = 3;         // שווי משולש זהב.
	var GOLD_TIMEOUT_S = 2;      // זהב נעלם תוך 2 שניות – חייבים להיות זריזים.
	var TRAP_CHANCE = 0.1;       // סיכוי למשולש מלכודת (אחרי 3 נקודות).
	var TRAP_MIN_SCORE = 3;      // ניקוד מינימלי להופעת מלכודת.
	var TRAP_POINTS = -1;        // מלכודת מורידה נקודה.

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
				case 'gold':
					// פנפרה קצרה למשולש הזהב.
					this.tone(784, 0.09, 'square', 0.1);
					this.tone(988, 0.09, 'square', 0.1, 0.08);
					this.tone(1319, 0.2, 'triangle', 0.12, 0.16);
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
		},

		/* ===== מוזיקת רקע 8-ביט (לופ מסונתז) ===== */

		musicTimer: null,
		musicStep: 0,
		musicFast: false,

		// תבנית בס + מלודיה קלילה בסולם דו מז'ור (הרץ). 0 = שקט.
		MUSIC_BASS: [131, 0, 131, 0, 165, 0, 165, 0, 147, 0, 147, 0, 196, 0, 165, 0],
		MUSIC_LEAD: [523, 0, 659, 523, 0, 784, 0, 659, 587, 0, 698, 587, 0, 880, 784, 0],

		startMusic: function () {
			this.stopMusic();
			this.musicStep = 0;
			this.musicFast = false;
			var self = this;
			var schedule = function () {
				if (!self.muted) {
					var i = self.musicStep % self.MUSIC_BASS.length;
					var bass = self.MUSIC_BASS[i];
					var lead = self.MUSIC_LEAD[i];
					if (bass) {
						self.tone(bass, 0.1, 'triangle', 0.035);
					}
					if (lead) {
						self.tone(lead, 0.07, 'square', 0.02);
					}
				}
				self.musicStep++;
				// טמפו רגיל 140ms לצעד; ב-10 שניות אחרונות – 100ms (מלחיץ).
				self.musicTimer = window.setTimeout(schedule, self.musicFast ? 100 : 140);
			};
			schedule();
		},

		setMusicFast: function (fast) {
			this.musicFast = !!fast;
		},

		stopMusic: function () {
			if (this.musicTimer) {
				window.clearTimeout(this.musicTimer);
				this.musicTimer = null;
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
		this.timebar = root.querySelector('[data-timebar]');
		this.dailyChampEl = root.querySelector('[data-daily-champ]');
		this.couponEl = root.querySelector('[data-coupon]');

		// לוחות מובילים (יומי/כל הזמנים) לטאבים במסך הסיום.
		this.boards = { daily: null, alltime: null };
		this.activeBoard = 'daily';

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
			lastUrgentTick: -1, // השנייה האחרונה שבה הושמע טיק.
			clicks: 0,          // סך לחיצות מוצלחות (לאנטי-רמייה בשרת).
			sliceType: 'normal' // סוג המשולש הנוכחי: normal | gold | trap.
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
			case 'share-whatsapp':
				this._shareWhatsApp();
				break;
			case 'copy-coupon':
				this._copyCoupon();
				break;
			case 'board-daily':
				this._switchBoard('daily');
				break;
			case 'board-alltime':
				this._switchBoard('alltime');
				break;
		}
	};

	/**
	 * שיתוף התוצאה בוואטסאפ (או Web Share API במובייל).
	 */
	PizzaHutGame.prototype._shareWhatsApp = function () {
		var i18n = CFG.i18n || {};
		var tmpl = i18n.shareText || 'תפסתי %s משולשי פיצה ב-60 שניות במשחק של פיצה האט! 🍕 נסו לעבור אותי:';
		var text = tmpl.replace('%s', String(this.state.score));
		// קישור נקי לעמוד (בלי פרמטרי UTM של המשתמש) + תיוג שיתוף.
		var url = window.location.origin + window.location.pathname + '?utm_source=whatsapp&utm_medium=share&utm_campaign=slice_game';
		var full = text + ' ' + url;

		if (navigator.share) {
			navigator.share({ text: text, url: url }).catch(function () { /* המשתמש ביטל */ });
			return;
		}
		window.open('https://wa.me/?text=' + encodeURIComponent(full), '_blank', 'noopener');
	};

	/**
	 * העתקת קוד הקופון ללוח.
	 */
	PizzaHutGame.prototype._copyCoupon = function () {
		if (!this.couponEl) {
			return;
		}
		var code = this.couponEl.getAttribute('data-coupon-code') || '';
		var btn = this.couponEl.querySelector('[data-action="copy-coupon"]');
		var copied = (CFG.i18n && CFG.i18n.copied) || 'הועתק!';

		var mark = function () {
			if (btn) {
				var original = btn.textContent;
				btn.textContent = copied + ' ✓';
				window.setTimeout(function () { btn.textContent = original; }, 1600);
			}
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(code).then(mark).catch(function () { /* מתעלמים */ });
		}
	};

	/**
	 * החלפת לוח מובילים (יומי/כל הזמנים).
	 *
	 * @param {string} which daily | alltime.
	 */
	PizzaHutGame.prototype._switchBoard = function (which) {
		this.activeBoard = which;
		var self = this;
		this.root.querySelectorAll('.phsg-board-tab').forEach(function (tab) {
			var active = tab.getAttribute('data-action') === 'board-' + which;
			tab.classList.toggle('is-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		if (this.boards[which]) {
			this._renderLeaderboard(this.boards[which], this.lastDisplayName || '');
		}
		return self;
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

		SoundKit.startMusic();
		this._spawnSlice();
	};

	/**
	 * מצב "לחוץ" ב-10 השניות האחרונות: הבהוב טיימר + טיק-טוק + מוזיקה מהירה.
	 */
	PizzaHutGame.prototype._updateUrgency = function () {
		var urgent = this.state.timeLeft <= 10 && this.state.timeLeft > 0;
		var timeItem = this.root.querySelector('.phsg-hud__item--time');
		if (timeItem) {
			timeItem.classList.toggle('is-urgent', urgent);
		}
		this.arena.classList.toggle('is-urgent', urgent);
		if (this.timebar && this.timebar.parentNode) {
			this.timebar.parentNode.classList.toggle('is-urgent', urgent);
		}
		SoundKit.setMusicFast(urgent);

		if (urgent) {
			var sec = Math.ceil(this.state.timeLeft);
			if (sec !== this.state.lastUrgentTick) {
				this.state.lastUrgentTick = sec;
				SoundKit.play('urgent');
			}
		}
	};

	/**
	 * קושי נוכחי לפי ניקוד – זמן שהות וגודל המשולש.
	 *
	 * @return {{timeoutMs: number, size: number}}
	 */
	PizzaHutGame.prototype._difficulty = function () {
		var score = this.state.score;
		var timeoutS = Math.max(MIN_TIMEOUT_S, BASE_TIMEOUT_S - score * TIMEOUT_STEP_S);
		var size = Math.max(MIN_SIZE_PX, Math.round(BASE_SIZE_PX - score * SIZE_STEP_PX));
		return { timeoutMs: timeoutS * 1000, size: size };
	};

	/**
	 * הגרלת סוג המשולש הבא.
	 *
	 * @return {string} normal | gold | trap.
	 */
	PizzaHutGame.prototype._rollSliceType = function () {
		var score = this.state.score;
		var r = Math.random();
		if (score >= GOLD_MIN_SCORE && r < GOLD_CHANCE) {
			return 'gold';
		}
		if (score >= TRAP_MIN_SCORE && r >= GOLD_CHANCE && r < GOLD_CHANCE + TRAP_CHANCE) {
			return 'trap';
		}
		return 'normal';
	};

	/**
	 * מיקום המשולש במקום אקראי בתוך הזירה.
	 */
	PizzaHutGame.prototype._spawnSlice = function () {
		if (!this.state.running) {
			return;
		}

		clearTimeout(this.state.sliceTimeout);

		var diff = this._difficulty();
		var type = this._rollSliceType();
		this.state.sliceType = type;
		this.slice.setAttribute('data-type', type);
		this.slice.style.width = diff.size + 'px';
		this.slice.style.height = diff.size + 'px';

		// זהב בורח מהר במיוחד.
		if (type === 'gold') {
			diff.timeoutMs = Math.min(diff.timeoutMs, GOLD_TIMEOUT_S * 1000);
		}

		var arenaRect = this.arena.getBoundingClientRect();
		var pad = diff.size / 2 + 6;

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
		// אם לא נלחץ בזמן – בריחה למיקום חדש. הזמן מתקצר ככל שהניקוד עולה.
		this.state.sliceTimeout = setTimeout(function () {
			// בריחה שוברת קומבו – לחץ אמיתי.
			self.state.combo = 0;
			self._hideCombo();
			self._spawnSlice();
		}, diff.timeoutMs);
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

		this.state.clicks += 1;

		var type = this.state.sliceType;
		var points = type === 'gold' ? GOLD_POINTS : (type === 'trap' ? TRAP_POINTS : 1);
		this.state.score = Math.max(0, this.state.score + points);

		var pos = this._eventPos(e);

		if (type === 'trap') {
			// מלכודת: שוברת קומבו, בלי חלקיקים חגיגיים.
			this.state.combo = 0;
			this._hideCombo();
			this._fxRing(pos.x, pos.y, true);
			this._fxFloat(pos.x, pos.y, '-1', 'phsg-float--trap');
			this._fxShake();
			SoundKit.play('miss');
			this._vibrate(60);
		} else {
			// קומבו – ויזואלי בלבד, לא משנה ניקוד.
			if (reaction <= COMBO_WINDOW) {
				this.state.combo += 1;
			} else {
				this.state.combo = 1;
			}
			this.state.bestCombo = Math.max(this.state.bestCombo, this.state.combo);

			this._fxBurst(pos.x, pos.y, type === 'gold');
			this._fxRing(pos.x, pos.y);
			this._fxFloat(pos.x, pos.y, '+' + points, type === 'gold' ? 'phsg-float--gold' : 'phsg-float--hit');
			this._fxShake();
			this._showCombo();
			SoundKit.play(type === 'gold' ? 'gold' : 'hit', this.state.combo);
			if (this.state.combo > 1 && this.state.combo % 5 === 0) {
				SoundKit.play('combo');
			}
			this._vibrate(type === 'gold' ? 35 : 18);
		}

		this._updateHud();

		// מעבר מיידי למיקום חדש.
		this._spawnSlice();
	};

	/**
	 * רטט קצר במובייל (אם נתמך).
	 *
	 * @param {number} ms משך הרטט במ"ש.
	 */
	PizzaHutGame.prototype._vibrate = function (ms) {
		if (!REDUCED_MOTION && navigator.vibrate) {
			try {
				navigator.vibrate(ms);
			} catch (err) { /* מתעלמים */ }
		}
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
	// חלקיקי זהב למשולש הזהב.
	var FX_GOLD_COLORS = ['#F5B301', '#FFE9A8', '#FFDD87', '#FFFFFF'];

	PizzaHutGame.prototype._clearFx = function () {
		if (this.fxLayer) {
			this.fxLayer.innerHTML = '';
		}
		this._hideCombo();
	};

	/**
	 * פיצוץ חלקיקים במיקום הפגיעה.
	 *
	 * @param {number}  x    מיקום X בזירה.
	 * @param {number}  y    מיקום Y בזירה.
	 * @param {boolean} gold חלקיקי זהב (משולש זהב).
	 */
	PizzaHutGame.prototype._fxBurst = function (x, y, gold) {
		if (!this.fxLayer || REDUCED_MOTION) {
			return;
		}

		var colors = gold ? FX_GOLD_COLORS : FX_COLORS;
		var count = gold ? 16 : 10;

		for (var i = 0; i < count; i++) {
			var p = document.createElement('span');
			p.className = 'phsg-particle';
			var size = 5 + Math.random() * 7;
			p.style.width = size + 'px';
			p.style.height = size + 'px';
			p.style.left = x + 'px';
			p.style.top = y + 'px';
			p.style.background = colors[Math.floor(Math.random() * colors.length)];
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
		// בר הזמן המתרוקן.
		if (this.timebar) {
			var pct = Math.max(0, Math.min(100, (this.state.timeLeft / GAME_DURATION) * 100));
			this.timebar.style.width = pct + '%';
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
		SoundKit.stopMusic();
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
			clicks: this.state.clicks,
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
		body.append('clicks', result.clicks);
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
					// שמירת שני הלוחות והצגת הטאב הפעיל.
					self.lastDisplayName = json.data.display_name || '';
					self.boards.daily = json.data.daily_leaderboard || [];
					self.boards.alltime = json.data.leaderboard || [];
					self._switchBoard(self.activeBoard);
					self._showDailyChamp(json.data);
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
			this._maybeShowCoupon(result.score);
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

	/**
	 * חשיפת הקופון אם עברו את רף הניקוד (מוגדר בשורטקוד).
	 *
	 * @param {number} score הניקוד הסופי.
	 */
	PizzaHutGame.prototype._maybeShowCoupon = function (score) {
		if (!this.couponEl) {
			return;
		}
		var min = parseInt(this.couponEl.getAttribute('data-coupon-min'), 10) || 0;
		this.couponEl.hidden = score < min;
	};

	/**
	 * באנר "שיאן/ית היום" + דירוג יומי במסך הסיום.
	 *
	 * @param {Object} data תשובת השרת.
	 */
	PizzaHutGame.prototype._showDailyChamp = function (data) {
		if (!this.dailyChampEl) {
			return;
		}
		var i18n = CFG.i18n || {};
		if (data.daily_rank === 1) {
			this.dailyChampEl.textContent = i18n.dailyChamp || 'שיאן/ית היום! 🏆';
			this.dailyChampEl.hidden = false;
		} else if (data.daily_rank > 1) {
			this.dailyChampEl.textContent = (i18n.dailyRank || 'דירוג יומי') + ': #' + data.daily_rank;
			this.dailyChampEl.hidden = false;
		} else {
			this.dailyChampEl.hidden = true;
		}
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
