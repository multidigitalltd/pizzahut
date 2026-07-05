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
		this.bonusEl = root.querySelector('[data-bonus]');
		this.cheeseEl = root.querySelector('[data-cheese]');
		this.popupsEl = root.querySelector('[data-popups]');
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
	}

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
		this.lastLevel = 0;
		clearInterval(this.loop);
		clearInterval(this.cd);
		clearInterval(this.countUpTimer);
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

		if (this.bonusEl) {
			this.bonusEl.addEventListener('pointerdown', function (e) {
				e.preventDefault();
				e.stopPropagation();
				self._hitBonus(e);
			});
		}

		if (this.cheeseEl) {
			this.cheeseEl.addEventListener('pointerdown', function (e) {
				e.preventDefault();
				e.stopPropagation();
				self._hitCheese(e);
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
			case 'promo-scroll': {
				// גלילה חלקה לכפתור "מתחילים" – עם חסימה כדי לא לגלוש מעבר לסוף הדף.
				var cta = this.root.querySelector('.phsg-cta--xl');
				if (cta) {
					var rect = cta.getBoundingClientRect();
					var target = window.pageYOffset + rect.top - (window.innerHeight - rect.height) / 2;
					var max = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
					window.scrollTo({ top: Math.max(0, Math.min(target, max)), behavior: 'smooth' });
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
		var elapsed = this.dur - this.timeLeft;
		return Math.min(4, Math.floor(elapsed / (this.dur / 5)));
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
		this.bonusEl.hidden = true;
		this.popupsEl.innerHTML = '';
		this.comboEl.hidden = true;
		this.frenzyEl.hidden = true;
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
		this.startedAt = Date.now();
		this.realStart = Date.now();
		this.score = 0;
		this.streak = 0;
		this.bestStreak = 0;
		this.reactions = [];
		this.clicks = 0;
		this.timeLeft = this.dur;
		this._spawnSlice();
		this._renderHud();
		this.startMusic();

		clearInterval(this.loop);
		this.loop = setInterval(function () {
			var tLeft = Math.max(0, self.dur - (Date.now() - self.startedAt) / 1000);
			// טיק בשניות האחרונות.
			if (Math.ceil(tLeft) !== Math.ceil(self.timeLeft) && tLeft <= 5 && tLeft > 0) {
				self.playTick();
			}
			self.timeLeft = tLeft;
			if (tLeft <= 0) {
				self._endGame();
				return;
			}
			// בריחת המשולש אם לא נתפס בזמן.
			if (Date.now() > self.sliceDeadline) {
				self.streak = 0;
				self._spawnSlice();
			}
			// נתח הגבינה נעלם אם לא נתפס בזמן.
			if (self.cheese && Date.now() > self.cheeseUntil) {
				self._renderCheese(null);
			}
			// חיווי מעבר שלב.
			var lv = self.level();
			if (lv !== self.lastLevel) {
				self.lastLevel = lv;
				self._levelUp(lv + 1);
			}
			self._renderHud();
			self._renderFrenzy();
		}, 100);
	};

	/**
	 * מיקום משולש חדש + מכשולים + בונוס (אלגוריתם האב-טיפוס).
	 */
	// כמות משולשים בו-זמנית לפי שלב (1-5): יותר שלבים = יותר משולשים.
	var SLICES_BY_LEVEL = [1, 1, 2, 2, 3];

	Game.prototype._spawnSlice = function () {
		var lv = this.level();

		// הגרלת מיקומי המשולשים – שומרים מרחק ביניהם.
		var slices = [];
		var n = SLICES_BY_LEVEL[lv] || 1;
		for (var k = 0; k < n; k++) {
			var sx;
			var sy;
			var st = 0;
			do {
				sx = 5 + Math.random() * 70;
				sy = 8 + Math.random() * 60;
				st++;
			} while (st < 25 && slices.some(function (p) { return Math.hypot(sx - p.x, sy - p.y) < 20; }));
			slices.push({ x: sx, y: sy, gold: Math.random() < 0.1 });
		}
		var x = slices[0].x;
		var y = slices[0].y;

		// מכשולים: 2 + שלב (עד 7).
		var count = Math.min(7, 2 + lv);
		var types = ['mush', 'olive', 'onion', 'tomato'];
		var obstacles = [];
		var i;
		for (i = 0; i < count; i++) {
			var ox;
			var oy;
			var tries = 0;
			// 35% מהמכשולים "אגרסיביים" – מותר להם להתקרב הרבה יותר למשולש.
			var minDist = Math.random() < 0.35 ? 12 : 22;
			do {
				ox = 4 + Math.random() * 80;
				oy = 6 + Math.random() * 70;
				tries++;
			} while (tries < 25 && (slices.some(function (p) { return Math.hypot(ox - p.x, oy - p.y) < minDist; }) || obstacles.some(function (o) { return Math.hypot(ox - o.x, oy - o.y) < 13; })));
			var burnt = lv >= 1 && i === 0 && Math.random() < 0.45;
			var type = burnt ? 'burnt' : types[Math.floor(Math.random() * types.length)];
			obstacles.push({
				x: ox,
				y: oy,
				rot: Math.round(Math.random() * 44 - 22),
				s: burnt ? 78 + Math.round(Math.random() * 10) : 50 + Math.round(Math.random() * 14),
				pen: burnt ? 2 : 1,
				type: type
			});
		}

		// בונוס (18%): שעון או פלפל.
		var bonus = null;
		if (Math.random() < 0.18) {
			var bx;
			var by;
			var btries = 0;
			do {
				bx = 6 + Math.random() * 80;
				by = 8 + Math.random() * 68;
				btries++;
			} while (btries < 25 && (Math.hypot(bx - x, by - y) < 20 || obstacles.some(function (o) { return Math.hypot(bx - o.x, by - o.y) < 13; })));
			bonus = { x: bx, y: by, type: Math.random() < 0.5 ? 'clock' : 'chili' };
		}

		// נתח גבינה (22%): פריט מהיר +2 שנעלם תוך ~1.8 שניות.
		var cheese = null;
		if (Math.random() < 0.22) {
			var cx;
			var cy;
			var ctries = 0;
			do {
				cx = 6 + Math.random() * 80;
				cy = 8 + Math.random() * 68;
				ctries++;
			} while (ctries < 25 && (Math.hypot(cx - x, cy - y) < 18 || obstacles.some(function (o) { return Math.hypot(cx - o.x, cy - o.y) < 12; })));
			cheese = { x: cx, y: cy };
		}

		this.sliceSpawn = Date.now();
		// שהות המשולש: 5 שניות בתחילת המשחק, ומתקצרת ככל שהזמן אוזל (עד 1.1 שנ').
		this.sliceDeadline = Date.now() + Math.max(1100, 5000 - lv * 975);

		this._renderSlices(slices, lv);
		this._renderObstacles(obstacles);
		this._renderBonus(bonus);
		this._renderCheese(cheese);
	};

	/* ==================== רינדור ==================== */

	Game.prototype._renderSlices = function (list, lv) {
		var self = this;
		var base = 116 - lv * 10;
		this.slicesEl.innerHTML = '';

		list.forEach(function (sl) {
			var el = self.slice.cloneNode(true);
			el.removeAttribute('data-slice');
			el.hidden = false;
			el.style.left = sl.x + '%';
			el.style.top = sl.y + '%';
			el.style.width = base + 'px';
			el.style.height = Math.round(base * 1.1) + 'px';
			el.setAttribute('data-type', sl.gold ? 'gold' : 'normal');
			el.classList.toggle('is-gold', sl.gold);
			el.querySelectorAll('[data-gold-only]').forEach(function (g) {
				g.hidden = !sl.gold;
			});
			// תנועת ריחוף משלב 3.
			if (lv >= 2) {
				el.classList.add('is-drifting');
				el.style.animationDuration = (3.4 - lv * 0.45).toFixed(2) + 's';
			}
			el.addEventListener('pointerdown', function (e) {
				e.preventDefault();
				e.stopPropagation();
				self._hitSlice(e, sl.gold);
			});
			self.slicesEl.appendChild(el);
		});
	};

	Game.prototype._renderObstacles = function (list) {
		var self = this;
		this.obstaclesEl.innerHTML = '';
		list.forEach(function (ob) {
			var el = document.createElement('div');
			el.className = 'phsg-obstacle';
			el.style.left = ob.x + '%';
			el.style.top = ob.y + '%';
			el.style.width = ob.s + 'px';
			el.style.height = ob.s + 'px';
			el.style.transform = 'rotate(' + ob.rot + 'deg)';
			el.setAttribute('data-pen', ob.pen);
			el.innerHTML = self.protos[ob.type] || '';
			el.addEventListener('pointerdown', function (e) {
				e.preventDefault();
				e.stopPropagation();
				self._hitObstacle(e, ob.pen);
			});
			self.obstaclesEl.appendChild(el);
		});
	};

	Game.prototype._renderCheese = function (cheese) {
		this.cheese = cheese;
		if (!this.cheeseEl) {
			return;
		}
		if (!cheese) {
			this.cheeseEl.hidden = true;
			return;
		}
		this.cheeseEl.style.left = cheese.x + '%';
		this.cheeseEl.style.top = cheese.y + '%';
		this.cheeseSpawn = Date.now();
		this.cheeseUntil = Date.now() + 1800; // חלון תפיסה קצר – צריך להספיק!
		this.cheeseEl.hidden = false;
	};

	Game.prototype._hitCheese = function (e) {
		if (!this.cheese || this.timeLeft <= 0) {
			return;
		}
		this.playCheese();
		this._popup(e, '+2', '#D19A2B');
		this._vibrate(25);
		this.score += 2;
		this.clicks += 1;
		// זמן תגובה נמדד רק על תפיסות משולש (מדד הדירוג) – לא על גבינה.
		this._renderCheese(null);
		this._renderHud();
	};

	Game.prototype._renderBonus = function (bonus) {
		this.bonus = bonus;
		if (!bonus) {
			this.bonusEl.hidden = true;
			return;
		}
		this.bonusEl.style.left = bonus.x + '%';
		this.bonusEl.style.top = bonus.y + '%';
		this.bonusEl.innerHTML = this.protos[bonus.type] || '';
		this.bonusEl.hidden = false;
	};

	Game.prototype._renderHud = function () {
		var secs = Math.ceil(this.timeLeft);
		var timeText = Math.floor(secs / 60) + ':' + String(secs % 60).padStart(2, '0');
		this._setText('[data-hud="score"]', this.score);
		this._setText('[data-hud="time"]', timeText);
		this._setText('[data-hud="level"]', this.level() + 1);

		var danger = this.timeLeft <= 10 && this.timeLeft > 0;
		if (this.timerCard) {
			this.timerCard.classList.toggle('is-danger', danger);
		}
		// המוזיקה נהיית מלחיצה בשעון העצר.
		this.musicTense = danger;
		if (this.progressEl) {
			this.progressEl.style.width = ((this.timeLeft / this.dur) * 100) + '%';
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

	Game.prototype._hitSlice = function (e, wasGold) {
		if (this.timeLeft <= 0 || !this.slicesEl.children.length) {
			return;
		}
		var frenzy = Date.now() < this.frenzyUntil;
		var mult = frenzy ? 2 : 1;
		var newStreak = this.streak + 1;
		var bonusPts = (newStreak > 0 && newStreak % 5 === 0) ? 2 : 0;
		var pts = (wasGold ? 3 : 1) * mult + bonusPts;

		if (wasGold) {
			this.playGold();
		} else {
			this.playPop(newStreak);
		}
		this._popup(e, '+' + pts, wasGold ? '#D19A2B' : (frenzy ? '#D01423' : '#F32735'));
		this._vibrate(wasGold ? 35 : 18);

		this.score += pts;
		this.streak = newStreak;
		this.bestStreak = Math.max(this.bestStreak, newStreak);
		this.reactions.push(Date.now() - this.sliceSpawn);
		this.clicks += 1;

		this._renderHud();
		this._spawnSlice();
	};

	Game.prototype._hitObstacle = function (e, pen) {
		if (this.timeLeft <= 0) {
			return;
		}
		var self = this;
		this.playBad();
		this._popup(e, '−' + pen, '#2D2A26');
		this._vibrate(60);
		this.score = Math.max(0, this.score - pen);
		this.streak = 0;
		this.stage.classList.remove('is-shaking');
		void this.stage.offsetHeight;
		this.stage.classList.add('is-shaking');
		window.setTimeout(function () {
			self.stage.classList.remove('is-shaking');
		}, 380);
		this._renderHud();
	};

	Game.prototype._hitBonus = function (e) {
		if (!this.bonus || this.timeLeft <= 0) {
			return;
		}
		var b = this.bonus;
		if (b.type === 'clock') {
			this.playBonus();
			this._popup(e, '+5 ' + t('sec', "שנ'"), '#D19A2B');
			// הזמן נגזר מ-startedAt – הזזה קדימה מוסיפה 5 שנ', עם תקרה במשך המלא.
			this.startedAt = Math.min(Date.now(), this.startedAt + 5000);
		} else {
			this.playFrenzy();
			this._popup(e, '×2!', '#D01423');
			this.frenzyUntil = Date.now() + 6000;
		}
		this._renderBonus(null);
		this._renderHud();
		this._renderFrenzy();
	};

	/**
	 * מסך התחלפות שלב – אוברליי מלא על הבמה + פנפרה.
	 *
	 * @param {number} levelNum מספר השלב (1-5).
	 */
	Game.prototype._levelUp = function (levelNum) {
		this.playGo();
		this.playBonus();
		// שלא יברח משולש בזמן ההכרזה.
		this.sliceDeadline += 1100;

		var ov = document.createElement('div');
		ov.className = 'phsg-levelflash';
		var num = document.createElement('span');
		num.className = 'phsg-levelflash__num';
		num.textContent = t('level', 'שלב') + ' ' + levelNum + '!';
		var sub = document.createElement('span');
		sub.className = 'phsg-levelflash__sub';
		sub.textContent = t('levelUpSub', 'מהר יותר… קשה יותר!');
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

	Game.prototype._endGame = function () {
		clearInterval(this.loop);
		this.stopMusic();
		this.playFanfare();

		var durationSec = (Date.now() - this.realStart) / 1000;
		var avgMs = this.reactions.length
			? this.reactions.reduce(function (a, b) { return a + b; }, 0) / this.reactions.length
			: 0;

		this.slicesEl.innerHTML = '';
		this.bonusEl.hidden = true;
		this.frenzyEl.hidden = true;
		this.stage.classList.remove('is-frenzy');
		this._show('end');

		// כותרת לפי ניקוד.
		var title = this.score >= 25
			? t('titleChamp', 'אלוף/ת הפיצה!')
			: (this.score >= 12 ? t('titleGood', 'כל הכבוד!') : t('titleMeh', 'לא רע… עוד סיבוב?'));
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
			var rowBg = isMe ? '' : (i % 2 ? '#E9E7D2' : '#F0EFDD');
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
