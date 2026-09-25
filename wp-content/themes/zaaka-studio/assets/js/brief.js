/* Zaaka — the Brief Builder's upgrade layer.
   Nothing here is load-bearing. The form rendered by inc/brief-form.php is a
   complete, submittable form on its own: every step visible, every control a
   real control. This file hides all but one step, swaps two selects for
   sliders, turns the scope selects into chips, and composes the brief panel.
   If it fails to load, the visitor gets a long form that works. */
(function () {
	"use strict";

	var form = document.querySelector(".zk-brief-form");
	if (!form) return;

	var root = document.documentElement;
	var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	var steps = Array.prototype.slice.call(form.querySelectorAll(".zk-brief-step"));
	if (steps.length < 2) return;

	root.classList.add("zk-brief-js");

	var current = 1;
	var seen = {};
	var route = null;
	var bar = document.querySelector(".zk-progress__bar");
	var nav = {
		back: form.querySelector('[data-nav="back"]'),
		next: form.querySelector('[data-nav="next"]'),
		submit: form.querySelector('[data-nav="submit"]'),
		skip: form.querySelector('[data-nav="skip"]')
	};

	/* Steps a route skips outright — the third of the form nobody sees. */
	var ROUTE_PHRASE = {
		product: "planning a new product or platform",
		site: "planning a site that sells something that already exists",
		rescue: "dealing with something already built that has stopped keeping up",
		integrate: "connecting systems that don't talk to each other",
		unsure: "still working out what they need"
	};

	/* ---------------------------------------------------------- the panel -- */
	/* The brief starts blank and gains a line each time one can honestly be
	   written. It used to render the whole skeleton up front with placeholders —
	   "the problem hasn't been described yet", "an audience we don't know yet" —
	   which described absence in the voice of a form validator, and made the
	   thing feel like a machine waiting to be fed. A blank page that fills is
	   friendlier and more honest: nothing is claimed before it is known.

	   It is also why the first question is now a name. Every line is written
	   about a person, and "Thandi is planning a new product" reads like someone
	   listening; "Someone is planning a new product" reads like a database. */

	var answers = {};

	function firstName(n) { return (n || "").trim().split(/\s+/)[0] || ""; }
	function lower(s) { return s ? s.charAt(0).toLowerCase() + s.slice(1) : s; }
	function cap(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }
	function t(x) { return { kind: "text", s: x }; }
	function v(x) { return { kind: "val", s: x }; }

	/* Each line needs only its first key; everything else folds into the same
	   sentence when it arrives. Grouping them this way is the point — a line per
	   answer reads as a receipt of what was clicked, whereas "It is for your
	   customers, about 400 of them, and it earns through a subscription" reads
	   as someone repeating your project back to you. */
	var LINES = [
		{ need: ["route"], build: function (a) {
			var o = [t((firstName(a.name) || "Someone") + " is ")];
			if (a.buildtype) {
				/* The form is more specific than the route, and saying both
				   gives you "building a SaaS product — a new product or
				   platform", which is the same fact twice. */
				o.push(t("building "), v(a.buildtype));
				if (a.industry) { o.push(t(" for "), v(lower(a.industry))); }
			} else {
				o.push(v(a.route));
			}
			return o.concat(t(".")); } },

		{ need: ["problem"], build: function (a) {
			var o = [t("Right now, "), v(lower(a.problem)), t(".")];
			if (a.donothing) { o.push(t(" Left twelve months, "), v(lower(a.donothing)), t(".")); }
			return o; } },

		{ need: ["audience"], build: function (a) {
			var o = [t("It is for "), v(a.audience)];
			if (a.count) { o.push(t(", about "), v(a.count), t(" of them")); }
			if (a.money)  { o.push(t(", and it earns through "), v(a.money)); }
			o.push(t("."));
			if (a.metric) { o.push(t(" Success looks like "), v(lower(a.metric)), t(".")); }
			return o; } },

		{ need: ["money"], skipIf: "audience", build: function (a) {
			var o = [t("It earns through "), v(a.money), t(".")];
			if (a.metric) { o.push(t(" Success looks like "), v(lower(a.metric)), t(".")); }
			return o; } },

		{ need: ["url"], build: function (a) {
			var o = [t("There is already "), v(a.url)];
			if (a.owner) { o.push(t(", and "), v(a.owner)); }
			return o.concat(t(".")); } },

		{ need: ["scope"], build: function (a) {
			return [t("The first release needs "), v(a.scope), t(".")]; } },

		{ need: ["timeline"], build: function (a) {
			var o = [t("You want it live in "), v(a.timeline)];
			if (a.datereal) { o.push(t(", and "), v(lower(a.datereal))); }
			o.push(t("."));
			if (a.budget) { o.push(t(" Budget is "), v(a.budget), t(".")); }
			return o; } },

		{ need: ["budget"], skipIf: "timeline", build: function (a) {
			return [t("Budget is "), v(a.budget), t(".")]; } },

		{ need: ["decider"], build: function (a) {
			var o = [v(cap(a.decider))];
			if (a.clienttime) { o.push(t(", with "), v(a.clienttime), t(" from your side")); }
			return o.concat(t(".")); } }
	];

	var panel = document.querySelector(".zk-brief-panel");
	panel.hidden = false;
	panel.innerHTML =
		'<div class="zk-brief__head"><h2 class="zk-brief__title">Your brief</h2></div>' +
		'<div class="zk-brief__body"><p class="zk-brief__empty"></p></div>' +
		'<div class="zk-stamp" hidden>Brief complete</div>';

	var body   = panel.querySelector(".zk-brief__body");
	var empty  = panel.querySelector(".zk-brief__empty");
	var stamp  = panel.querySelector(".zk-stamp");
	var lineEl = [];

	empty.textContent = "Blank for now. It fills in as you answer.";

	/* Type the sentence as plain text, then swap in the marked-up version once
	   it is complete. The two are character-identical, so the swap is invisible
	   — it just lets the values keep their own colour without typing tags. */
	/* Every paint claims the element. A line that is still typing when a later
	   answer extends it — "Right now, X." becoming "Right now, X. Left twelve
	   months, Y." — must stop, or its loop finishes writing the stale sentence
	   over the new one a few frames later. */
	var paintSeq = 0;

	function paint(el, parts, animate) {
		var plain = parts.map(function (p) { return p.s; }).join("");
		var mine  = ++paintSeq;
		el.dataset.paint = mine;

		function markup() {
			el.textContent = "";
			parts.forEach(function (p) {
				if (p.kind === "text") { el.appendChild(document.createTextNode(p.s)); return; }
				var span = document.createElement("span");
				span.className = "zk-brief__val" + (animate && !reduce ? " is-writing" : "");
				span.textContent = p.s;
				el.appendChild(span);
			});
			if (animate && !reduce) {
				window.setTimeout(function () {
					Array.prototype.forEach.call(el.querySelectorAll(".is-writing"), function (sp) {
						sp.classList.remove("is-writing");
					});
				}, 420);
			}
		}

		if (!animate || reduce) { markup(); return; }

		var i = 0;
		var speed = plain.length > 60 ? 8 : 14;
		(function tick() {
			if (+el.dataset.paint !== mine) { return; }   // superseded
			el.textContent = plain.slice(0, ++i);
			if (i < plain.length) { window.setTimeout(tick, speed); } else { markup(); }
		})();
	}

	function renderLines() {
		LINES.forEach(function (line, i) {
			var ready = line.need.every(function (k) { return answers[k]; });
			if (line.skipIf && answers[line.skipIf]) { ready = false; }
			if (!ready) {
				if (lineEl[i]) { lineEl[i].el.remove(); lineEl[i] = null; }
				return;
			}

			var parts = line.build(answers);
			var plain = parts.map(function (p) { return p.s; }).join("");
			var have  = lineEl[i];

			if (have && have.text === plain) { return; }
			if (have) { have.text = plain; paint(have.el, parts, true); return; }

			var el = document.createElement("p");
			el.className = "zk-brief__line";

			/* Keep document order even when an earlier line is answered later —
			   going back and filling in a skipped step must not append to the end. */
			var after = null;
			for (var j = i + 1; j < LINES.length; j++) {
				if (lineEl[j]) { after = lineEl[j].el; break; }
			}
			body.insertBefore(el, after);
			lineEl[i] = { el: el, text: plain };

			if (empty && empty.parentNode) { empty.parentNode.removeChild(empty); }
			paint(el, parts, true);
		});
	}

	function record(key, value) {
		if (!value || answers[key] === value) { return; }
		answers[key] = value;
		renderLines();
	}

	/* ------------------------------------------------------------ stepping -- */

	function visible() {
		return steps.filter(function (s) {
			var skip = (s.dataset.routeSkip || "").split(/\s+/);
			return !route || skip.indexOf(route) === -1;
		});
	}

	function show(n) {
		var list = visible();
		var target = list.filter(function (s) { return +s.dataset.step >= n; })[0] || list[list.length - 1];
		var prev = steps.filter(function (s) { return !s.hidden; })[0];
		current = +target.dataset.step;

		function swap() {
			steps.forEach(function (s) { s.hidden = true; s.classList.remove("is-leaving"); });
			target.hidden = false;
			seen[+target.dataset.step] = true;

			var last = list.indexOf(target) === list.length - 1;
			nav.back.hidden = list.indexOf(target) === 0;
			nav.next.hidden = last;
			nav.skip.hidden = last;
			nav.submit.hidden = !last;

			var focusable = target.querySelector("textarea, input:not([type=hidden]):not(.zk-sr), select, .zk-route");
			if (focusable && focusable.focus) { try { focusable.focus({ preventScroll: true }); } catch (e) {} }
			if (bar) {
				var at = list.indexOf(target);
				bar.style.width = ( at / Math.max( list.length - 1, 1 ) * 100 ) + "%";
			}
		}

		if (prev && prev !== target && !reduce) {
			prev.classList.add("is-leaving");
			window.setTimeout(swap, 200);
		} else { swap(); }
	}

	function step(dir) {
		var list = visible();
		var here = list.filter(function (s) { return +s.dataset.step === current; })[0];
		var i = list.indexOf(here) + dir;
		if (i < 0 || i >= list.length) return;
		commit();
		show(+list[i].dataset.step);
	}

	nav.next.addEventListener("click", function () { step(1); });
	nav.skip.addEventListener("click", function () { step(1); });
	nav.back.addEventListener("click", function () { step(-1); });

	/* Enter should advance, not submit, until the last step. */
	form.addEventListener("keydown", function (e) {
		if (e.key !== "Enter" || e.target.tagName === "TEXTAREA") return;
		if (nav.submit.hidden) { e.preventDefault(); step(1); }
	});

	/* -------------------------------------------------------------- routes -- */

	var scopeWrap = form.querySelector(".zk-scope");
	var SCOPE = {};
	try { SCOPE = JSON.parse(scopeWrap.dataset.scopeGeneric || "{}"); } catch (e) { SCOPE = {}; }

	form.querySelectorAll('[data-route]').forEach(function (input) {
		input.addEventListener("change", function () {
			route = input.dataset.route;
			record("route", ROUTE_PHRASE[route]);
			buildScope(SCOPE[route] || SCOPE.generic || []);

			if (!reduce) {
				form.querySelectorAll(".zk-route").forEach(function (card) {
					if (!card.contains(input)) card.classList.add("is-folding");
				});
			}
			window.setTimeout(function () { step(1); }, reduce ? 0 : 520);
		});
	});

	/* --------------------------------------------------------------- scope -- */
	/* The selects stay in the DOM and stay the source of truth; the chips are a
	   skin over them. That way the posted value is the same either way, and
	   there is nothing to keep in sync. */

	function buildScope(items) {
		scopeWrap.innerHTML = "";
		var chips = document.createElement("div");
		chips.className = "zk-brief-chips zk-brief-chips--scope";

		items.forEach(function (item) {
			var sel = document.createElement("select");
			sel.name = "zk_scope[" + item + "]";
			sel.className = "zk-sr";
			sel.innerHTML = '<option value="">Not needed</option><option value="must">Must have</option><option value="later">Can wait</option>';

			var btn = document.createElement("button");
			btn.type = "button";
			btn.className = "zk-chip";
			btn.setAttribute("aria-pressed", "false");
			btn.innerHTML = "<span>" + item + "</span>";

			btn.addEventListener("click", function () {
				var next = sel.value === "" ? "must" : (sel.value === "must" ? "later" : "");
				sel.value = next;
				btn.dataset.state = next;
				btn.setAttribute("aria-pressed", String(next === "must"));
				var tag = btn.querySelector(".zk-chip__tag");
				if (tag) tag.remove();
				if (next) {
					btn.insertAdjacentHTML("beforeend",
						'<span class="zk-chip__tag">' + (next === "must" ? "must" : "later") + "</span>");
				}
			});

			chips.appendChild(btn);
			scopeWrap.appendChild(sel);
		});

		scopeWrap.appendChild(chips);
		scopeWrap.insertAdjacentHTML("beforeend",
			'<div class="zk-legend">' +
			'<span><i class="zk-legend__must"></i> Must have</span>' +
			'<span><i class="zk-legend__later"></i> Can wait</span>' +
			'<span><i class="zk-legend__no"></i> Tap again to clear</span></div>');
	}

	/* ------------------------------------------------- selects into sliders -- */

	var REACT = {
		timeline: {
			"about four weeks": ["warn", "That's tight. Possible — but it decides what goes in the first release, rather than the other way round. Expect us to cut something."],
			"about two months": ["ok", "Fast, but workable for a focused scope. We'd want the content ready before we start."],
			"about three months": ["ok", "Realistic for most of what we build, assuming decisions come back within a few days."],
			"about six months": ["ok", "Comfortable. Long enough that we'd phase it, so you get something usable well before the end."],
			"nine months or more": ["ok", "Room to do it properly. We'd still ship something usable early rather than save it all for the end."],
			"no fixed date": ["ok", "No date is fine, and often honest. It does mean we'd set one with you — work without a date tends not to finish."]
		},
		budget: {
			"under R50,000": ["warn", "Honest answer: that's one focused thing done properly — a small site, or a single feature. If what you've described is bigger, we'll tell you rather than quietly cut corners."],
			"R50,000 to R150,000": ["ok", "A solid site, or a first version of a product with one or two moving parts."],
			"R150,000 to R400,000": ["ok", "A product with several moving parts — accounts, payments, an admin side — built in phases you can stop between."],
			"over R400,000": ["ok", "Room to build properly and keep going after launch. We'd phase it tightly so you're never far from something shippable."],
			"not yet decided": ["ok", "Completely fine, and more common than you'd think. Tell us the problem and we'll tell you what it should cost — that's most of what the Discovery Sprint is for."]
		},
		clienttime: {
			"a couple of hours a week": ["warn", "Worth saying: that's enough for a site and thin for a product. Builds wait on answers more often than on code."],
			"about a day a week": ["ok", "Good. Projects move at the speed of the client's decisions more often than the developer's."],
			"several days a week": ["ok", "That's more than most clients give, and it shows in how fast these go."]
		}
	};

	function react(name, value) {
		var el = form.querySelector('[data-react="' + name + '"]');
		if (!el) return;
		var pair = (REACT[name] || {})[value];
		if (!pair) { el.innerHTML = ""; return; }
		el.dataset.tone = pair[0];
		el.innerHTML = "<p>" + pair[1] + "</p>";
	}

	["timeline", "budget"].forEach(function (name) {
		var sel = form.querySelector("#zk-" + name);
		if (!sel) return;
		var opts = Array.prototype.slice.call(sel.options);

		var wrap = document.createElement("div");
		wrap.className = "zk-slider";
		wrap.innerHTML =
			'<span class="zk-range-val"></span>' +
			'<input type="range" class="zk-range" min="0" max="' + (opts.length - 1) + '" step="1" aria-label="' + name + '">' +
			'<div class="zk-ticks">' + opts.map(function (o) {
				return "<span>" + o.textContent.toUpperCase() + "</span>";
			}).join("") + "</div>";

		var range = wrap.querySelector(".zk-range");
		var val = wrap.querySelector(".zk-range-val");
		range.value = String(sel.selectedIndex);

		function sync() {
			var o = opts[+range.value];
			sel.value = o.value;
			val.textContent = o.textContent;
			react(name, o.value);
		}
		range.addEventListener("input", sync);

		sel.classList.add("zk-sr");
		sel.parentNode.classList.add("zk-field--slider");
		sel.parentNode.appendChild(wrap);
		sync();
	});

	form.querySelectorAll('input[name="zk_clienttime"]').forEach(function (input) {
		input.addEventListener("change", function () { react("clienttime", input.value); });
	});

	/* --------------------------------------------- commit answers to the brief -- */

	/* Only answers from steps the visitor has actually reached count. The
	   timeline and budget controls carry sensible defaults so they are usable
	   the moment they appear — but a default is not an answer, and the panel
	   was asserting "Live in about three months" before that question had been
	   asked. Nothing is claimed before it is known, which is the whole premise
	   of a brief that writes itself. */
	function reached(el) {
		var step = el && el.closest(".zk-brief-step");
		return !!(step && seen[+step.dataset.step]);
	}

	function commit() {
		/* Read whatever the form actually has rather than a list of field names
		   kept in step with it by hand. The hardcoded version silently dropped
		   the two questions added after it was written: they saved and emailed
		   correctly and simply never appeared in the panel, which is the worst
		   kind of bug because nothing looks broken. */
		var fields = form.querySelectorAll(
			"input[name^='zk_'], textarea[name^='zk_'], select[name^='zk_']"
		);
		Array.prototype.forEach.call(fields, function (el) {
			if (el.name.indexOf("zk_scope[") === 0) { return; }   // grouped below
			if (el.name === "zk_route") { return; }               // phrased by its own handler
			if (el.type === "radio" && !el.checked) { return; }
			var val = (el.value || "").trim();
			if (!val || !reached(el)) { return; }
			record(el.name.replace(/^zk_/, ""), val);
		});

		var must = [], later = [];
		Array.prototype.forEach.call(form.querySelectorAll(".zk-scope select"), function (sel) {
			var item = sel.name.replace(/^zk_scope\[|\]$/g, "").toLowerCase();
			if (sel.value === "must") { must.push(item); }
			else if (sel.value === "later") { later.push(item); }
		});
		if (must.length) {
			record("scope", must.join(", ") + (later.length ? "; later, " + later.join(", ") : ""));
		}
	}

	form.addEventListener("submit", function () {
		commit();
		if (stamp) stamp.hidden = false;
	});

	/* ---------------------------------------------------------------- boot -- */

	buildScope(SCOPE.generic || []);
	show(1);
})();
