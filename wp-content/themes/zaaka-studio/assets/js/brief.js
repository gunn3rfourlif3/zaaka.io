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
	var route = null;
	var bar = document.querySelector(".zk-progress__bar");
	var nav = {
		back: form.querySelector('[data-nav="back"]'),
		next: form.querySelector('[data-nav="next"]'),
		submit: form.querySelector('[data-nav="submit"]'),
		skip: form.querySelector('[data-nav="skip"]')
	};

	/* Steps a route skips outright — the third of the form nobody sees. */
	var SKIP = { site: [4], rescue: [4], integrate: [3, 4], product: [], unsure: [] };

	var ROUTE_PHRASE = {
		product: "planning a new product or platform",
		site: "planning a site that sells something that already exists",
		rescue: "dealing with something already built that has stopped keeping up",
		integrate: "connecting systems that don't talk to each other",
		unsure: "still working out what they need"
	};

	/* ---------------------------------------------------------- the panel -- */
	/* Built here rather than in PHP: with no JavaScript there is no running
	   commentary to show, and an empty skeleton would just be noise. */
	var SLOTS = [
		["company", "Someone", "%s"],
		["route", "working out what they need", " is %s."],
		["problem", "the problem hasn't been described yet", " Right now, %s."],
		["donothing", "it's unclear what happens", " Left twelve months, %s."],
		["audience", "an audience we don't know yet", " It's for %s"],
		["count", "an unknown number", ", roughly %s of them."],
		["money", "a route not yet stated", " It earns through %s."],
		["metric", "a number not yet given", " Success looks like %s."],
		["url", "nothing on record", " Today there is %s"],
		["owner", "ownership unstated", ", and %s."],
		["scope", "to be chosen", " First release: %s."],
		["timeline", "a timeframe not set", " Live in %s"],
		["datereal", "realism unknown", " — %s."],
		["budget", "not stated", " Budget %s."],
		["decider", "sign-off unclear", " %s,"],
		["clienttime", "an unknown amount of time", " with %s a week from your side."]
	];

	var panel = document.querySelector(".zk-brief-panel");
	panel.hidden = false;
	panel.innerHTML =
		'<div class="zk-brief__head">' +
			'<h2 class="zk-brief__title">Your brief</h2>' +
			'<span class="zk-brief__pct" aria-live="polite">0% written</span>' +
		'</div>' +
		'<div class="zk-brief__body"></div>' +
		'<div class="zk-stamp" hidden>Brief complete</div>';

	var body = panel.querySelector(".zk-brief__body");
	var pctEl = panel.querySelector(".zk-brief__pct");
	var stamp = panel.querySelector(".zk-stamp");

	SLOTS.forEach(function (s) {
		var parts = s[2].split("%s");
		body.appendChild(document.createTextNode(parts[0]));
		var span = document.createElement("span");
		span.className = "zk-slot";
		span.dataset.slot = s[0];
		span.textContent = s[1];
		body.appendChild(span);
		if (parts[1]) body.appendChild(document.createTextNode(parts[1]));
	});

	function write(name, text) {
		var el = body.querySelector('[data-slot="' + name + '"]');
		if (!el || !text) return;
		el.classList.add("is-set");

		if (reduce) { el.textContent = text; pct(); return; }

		el.classList.add("is-writing");
		var i = 0;
		var speed = text.length > 44 ? 6 : 16;
		(function tick() {
			el.textContent = text.slice(0, ++i);
			if (i < text.length) { window.setTimeout(tick, speed); }
			else { window.setTimeout(function () { el.classList.remove("is-writing"); }, 420); }
		})();
		pct();
	}

	function pct() {
		var all = body.querySelectorAll(".zk-slot").length;
		var set = body.querySelectorAll(".zk-slot.is-set").length;
		var p = Math.round((set / all) * 100);
		pctEl.textContent = p + "% written";
		if (bar) bar.style.width = Math.max(p, ((current - 1) / steps.length) * 100) + "%";
	}

	/* ------------------------------------------------------------ stepping -- */

	function visible() {
		var skip = (route && SKIP[route]) || [];
		return steps.filter(function (s) { return skip.indexOf(+s.dataset.step) === -1; });
	}

	function show(n) {
		var list = visible();
		var target = list.filter(function (s) { return +s.dataset.step >= n; })[0] || list[list.length - 1];
		var prev = steps.filter(function (s) { return !s.hidden; })[0];
		current = +target.dataset.step;

		function swap() {
			steps.forEach(function (s) { s.hidden = true; s.classList.remove("is-leaving"); });
			target.hidden = false;

			var last = list.indexOf(target) === list.length - 1;
			nav.back.hidden = list.indexOf(target) === 0;
			nav.next.hidden = last;
			nav.skip.hidden = last;
			nav.submit.hidden = !last;

			var focusable = target.querySelector("textarea, input:not([type=hidden]):not(.zk-sr), select, .zk-route");
			if (focusable && focusable.focus) { try { focusable.focus({ preventScroll: true }); } catch (e) {} }
			pct();
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
			write("route", ROUTE_PHRASE[route]);
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

	function commit() {
		["problem", "count", "metric", "company", "url"].forEach(function (id) {
			var el = form.querySelector("#zk-" + id);
			if (el && el.value.trim()) write(id, el.value.trim());
		});
		["donothing", "audience", "money", "owner", "datereal", "decider", "clienttime"].forEach(function (name) {
			var checked = form.querySelector('input[name="zk_' + name + '"]:checked');
			if (checked) write(name, checked.value);
		});
		["timeline", "budget"].forEach(function (name) {
			var sel = form.querySelector("#zk-" + name);
			if (sel && sel.value) write(name, sel.value);
		});

		var must = [], later = [];
		form.querySelectorAll('.zk-scope select').forEach(function (s) {
			var item = s.name.replace(/^zk_scope\[|\]$/g, "").toLowerCase();
			if (s.value === "must") must.push(item);
			else if (s.value === "later") later.push(item);
		});
		if (must.length) write("scope", must.join(", ") + (later.length ? "; later, " + later.join(", ") : ""));
	}

	form.addEventListener("submit", function () {
		commit();
		if (stamp) stamp.hidden = false;
	});

	/* ---------------------------------------------------------------- boot -- */

	buildScope(SCOPE.generic || []);
	show(1);
})();
