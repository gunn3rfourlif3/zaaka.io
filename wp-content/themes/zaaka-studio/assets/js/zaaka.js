/* Zaaka — ~1 KB of progressive enhancement. Nothing here is load-bearing:
   with JavaScript off the page renders complete and static. */
(function () {
	"use strict";
	var root = document.documentElement;
	root.classList.add("zk-js");

	if (!("IntersectionObserver" in window)) {
		root.classList.remove("zk-js");
		return;
	}

	var reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
	if (reduce.matches) return;

	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (e) {
			if (!e.isIntersecting) return;
			e.target.classList.add("is-in");
			io.unobserve(e.target);
		});
	}, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });

	function observe() {
		document.querySelectorAll(".zk-reveal:not(.is-in)").forEach(function (el, i) {
			el.style.transitionDelay = Math.min(i % 6, 5) * 60 + "ms";
			io.observe(el);
		});
	}

	if (document.readyState !== "loading") observe();
	else document.addEventListener("DOMContentLoaded", observe);

	// Failsafe: whatever happens, nothing stays invisible. If the observer has
	// not revealed an element within a few seconds, show everything.
	window.setTimeout(function () {
		document.querySelectorAll(".zk-reveal:not(.is-in)").forEach(function (el) {
			el.style.transitionDelay = "0ms";
			el.classList.add("is-in");
		});
	}, 4000);
})();
