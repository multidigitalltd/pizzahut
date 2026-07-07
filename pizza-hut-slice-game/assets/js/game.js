/**
 * Pizza Hut Slice Game – מנוע המשחק (Vanilla JS).
 *
 * פורט נאמן של לוגיקת אב-הטיפוס המאושר (design handoff v2):
 * מסכים: intro → form → countdown → game → end.
 * חוקים: משולש +1 · זהב +3 (10%) · פרנזי ×2 · רצף 5 = +2 בונוס ·
 * מכשול −1 · שרוף −2 · שעון +5 שנ' · לולאת משחק כל 100ms · שלבים 1–5.
 */
(function () {
	'use strict';

	if (typeof window.PHSG_DATA === 'undefined') {
		return;
	}

	var CFG = window.PHSG_DATA;
	var I18N = CFG.i18n || {};

	function t(key, fallback) {
		return I18N[key] || fallback;
	}

	/**
	 * מופע משחק בודד.
	 *
	 * @param {HTMLElement} root שורש האפליקציה.
	 */
	// שלבים מבוססי-תפיסות, ללא הגבלה – עד שנכשלים.
	var LEVEL_TARGETS = CFG.levelTargets || [7, 9, 12, 15, 18];
	var LEVEL_TIMES = CFG.levelTimes || [60, 45, 35, 30, 25];
	var MAX_STRIKES = 4; // יותר מ-4 פגיעות במכשולים בשלב = סוף המשחק.

	/**
	 * מכסת התפיסות של שלב: אחרי הטבלה – עוד 3 לכל שלב.
	 *
	 * @param {number} idx אינדקס שלב (0-based).
	 * @return {number}
	 */
	function levelTarget(idx) {
		if (idx < LEVEL_TARGETS.length) {
			return LEVEL_TARGETS[idx];
		}
		return LEVEL_TARGETS[LEVEL_TARGETS.length - 1] + (idx - LEVEL_TARGETS.length + 1) * 3;
	}

	/**
	 * מגבלת הזמן של שלב: אחרי הטבלה – מתקצר עד רצפה של 18 שניות.
	 *
	 * @param {number} idx אינדקס שלב (0-based).
	 * @return {number}
	 */
	function levelTime(idx) {
		if (idx < LEVEL_TIMES.length) {
			return LEVEL_TIMES[idx];
		}
		return Math.max(18, LEVEL_TIMES[LEVEL_TIMES.length - 1] - (idx - LEVEL_TIMES.length + 1) * 2);
	}

	function Game(root) {
		this.root = root;
		this.dur = parseInt(CFG.gameDuration, 10) || 60;

		this.screens = {};
		root.querySelectorAll('[data-screen]').forEach(function (el) {
			this.screens[el.getAttribute('data-screen')] = el;
		}, this);

		this.stage = root.querySelector('[data-stage]');
		this.slice = root.querySelector('[data-slice]'); // אב-טיפוס לשכפול – נשאר נסתר.
		this.slicesEl = root.querySelector('[data-slices]');
		this.obstaclesEl = root.querySelector('[data-obstacles]');
		this.bonusesEl = root.querySelector('[data-bonuses]');
		this.mapEl = root.querySelector('[data-map]');
		this.popupsEl = root.querySelector('[data-popups]');
		this.burstsEl = root.querySelector('[data-bursts]');
		this.comboEl = root.querySelector('[data-combo]');
		this.frenzyEl = root.querySelector('[data-frenzy]');
		this.countdownEl = root.querySelector('[data-countdown]');
		this.countdownNum = root.querySelector('[data-countdown-num]');
		this.timerCard = root.querySelector('[data-timer-card]');
		this.progressEl = root.querySelector('[data-progress]');
		this.confettiEl = root.querySelector('[data-confetti]');
		this.medalEl = root.querySelector('[data-medal]');
		this.boardEl = root.querySelector('[data-leaderboard]');
		this.loaderEl = root.querySelector('[data-loader]');
		this.form = root.querySelector('.phsg-form');
		this.formErrorEl = root.querySelector('[data-form-error]');
		this.soundBtn = root.querySelector('[data-action="toggle-sound"]');
		this.soundLabel = root.querySelector('[data-sound-label]');
		this.bootFill = root.querySelector('[data-boot-fill]');
		this.bootPct = root.querySelector('[data-boot-pct]');

		// אבות-טיפוס של SVG למכשולים/בונוסים.
		this.protos = {};
		root.querySelectorAll('[data-proto]').forEach(function (el) {
			this.protos[el.getAttribute('data-proto')] = el.innerHTML;
		}, this);

		// מצב.
		this.muted = false;
		try {
			this.muted = window.localStorage.getItem('phsg_muted') === '1';
		} catch (e) { /* מתעלמים */ }

		this.participant = null;
		this.utm = this._captureUtm();
		this.token = '';
		this._resetGameState();
		this._syncSound();
		this._bind();
		this._initTilt();
		this._boot();
	}

	/* מסך טעינה בסגנון משחק – מתקדם מהר ואז נפתח האינטרו. */
	Game.prototype._boot = function () {
		var self = this;
		if (!this.screens.boot || !this.bootFill) {
			return;
		}
		var p = 0;
		var iv = setInterval(function () {
			p = Math.min(100, p + 3 + Math.random() * 9);
			self.bootFill.style.width = p + '%';
			if (self.bootPct) {
				self.bootPct.textContent = Math.round(p) + '%';
			}
			if (p >= 100) {
				clearInterval(iv);
				setTimeout(function () {
					self._show('intro');
				}, 420);
			}
		}, 110);
	};

	/* אפקט הטיה תלת-ממדית לכרטיסים במעבר עכבר (דסקטופ בלבד). */
	Game.prototype._initTilt = function () {
		if (window.matchMedia && window.matchMedia('(hover: none)').matches) {
			return;
		}
		this.root.querySelectorAll('.phsg-legend__card, .phsg-stats__card, .phsg-coupon-card, .phsg-howto, .phsg-promo').forEach(function (card) {
			card.addEventListener('mousemove', function (e) {
				var r = card.getBoundingClientRect();
				var rx = ((e.clientY - r.top) / r.height - 0.5) * -9;
				var ry = ((e.clientX - r.left) / r.width - 0.5) * 9;
				card.style.transform = 'perspective(700px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateY(-4px)';
			});
			card.addEventListener('mouseleave', function () {
				card.style.transform = '';
			});
		});
	};

	Game.prototype._resetGameState = function () {
		this.score = 0;
		this.timeLeft = this.dur;
		this.streak = 0;
		this.bestStreak = 0;
		this.reactions = [];
		this.clicks = 0;
		this.frenzyUntil = 0;
		this.sliceSpawn = 0;
		this.sliceDeadline = 0;
		this.cheese = null;
		this.cheeseSpawn = 0;
		this.cheeseUntil = 0;
		this.startedAt = 0;
		this.realStart = 0;
		this.levelIdx = 0;
		this.levelCatches = 0;
		this.levelStrikes = 0;
		clearInterval(this.loop);
		clearInterval(this.cd);
		clearInterval(this.countUpTimer);
		clearInterval(this.mapTimer);
	};

	/* ==================== צלילים (WebAudio – מהאב-טיפוס) ==================== */

	Game.prototype._ac = function () {
		if (!this.audio) {
			var AC = window.AudioContext || window.webkitAudioContext;
			if (!AC) {
				return null;
			}
			try {
				this.audio = new AC();
			} catch (e) {
				return null;
			}
		}
		if (this.audio.state === 'suspended') {
			this.audio.resume();
		}
		return this.audio;
	};

	Game.prototype._tone = function (freq, dur, type, vol, slide) {
		if (this.muted) {
			return;
		}
		try {
			var ac = this._ac();
			if (!ac) {
				return;
			}
			var o = ac.createOscillator();
			var g = ac.createGain();
			o.type = type || 'sine';
			o.frequency.value = freq;
			if (slide) {
				o.frequency.exponentialRampToValueAtTime(slide, ac.currentTime + dur);
			}
			g.gain.setValueAtTime(vol || 0.15, ac.currentTime);
			g.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + dur);
			o.connect(g);
			g.connect(ac.destination);
			o.start();
			o.stop(ac.currentTime + dur);
		} catch (e) { /* מתעלמים */ }
	};

	Game.prototype.playPop = function (streak) {
		var base = 420 + Math.min(8, streak || 0) * 40;
		this._tone(base, 0.12, 'triangle', 0.22, base * 2.2);
		this._tone(base * 1.5, 0.08, 'sine', 0.1, base * 2.8);
	};
	Game.prototype.playGold = function () {
		var self = this;
		[660, 880, 1180].forEach(function (f, i) {
			window.setTimeout(function () { self._tone(f, 0.14, 'triangle', 0.2); }, i * 70);
		});
	};
	Game.prototype.playBad = function () { this._tone(190, 0.28, 'sawtooth', 0.14, 70); };
	Game.prototype.playTick = function () { this._tone(950, 0.05, 'square', 0.06); };
	Game.prototype.playGo = function () { this._tone(520, 0.2, 'triangle', 0.2, 1040); };
	Game.prototype.playBonus = function () {
		var self = this;
		[880, 1175, 1568].forEach(function (f, i) {
			window.setTimeout(function () { self._tone(f, 0.1, 'sine', 0.18); }, i * 55);
		});
	};
	Game.prototype.playFrenzy = function () {
		var self = this;
		[440, 554, 659, 880].forEach(function (f, i) {
			window.setTimeout(function () { self._tone(f, 0.12, 'square', 0.09); }, i * 60);
		});
	};
	Game.prototype.playCheese = function () {
		this._tone(700, 0.09, 'sine', 0.16);
		this._tone(1050, 0.12, 'sine', 0.14, 0);
	};

	/* ---------- מוזיקת רקע (לופ מסונתז, מלחיץ בסוף) ---------- */

	Game.prototype.startMusic = function () {
		this.stopMusic();
		this.musicStep = 0;
		this.musicTense = false;
		var self = this;
		// בס + מלודיה קלילה; במצב לחוץ – טמפו מהיר וגובה עולה.
		var BASS = [131, 0, 131, 0, 165, 0, 147, 0, 131, 0, 165, 0, 196, 0, 147, 0];
		var LEAD = [523, 0, 659, 0, 784, 659, 0, 587, 523, 0, 698, 0, 880, 0, 659, 0];
		var TENSE_BASS = [147, 147, 0, 147, 175, 175, 0, 175, 196, 196, 0, 196, 220, 220, 0, 220];
		var step = function () {
			if (!self.muted) {
				var bass = self.musicTense ? TENSE_BASS : BASS;
				var i = self.musicStep % bass.length;
				if (bass[i]) {
					self._tone(bass[i] * (self.musicTense ? 2 : 1), 0.09, 'triangle', self.musicTense ? 0.05 : 0.035);
				}
				if (!self.musicTense && LEAD[i]) {
					self._tone(LEAD[i], 0.07, 'square', 0.018);
				}
			}
			self.musicStep++;
			self.musicTimer = window.setTimeout(step, self.musicTense ? 95 : 150);
		};
		step();
	};

	Game.prototype.stopMusic = function () {
		if (this.musicTimer) {
			window.clearTimeout(this.musicTimer);
			this.musicTimer = null;
		}
	};

	Game.prototype.playFanfare = function () {
		var self = this;
		[523, 659, 784, 1046, 1318].forEach(function (f, i) {
			window.setTimeout(function () { self._tone(f, 0.22, 'triangle', 0.18); }, i * 120);
		});
	};

	/* ==================== חיווט ==================== */

	Game.prototype._bind = function () {
		var self = this;

		this.root.querySelectorAll('[data-action]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				self._onAction(btn.getAttribute('data-action'));
			});
		});

		if (this.form) {
			this.form.addEventListener('submit', function (e) {
				e.preventDefault();
				self._submitForm();
			});
			this.form.querySelectorAll('input').forEach(function (inp) {
				inp.addEventListener('input', function () { self._hideFormError(); });
			});
		}

	};

	Game.prototype._onAction = function (action) {
		switch (action) {
			case 'go-form':
				this._ac();
				this.playTick();
				this._show('form');
				break;
			case 'play-again':
				this._startCountdown();
				break;
			case 'go-home':
				this._clearConfetti();
				this._show('intro');
				break;
			case 'copy-coupon':
				this._copyCoupon();
				break;
			case 'open-branches': {
				var bm = this.root.querySelector('[data-branches-modal]');
				if (bm) { bm.removeAttribute('hidden'); }
				break;
			}
			case 'close-branches': {
				var bmc = this.root.querySelector('[data-branches-modal]');
				if (bmc) { bmc.setAttribute('hidden', ''); }
				break;
			}
			case 'toggle-instructions': {
				var panel = this.root.querySelector('[data-instructions]');
				var btn = this.root.querySelector('[data-action="toggle-instructions"]');
				if (panel) {
					var open = panel.hasAttribute('hidden');
					if (open) { panel.removeAttribute('hidden'); } else { panel.setAttribute('hidden', ''); }
					if (btn) { btn.setAttribute('aria-expanded', open ? 'true' : 'false'); btn.classList.toggle('is-open', open); }
					if (open) {
						var self2 = this;
						window.setTimeout(function () { panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }, 40);
					}
				}
				break;
			}
			case 'toggle-sound':
				this.muted = !this.muted;
				try {
					window.localStorage.setItem('phsg_muted', this.muted ? '1' : '0');
				} catch (e) { /* מתעלמים */ }
				this._syncSound();
				if (!this.muted) {
					this.playTick();
				}
				break;
		}
	};

	/**
	 * העתקת קוד הקופון ללוח עם חיווי "הועתק!".
	 */
	Game.prototype._copyCoupon = function () {
		var btn = this.root.querySelector('[data-action="copy-coupon"]');
		if (!btn) {
			return;
		}
		var code = btn.getAttribute('data-coupon-code') || '';
		var copied = t('copied', 'הועתק!');
		var mark = function () {
			var original = btn.textContent;
			btn.textContent = copied + ' ✓';
			window.setTimeout(function () { btn.textContent = original; }, 1600);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(code).then(mark).catch(function () { /* מתעלמים */ });
		} else {
			mark();
		}
	};

	Game.prototype._syncSound = function () {
		if (this.soundBtn) {
			this.soundBtn.setAttribute('aria-pressed', this.muted ? 'true' : 'false');
		}
		if (this.soundLabel) {
			this.soundLabel.textContent = this.muted ? t('soundOff', 'צליל: כבוי') : t('soundOn', 'צליל: פועל');
		}
	};

	Game.prototype._show = function (name) {
		Object.keys(this.screens).forEach(function (key) {
			var active = key === name;
			this.screens[key].hidden = !active;
			this.screens[key].classList.toggle('is-active', active);
		}, this);
		window.scrollTo(0, 0);
	};

	/* ==================== טופס ==================== */

	Game.prototype._submitForm = function () {
		var f = this.form;
		var name = (f.querySelector('[name="full_name"]').value || '').trim();
		var phone = (f.querySelector('[name="phone"]').value || '').replace(/[-\s()]/g, '');
		var email = (f.querySelector('[name="email"]').value || '').trim();
		var consent = f.querySelector('[name="consent"]').checked;

		// קידומת ישראלית בינלאומית (+972 / 972) מומרת למספר מקומי.
		if (/^\+972\d{8,9}$/.test(phone)) {
			phone = '0' + phone.slice(4);
		} else if (/^972\d{8,9}$/.test(phone)) {
			phone = '0' + phone.slice(3);
		}

		if (name.length < 2) {
			return this._showFormError(t('errName', 'נא להזין שם מלא'));
		}
		// מקומי (0XXXXXXXXX) או בינלאומי (+XXXXXXXXX...).
		if (!/^0\d{8,9}$/.test(phone) && !/^\+\d{7,15}$/.test(phone)) {
			return this._showFormError(t('errPhone', 'מספר טלפון לא תקין'));
		}
		if (!/^\S+@\S+\.\S+$/.test(email)) {
			return this._showFormError(t('errEmail', 'כתובת אימייל לא תקינה'));
		}
		if (!consent) {
			return this._showFormError(t('errConsent', 'יש לאשר את התקנון כדי להשתתף'));
		}

		this._hideFormError();
		this.participant = { full_name: name, phone: phone, email: email, consent: consent };
		this._startCountdown();
	};

	Game.prototype._showFormError = function (msg) {
		if (this.formErrorEl) {
			this.formErrorEl.textContent = msg;
			this.formErrorEl.hidden = false;
			// ריסטרט אנימציית הרעד.
			this.formErrorEl.style.animation = 'none';
			void this.formErrorEl.offsetHeight;
			this.formErrorEl.style.animation = '';
		}
	};

	Game.prototype._hideFormError = function () {
		if (this.formErrorEl) {
			this.formErrorEl.hidden = true;
		}
	};

	/* ==================== זרימת משחק ==================== */

	Game.prototype.level = function () {
		return this.levelIdx;
	};

	Game.prototype._startCountdown = function () {
		var self = this;
		this._clearConfetti();
		this._resetGameState();
		this._requestToken();
		this._show('game');
		this._renderHud();
		this.slicesEl.innerHTML = '';
		this.obstaclesEl.innerHTML = '';
		this._clearStage();
		this.popupsEl.innerHTML = '';
		this.comboEl.hidden = true;
		if (this.frenzyEl) { this.frenzyEl.hidden = true; }
		this.stage.classList.remove('is-frenzy');

		var count = 3;
		this.countdownEl.hidden = false;
		this._setCountdownNum(count);
		this.playTick();

		clearInterval(this.cd);
		this.cd = setInterval(function () {
			if (count <= 1) {
				clearInterval(self.cd);
				self.countdownEl.hidden = true;
				self._begin();
			} else {
				count--;
				self.playTick();
				self._setCountdownNum(count);
			}
		}, 850);
	};

	Game.prototype._setCountdownNum = function (n) {
		this.countdownNum.textContent = n;
		this.countdownNum.style.animation = 'none';
		void this.countdownNum.offsetHeight;
		this.countdownNum.style.animation = '';
	};

	Game.prototype._begin = function () {
		var self = this;
		this.playGo();
		this.realStart = Date.now();
		this.score = 0;
		this.streak = 0;
		this.bestStreak = 0;
		this.reactions = [];
		this.clicks = 0;
		this.levelIdx = 0;
		this.levelCatches = 0;
		this.levelStrikes = 0;
		this.startedAt = Date.now();
		this.timeLeft = levelTime(0);
		this._syncStrikes();
		this._resetSpawnTimers();
		this._panMap();
		clearInterval(this.mapTimer);
		var mapSelf = this;
		this.mapTimer = setInterval(function () { mapSelf._panMap(); }, 3600);
		this._renderHud();
		this.startMusic();

		clearInterval(this.loop);
		this.loop = setInterval(function () {
			var limit = levelTime(self.levelIdx);
			var tLeft = Math.max(0, limit - (Date.now() - self.startedAt) / 1000);
			// טיק בשניות האחרונות של השלב.
			if (Math.ceil(tLeft) !== Math.ceil(self.timeLeft) && tLeft <= 5 && tLeft > 0) {
				self.playTick();
			}
			self.timeLeft = tLeft;
			if (tLeft <= 0) {
				self._endGame('time');
				return;
			}
			self._tick();
			self._renderHud();
		}, 100);
	};

	/**
	 * השלמת מכסת השלב: בונוס, ואז שלב הבא או ניצחון.
	 */
	Game.prototype._completeLevel = function () {
		// בונוס השלמת שלב – גדל עם השלב (עם תקרה).
		var bonus = Math.min(20, (this.levelIdx + 1) * 3);
		this.score += bonus;

		// אין ניצחון – השלבים נמשכים ללא הגבלה עד כישלון.
		this.levelIdx++;
		this.levelCatches = 0;
		this.levelStrikes = 0;
		this._syncStrikes();
		this.startedAt = Date.now();
		this.timeLeft = levelTime(this.levelIdx);
		this._clearStage();
		this._resetSpawnTimers();
		this._panMap();
		this._levelUp(this.levelIdx + 1, bonus);
		this._renderHud();
	};

	/**
	 * עדכון תג הפסילות (פגיעות במכשולים בשלב הנוכחי).
	 */
	Game.prototype._syncStrikes = function () {
		var el = this.root.querySelector('[data-strikes]');
		if (!el) {
			return;
		}
		if (this.levelStrikes <= 0) {
			el.hidden = true;
			return;
		}
		el.textContent = '⚠️ ' + t('strikes', 'מכשולים') + ' ' + this.levelStrikes + '/' + MAX_STRIKES;
		el.classList.toggle('is-hot', this.levelStrikes >= MAX_STRIKES - 1);
		el.hidden = false;
	};

	/* ==================== מנוע ספאון עצמאי ==================== */
	// כל אובייקט חי בפני עצמו – נכנס ונעלם בזמן משלו לפי קצב השלב.
	function maxSlices(lv) { return Math.min(5, 2 + lv); }
	function sliceLife(lv) { return Math.max(1500, 4200 - lv * 430); }
	function sliceGap(lv) { return Math.max(300, 900 - lv * 85); }
	function maxObst(lv) { return Math.min(6, 2 + lv); }
	function obstLife(lv) { return Math.max(1700, 4200 - lv * 360); }
	function obstGap(lv) { return Math.max(420, 1250 - lv * 105); }

	// הזזת המפה לאזור אקראי – יוצר תחושת מעבר בין מיקומים על המפה.
	Game.prototype._panMap = function () {
		if (!this.mapEl) { return; }
		var x = 8 + Math.random() * 84;
		var y = 6 + Math.random() * 88;
		this.mapEl.style.backgroundPosition = x.toFixed(1) + '% ' + y.toFixed(1) + '%';
	};

	Game.prototype._resetSpawnTimers = function () {
		var now = Date.now();
		this.nextSliceAt = now;
		this.nextObstAt = now + 550;
		this.nextBoxAt = now + 4200 + Math.random() * 3500;
		this.nextPinAt = now + 8000 + Math.random() * 6000;
	};

	Game.prototype._clearStage = function () {
		if (this.slicesEl) { this.slicesEl.innerHTML = ''; }
		if (this.obstaclesEl) { this.obstaclesEl.innerHTML = ''; }
		if (this.bonusesEl) { this.bonusesEl.innerHTML = ''; }
	};

	Game.prototype._countBonus = function (kind) {
		if (!this.bonusesEl) { return 0; }
		return this.bonusesEl.querySelectorAll('[data-kind="' + kind + '"]').length;
	};

	// מיקום פנוי בבמה שלא חופף לאובייקטים חיים.
	Game.prototype._freePos = function (spanX, spanY, minGap) {
		var live = [];
		var collect = function (c) {
			if (!c) { return; }
			for (var i = 0; i < c.children.length; i++) {
				live.push({ x: parseFloat(c.children[i].style.left) || 0, y: parseFloat(c.children[i].style.top) || 0 });
			}
		};
		collect(this.slicesEl); collect(this.obstaclesEl); collect(this.bonusesEl);
		var px, py, tries = 0;
		do {
			px = 4 + Math.random() * spanX;
			py = 6 + Math.random() * spanY;
			tries++;
		} while (tries < 26 && live.some(function (q) { return Math.hypot(px - q.x, py - q.y) < minGap; }));
		return { x: px, y: py };
	};

	Game.prototype._expire = function (container, now, isSlice) {
		if (!container) { return; }
		var kids = container.children;
		for (var i = kids.length - 1; i >= 0; i--) {
			var el = kids[i];
			if (el._expire && now > el._expire) {
				if (isSlice) { this.streak = 0; }
				el.remove();
			}
		}
	};

	Game.prototype._tick = function () {
		var now = Date.now();
		var lv = this.level();
		this._expire(this.slicesEl, now, true);
		this._expire(this.obstaclesEl, now, false);
		this._expire(this.bonusesEl, now, false);
		if (now >= this.nextSliceAt && this.slicesEl.children.length < maxSlices(lv)) {
			this._spawnSlice();
			this.nextSliceAt = now + sliceGap(lv) * (0.7 + Math.random() * 0.7);
		}
		if (now >= this.nextObstAt && this.obstaclesEl.children.length < maxObst(lv)) {
			this._spawnObstacle();
			this.nextObstAt = now + obstGap(lv) * (0.7 + Math.random() * 0.7);
		}
		if (now >= this.nextBoxAt && this._countBonus('box') < 2) {
			this._spawnBox();
			this.nextBoxAt = now + 4500 + Math.random() * 4500;
		}
		if (now >= this.nextPinAt && this._countBonus('pin') < 1) {
			this._spawnPin();
			this.nextPinAt = now + 8000 + Math.random() * 7000;
		}
	};

	Game.prototype._spawnSlice = function () {
		var self = this;
		var lv = this.level();
		var base = Math.max(74, 132 - lv * 9);
		var pos = this._freePos(72, 62, 18);
		var gold = Math.random() < 0.1;
		var pizzas = this._pizzas();
		var el = this.slice.cloneNode(true);
		el.removeAttribute('data-slice');
		el.hidden = false;
		el.style.left = pos.x + '%';
		el.style.top = pos.y + '%';
		el.style.width = base + 'px';
		el.style.height = base + 'px';
		var img = el.querySelector('[data-pizza-img]');
		if (img && pizzas.length) { img.src = pizzas[Math.floor(Math.random() * pizzas.length)]; }
		el.setAttribute('data-type', gold ? 'gold' : 'normal');
		el.classList.toggle('is-gold', gold);
		el.querySelectorAll('[data-gold-only]').forEach(function (g) { g.hidden = !gold; });
		if (lv >= 2) {
			el.classList.add('is-drifting');
			var driftBase = Math.max(1.1, 3.4 - lv * 0.45);
			var driftDur = driftBase * (0.75 + Math.random() * 0.6);
			el.style.animationDuration = driftDur.toFixed(2) + 's';
			el.style.animationDelay = '-' + (Math.random() * driftDur).toFixed(2) + 's';
			if (Math.random() < 0.5) { el.style.animationDirection = 'reverse'; }
			var wob = el.querySelector('.phsg-sprite__wobble');
			if (wob) { wob.style.animationDuration = (0.8 + Math.random() * 0.5).toFixed(2) + 's'; }
		}
		el._spawnAt = Date.now();
		el._expire = Date.now() + sliceLife(lv);
		el._gold = gold;
		el.addEventListener('pointerdown', function (e) {
			e.preventDefault(); e.stopPropagation();
			self._hitSlice(e, el);
		});
		this.slicesEl.appendChild(el);
	};

	Game.prototype._spawnObstacle = function () {
		var self = this;
		var lv = this.level();
		var sprites = this._sprites();
		var pos = this._freePos(80, 70, 14);
		var hot = lv >= 1 && Math.random() < 0.28;
		var types = ['onion', 'tomato', 'mushroom'];
		var type = hot ? 'chili' : types[Math.floor(Math.random() * types.length)];
		var el = document.createElement('div');
		el.className = 'phsg-obstacle';
		el.style.left = pos.x + '%';
		el.style.top = pos.y + '%';
		var sz = 54 + Math.round(Math.random() * 16);
		el.style.width = sz + 'px';
		el.style.height = sz + 'px';
		el.style.transform = 'rotate(' + Math.round(Math.random() * 40 - 20) + 'deg)';
		var url = sprites[type];
		if (url) { var im = document.createElement('img'); im.className = 'phsg-obstacle__img'; im.src = url; im.alt = ''; el.appendChild(im); }
		el._pen = hot ? 2 : 1;
		el._expire = Date.now() + obstLife(lv);
		el.addEventListener('pointerdown', function (e) {
			e.preventDefault(); e.stopPropagation();
			self._hitObstacle(e, el);
		});
		this.obstaclesEl.appendChild(el);
	};

	Game.prototype._spawnBox = function () {
		var self = this;
		var pos = this._freePos(78, 66, 16);
		var big = Math.random() < 0.3;
		var sz = big ? 94 + Math.round(Math.random() * 26) : 56 + Math.round(Math.random() * 14);
		var el = document.createElement('div');
		el.className = 'phsg-sprite phsg-sprite--box' + (big ? ' is-big' : '');
		el.setAttribute('data-kind', 'box');
		el.style.left = pos.x + '%';
		el.style.top = pos.y + '%';
		el.style.width = sz + 'px';
		el.style.height = sz + 'px';
		var im = document.createElement('img'); im.src = this._sprites().box || ''; im.alt = ''; el.appendChild(im);
		el._expire = Date.now() + (big ? 3200 : 2600);
		el.addEventListener('pointerdown', function (e) {
			e.preventDefault(); e.stopPropagation();
			self._hitBox(e, el);
		});
		this.bonusesEl.appendChild(el);
	};

	Game.prototype._spawnPin = function () {
		var self = this;
		var pos = this._freePos(78, 64, 18);
		var el = document.createElement('div');
		el.className = 'phsg-sprite phsg-sprite--pin';
		el.setAttribute('data-kind', 'pin');
		el.style.left = pos.x + '%';
		el.style.top = pos.y + '%';
		var im = document.createElement('img'); im.src = this._sprites().pin || ''; im.alt = ''; el.appendChild(im);
		el._expire = Date.now() + 2900;
		el.addEventListener('pointerdown', function (e) {
			e.preventDefault(); e.stopPropagation();
			self._hitPin(e, el);
		});
		this.bonusesEl.appendChild(el);
	};

	/* ==================== רינדור ==================== */

	// מפת ספרייטים אמיתיים (מכשולים/בונוסים) מ-data-sprites של המופע.
	Game.prototype._sprites = function () {
		if (!this._spriteCache) {
			var obj = {};
			var raw = this.root.getAttribute('data-sprites');
			if (raw) { try { obj = JSON.parse(raw); } catch (e) { obj = {}; } }
			if ((!obj || !obj.pin) && CFG.sprites) { obj = CFG.sprites; }
			this._spriteCache = obj || {};
		}
		return this._spriteCache;
	};

	// רשימת תמונות הפיצה: קודם data-pizzas של המופע, אחרת ברירת המחדל שב-PHSG_DATA.
	Game.prototype._pizzas = function () {
		if (!this._pizzaCache) {
			var arr = [];
			var raw = this.root.getAttribute('data-pizzas');
			if (raw) {
				try { arr = JSON.parse(raw); } catch (e) { arr = []; }
			}
			if (!arr.length && CFG.pizzas && CFG.pizzas.length) {
				arr = CFG.pizzas;
			}
			this._pizzaCache = arr;
		}
		return this._pizzaCache;
	};

	Game.prototype._renderHud = function () {
		var secs = Math.ceil(this.timeLeft);
		var timeText = Math.floor(secs / 60) + ':' + String(secs % 60).padStart(2, '0');
		this._setText('[data-hud="score"]', this.score);
		this._setText('[data-hud="time"]', timeText);
		this._setText('[data-hud="level"]', this.level() + 1);
		// התקדמות המכסה בשלב הנוכחי.
		this._setText('[data-hud="target"]', this.levelCatches + '/' + levelTarget(this.levelIdx));

		var danger = this.timeLeft <= 10 && this.timeLeft > 0;
		if (this.timerCard) {
			this.timerCard.classList.toggle('is-danger', danger);
		}
		// המוזיקה נהיית מלחיצה בשעון העצר.
		this.musicTense = danger;
		if (this.progressEl) {
			this.progressEl.style.width = ((this.timeLeft / levelTime(this.levelIdx)) * 100) + '%';
		}
		// תג רצף מ-3 ומעלה.
		if (this.comboEl) {
			if (this.streak >= 3) {
				this.comboEl.textContent = t('streak', 'רצף') + ' ×' + this.streak;
				this.comboEl.hidden = false;
			} else {
				this.comboEl.hidden = true;
			}
		}
	};

	Game.prototype._renderFrenzy = function () {
		if (!this.frenzyEl) { return; }
		var active = Date.now() < this.frenzyUntil;
		this.frenzyEl.hidden = !active;
		this.stage.classList.toggle('is-frenzy', active);
	};

	/* ==================== אירועי משחק ==================== */

	Game.prototype._popup = function (e, text, color) {
		var rect = this.stage.getBoundingClientRect();
		var x = ((e.clientX - rect.left) / rect.width) * 100;
		var y = ((e.clientY - rect.top) / rect.height) * 100;

		var txt = document.createElement('div');
		txt.className = 'phsg-popup';
		txt.style.left = x + '%';
		txt.style.top = y + '%';
		txt.style.color = color;
		txt.textContent = text;

		var ring = document.createElement('div');
		ring.className = 'phsg-popup-ring';
		ring.style.left = x + '%';
		ring.style.top = y + '%';
		ring.style.borderColor = color;

		this.popupsEl.appendChild(txt);
		this.popupsEl.appendChild(ring);
		window.setTimeout(function () {
			txt.remove();
			ring.remove();
		}, 820);
	};

	Game.prototype._hitSlice = function (e, el) {
		if (this.timeLeft <= 0 || !el || el._done) { return; }
		el._done = true;
		var wasGold = el._gold;
		var newStreak = this.streak + 1;
		var bonusPts = (newStreak > 0 && newStreak % 5 === 0) ? 2 : 0;
		var reactionNow = Date.now() - (el._spawnAt || Date.now());
		var speedBonus = reactionNow <= 1200 ? 1 : 0;
		var pts = (wasGold ? 3 : 1) + bonusPts + speedBonus + Math.min(6, this.levelIdx);
		if (wasGold) { this.playGold(); } else { this.playPop(newStreak); }
		this._popup(e, '+' + pts, wasGold ? '#D19A2B' : '#F32735');
		this._vibrate(wasGold ? 35 : 18);
		this.score += pts;
		this.streak = newStreak;
		this.bestStreak = Math.max(this.bestStreak, newStreak);
		this.reactions.push(reactionNow);
		this.clicks += 1;
		this.levelCatches += 1;
		el.remove();
		this._renderHud();
		if (this.levelCatches >= levelTarget(this.levelIdx)) {
			this._completeLevel();
		}
	};

	Game.prototype._hitObstacle = function (e, el) {
		if (this.timeLeft <= 0 || !el || el._done) { return; }
		el._done = true;
		var self = this;
		var pen = el._pen || 1;
		this.playBad();
		this._popup(e, '−' + pen, '#2D2A26');
		this._vibrate(60);
		this.score = Math.max(0, this.score - pen);
		this.streak = 0;
		this.levelStrikes += 1;
		this._syncStrikes();
		el.remove();
		if (this.levelStrikes > MAX_STRIKES) {
			this._endGame('strikes');
			return;
		}
		this.stage.classList.remove('is-shaking');
		void this.stage.offsetHeight;
		this.stage.classList.add('is-shaking');
		window.setTimeout(function () { self.stage.classList.remove('is-shaking'); }, 380);
		this._renderHud();
	};

	Game.prototype._hitBox = function (e, el) {
		if (this.timeLeft <= 0 || !el || el._done) { return; }
		el._done = true;
		this.playCheese();
		this._popup(e, '+3', '#FFC93C');
		this._vibrate(25);
		this.score += 3;
		this.clicks += 1;
		el.remove();
		this._renderHud();
	};

	Game.prototype._hitPin = function (e, el) {
		if (this.timeLeft <= 0 || !el || el._done) { return; }
		el._done = true;
		this.playFrenzy();
		this.playBonus();
		this._burst(e);
		this._popup(e, '+5', '#FF3B30');
		this._vibrate(70);
		this.score += 5;
		this.clicks += 1;
		el.remove();
		this._renderHud();
	};


	// פיצוץ אור + אובייקטים מתפזרים (נקודת פיצה האט).
	Game.prototype._burst = function (e) {
		if (!this.burstsEl) { return; }
		var rect = this.stage.getBoundingClientRect();
		var x = ((e.clientX - rect.left) / rect.width) * 100;
		var y = ((e.clientY - rect.top) / rect.height) * 100;
		var wrap = document.createElement('div');
		wrap.className = 'phsg-burst';
		wrap.style.left = x + '%';
		wrap.style.top = y + '%';
		var flash = document.createElement('span');
		flash.className = 'phsg-burst__flash';
		wrap.appendChild(flash);
		var rays = document.createElement('span');
		rays.className = 'phsg-burst__rays';
		wrap.appendChild(rays);
		var n = 14;
		var pizzas = this._pizzas();
		for (var i = 0; i < n; i++) {
			var pc = document.createElement('span');
			pc.className = 'phsg-burst__pc';
			var ang = (i / n) * Math.PI * 2 + Math.random() * 0.5;
			var dist = 70 + Math.random() * 80;
			pc.style.setProperty('--dx', Math.cos(ang) * dist + 'px');
			pc.style.setProperty('--dy', Math.sin(ang) * dist + 'px');
			pc.style.animationDelay = (Math.random() * 0.08).toFixed(2) + 's';
			if (pizzas.length && i % 2 === 0) {
				var im = document.createElement('img');
				im.src = pizzas[i % pizzas.length];
				im.alt = '';
				pc.appendChild(im);
			} else {
				pc.classList.add('phsg-burst__pc--spark');
			}
			wrap.appendChild(pc);
		}
		this.burstsEl.appendChild(wrap);
		window.setTimeout(function () { wrap.remove(); }, 1000);
	};

	/**
	 * מסך התחלפות שלב – אוברליי מלא על הבמה + פנפרה.
	 *
	 * @param {number} levelNum מספר השלב (1-5).
	 */
	Game.prototype._levelUp = function (levelNum, bonus) {
		this.playGo();
		this.playBonus();
		// שלא יברח משולש בזמן ההכרזה.
		// עיכוב ספאון קצר בזמן הכרזת השלב.
		this.nextSliceAt = Date.now() + 1200;
		this.nextObstAt = Date.now() + 1300;

		var ov = document.createElement('div');
		ov.className = 'phsg-levelflash';
		var num = document.createElement('span');
		num.className = 'phsg-levelflash__num';
		num.textContent = t('level', 'שלב') + ' ' + levelNum + '!';
		var sub = document.createElement('span');
		sub.className = 'phsg-levelflash__sub';
		sub.textContent = (bonus ? '+' + bonus + ' ' + t('levelBonus', 'בונוס שלב') + ' · ' : '') +
			levelTarget(this.levelIdx) + ' ' + t('levelGoal', 'תפיסות ב-') + levelTime(this.levelIdx) + ' ' + t('sec', "שנ'");
		ov.appendChild(num);
		ov.appendChild(sub);
		this.stage.appendChild(ov);
		window.setTimeout(function () { ov.remove(); }, 1150);
	};

	Game.prototype._vibrate = function (ms) {
		if (navigator.vibrate) {
			try {
				navigator.vibrate(ms);
			} catch (e) { /* מתעלמים */ }
		}
	};

	/* ==================== סיום ==================== */

	Game.prototype._endGame = function (reason) {
		this.endReason = reason || 'time';
		clearInterval(this.loop);
		this.stopMusic();
		if (this.endReason === 'strikes') {
			this.playBad();
		} else {
			this.playFanfare();
		}

		var durationSec = (Date.now() - this.realStart) / 1000;
		var avgMs = this.reactions.length
			? this.reactions.reduce(function (a, b) { return a + b; }, 0) / this.reactions.length
			: 0;

		this.slicesEl.innerHTML = '';
		this._clearStage();
		if (this.frenzyEl) { this.frenzyEl.hidden = true; }
		this.stage.classList.remove('is-frenzy');
		this._show('end');

		// כותרת לפי סיבת הסיום; ניקוד גבוה במיוחד = אלופים בכל מקרה.
		var title;
		if (this.score >= 80) {
			title = t('titleChamp', 'אלוף/ת הפיצה!');
		} else if (this.endReason === 'strikes') {
			title = t('titleStrikes', 'יותר מדי מכשולים…');
		} else {
			title = t('titleTime', 'הזמן נגמר!');
		}
		this._setText('[data-end-title]', title);

		// סטטיסטיקות.
		this._setText('[data-result="avg"]', (avgMs / 1000).toFixed(1) + ' ' + t('sec', "שנ'"));
		this._setText('[data-result="streak"]', this.bestStreak);
		this._setText('[data-result="rank"]', '—');
		this._setMedal(0);
		this._countUpScore(this.score);
		this._confetti();

		this._submitScore({
			score: this.score,
			clicks: this.clicks,
			duration: Math.round(durationSec * 100) / 100,
			avg_reaction: Math.round(avgMs * 100) / 100
		});
	};

	Game.prototype._countUpScore = function (target) {
		var el = this.root.querySelector('[data-result="score"]');
		if (!el) {
			return;
		}
		clearInterval(this.countUpTimer);
		var cur = 0;
		el.textContent = '0';
		if (target <= 0) {
			return;
		}
		this.countUpTimer = setInterval(function () {
			cur = Math.min(target, cur + Math.max(1, Math.ceil(target / 30)));
			el.textContent = cur;
			if (cur >= target) {
				clearInterval(this.countUpTimer);
			}
		}.bind(this), 35);
	};

	Game.prototype._confetti = function () {
		if (!this.confettiEl) {
			return;
		}
		var palette = ['#F32735', '#F0EFDD', '#2D2A26', '#FFC93C', '#B58967'];
		this.confettiEl.innerHTML = '';
		for (var i = 0; i < 30; i++) {
			var c = document.createElement('div');
			c.className = 'phsg-confetti__piece';
			c.style.left = (Math.random() * 100) + '%';
			c.style.width = (7 + Math.random() * 8) + 'px';
			c.style.height = (10 + Math.random() * 10) + 'px';
			c.style.borderRadius = (Math.random() < 0.5 ? 999 : 2) + 'px';
			c.style.background = palette[Math.floor(Math.random() * palette.length)];
			c.style.animationDuration = (2.6 + Math.random() * 2.4) + 's';
			c.style.animationDelay = (Math.random() * 2.5) + 's';
			this.confettiEl.appendChild(c);
		}
	};

	Game.prototype._clearConfetti = function () {
		if (this.confettiEl) {
			this.confettiEl.innerHTML = '';
		}
	};

	Game.prototype._setMedal = function (rank) {
		if (!this.medalEl) {
			return;
		}
		var hi = '#FBFAEE';
		var lo = '#DDDBC1';
		if (rank === 1) { hi = '#FFE9A8'; lo = '#F2B33C'; }
		else if (rank === 2) { hi = '#F2F1EA'; lo = '#C9C7BA'; }
		else if (rank === 3) { hi = '#E8B98D'; lo = '#C08552'; }
		this.medalEl.style.background = 'radial-gradient(circle at 35% 30%, ' + hi + ', ' + lo + ' 75%)';
	};

	/* ==================== שרת ==================== */

	/**
	 * בקשת טוקן חד-פעמי להגשה (אנטי-רמייה).
	 */
	Game.prototype._requestToken = function () {
		var self = this;
		this.token = '';
		var body = new URLSearchParams();
		body.append('action', 'phsg_start_game');
		body.append('nonce', CFG.nonce);
		fetch(CFG.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
			credentials: 'same-origin'
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (json && json.success && json.data && json.data.token) {
					self.token = json.data.token;
				}
			})
			.catch(function () { /* ההגשה תיכשל בעדינות בהמשך */ });
	};

	Game.prototype._submitScore = function (result) {
		var self = this;
		this._toggleLoader(true);

		var body = new URLSearchParams();
		body.append('action', 'phsg_submit_score');
		body.append('nonce', CFG.nonce);
		body.append('token', this.token);
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
					var rank = parseInt(json.data.rank, 10) || 0;
					self._setText('[data-result="rank"]', rank ? '#' + rank : '—');
					self._setMedal(rank);
					self._renderBoard(json.data.leaderboard || [], json.data.display_name || '');
				} else {
					var msg = (json && json.data && json.data.message) || t('saveError', 'אירעה שגיאה בשמירה. נסו שוב.');
					self._boardError(msg);
				}
			})
			.catch(function () {
				self._toggleLoader(false);
				self._boardError(t('saveError', 'אירעה שגיאה בשמירה. נסו שוב.'));
			});
	};

	Game.prototype._renderBoard = function (rows, meName) {
		if (!this.boardEl) {
			return;
		}
		var medals = ['#FFC93C', '#D6D4C8', '#D19A6A'];
		var html = '';
		var meMarked = false;

		rows.slice(0, 8).forEach(function (row, i) {
			var isMe = !meMarked && meName && row.display_name === meName;
			if (isMe) {
				meMarked = true;
			}
			var rankBg = i < 3 ? medals[i] : '#FBFAEE';
			var rowBg = isMe ? '' : (i % 2 ? '#231C22' : '#1D171C');
			var name = esc(row.display_name) + (isMe ? ' ' + t('youSuffix', '(את/ה!)') : '');
			var avg = ((parseFloat(row.avg_reaction) || 0) / 1000).toFixed(1) + ' ' + t('sec', "שנ'");

			html += '<div class="phsg-board__row' + (isMe ? ' is-me' : '') + '"' + (rowBg ? ' style="background:' + rowBg + ';"' : '') + '>' +
				'<span class="phsg-board__rank" style="background:' + rankBg + ';">' + (i + 1) + '</span>' +
				'<span class="phsg-board__name">' + name + '</span>' +
				'<span class="phsg-board__score">' + esc(row.score) + '</span>' +
				'<span class="phsg-board__avg">' + esc(avg) + '</span>' +
				'</div>';
		});

		if (html) {
			this.boardEl.innerHTML = html;
		}
	};

	Game.prototype._boardError = function (msg) {
		if (this.boardEl) {
			this.boardEl.insertAdjacentHTML(
				'afterbegin',
				'<div class="phsg-board__row"><span class="phsg-board__empty">' + esc(msg) + '</span></div>'
			);
		}
	};

	Game.prototype._toggleLoader = function (show) {
		if (this.loaderEl) {
			this.loaderEl.hidden = !show;
		}
	};

	Game.prototype._setText = function (selector, value) {
		var el = this.root.querySelector(selector);
		if (el) {
			el.textContent = value;
		}
	};

	/* ==================== UTM ==================== */

	Game.prototype._captureUtm = function () {
		var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
		var out = {};
		var params = null;
		try {
			params = new URLSearchParams(window.location.search);
		} catch (e) { /* מתעלמים */ }

		keys.forEach(function (k) {
			var val = '';
			if (params && params.get(k)) {
				val = params.get(k);
			} else {
				try {
					val = window.sessionStorage.getItem('phsg_' + k) || '';
				} catch (e2) { /* מתעלמים */ }
			}
			out[k] = (val || '').substring(0, 120);
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
	 * השתלטות על העמוד: העברת המשחק ישירות ל-body והסתרת כל שאר התוכן
	 * (header/footer/כותרת של התבנית). עמיד בפני כל תבנית וורדפרס.
	 *
	 * @param {HTMLElement} root שורש האפליקציה.
	 */
	function takeover(root) {
		if (root.getAttribute('data-fullscreen') !== '1') {
			return;
		}
		// בתבנית הנחיתה של התוסף העמוד כבר נקי.
		if (document.body.classList.contains('phsg-landing-body')) {
			return;
		}
		if (root.closest('.phsg-takeover-host')) {
			return;
		}
		var host = document.createElement('div');
		host.className = 'phsg-takeover-host';
		document.body.appendChild(host);
		host.appendChild(root);
		document.documentElement.classList.add('phsg-takeover');
		window.scrollTo(0, 0);
	}

	function init() {
		document.querySelectorAll('.phsg-app').forEach(function (root) {
			takeover(root);
			new Game(root);
		});
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
