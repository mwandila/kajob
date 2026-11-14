/**
 * Kajob — front-end behavior.
 * Deliberately dependency-free and tiny.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		/* Reveal section content as it enters view; never leave it hidden if unsupported. */
		var revealItems = document.querySelectorAll('[data-reveal]');
		if ('IntersectionObserver' in window) {
			var revealObserver = new IntersectionObserver(function (entries, observer) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					}
				});
			}, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });

			revealItems.forEach(function (item) { revealObserver.observe(item); });
		} else {
			revealItems.forEach(function (item) { item.classList.add('is-visible'); });
		}

		/* Mobile navigation toggle */
		var toggle = document.querySelector('.main-navigation__toggle');
		var nav = document.querySelector('.main-navigation');

		if (toggle && nav) {
			toggle.addEventListener('click', function () {
				var isOpen = nav.classList.toggle('is-open');
				toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			});
		}

		/* Light / dark toggle — only two modes, persisted per visitor */
		var themeToggle = document.querySelector('[data-theme-toggle]');
		var richTextEditors = document.querySelectorAll('.wp-editor-wrap');
		Array.prototype.forEach.call(richTextEditors, function (editorWrap) {
			var syncEditorTheme = function () {
				var paper = getComputedStyle(document.documentElement).getPropertyValue('--paper').trim();
				var text = getComputedStyle(document.documentElement).getPropertyValue('--text').trim();
				var frame = editorWrap.querySelector('iframe');

				if (!frame) return;
				if (!frame.dataset.kajobThemeSync) {
					frame.dataset.kajobThemeSync = '1';
					frame.addEventListener('load', syncEditorTheme);
				}

				try {
					var editorBody = frame.contentDocument && frame.contentDocument.body;
					if (editorBody) {
						editorBody.style.setProperty('background-color', paper, 'important');
						editorBody.style.setProperty('color', text, 'important');
					}
				} catch (error) {
					// The editor iframe can be unavailable while WordPress initializes it.
				}
			};

			var editorObserver = new MutationObserver(syncEditorTheme);
			editorObserver.observe(editorWrap, { childList: true, subtree: true });
			editorWrap.addEventListener('load', syncEditorTheme, true);
			var themeObserver = new MutationObserver(syncEditorTheme);
			themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
			syncEditorTheme();
		});

		if (themeToggle) {
			themeToggle.addEventListener('click', function () {
				var current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
				var next = current === 'dark' ? 'light' : 'dark';

				document.documentElement.setAttribute('data-theme', next);

				try {
					localStorage.setItem('kajob-theme', next);
				} catch (e) {
					/* Private mode — theme still applies for this page view. */
				}
			});
		}

		/* Close mobile nav when clicking a link */
		if (nav) {
			var demoMenu = nav.querySelector('.kajob-home-demo-menu');
			if (demoMenu) {
				var demoTrigger = demoMenu.querySelector(':scope > a');
				var setDemoExpanded = function (expanded) {
					demoTrigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				};

				demoMenu.addEventListener('focusin', function () { setDemoExpanded(true); });
				demoMenu.addEventListener('focusout', function (event) {
					if (!demoMenu.contains(event.relatedTarget)) {
						demoMenu.classList.remove('is-open');
						setDemoExpanded(false);
					}
				});
				demoMenu.addEventListener('pointerenter', function () { setDemoExpanded(true); });
				demoMenu.addEventListener('pointerleave', function () {
					if (!demoMenu.classList.contains('is-open') && !demoMenu.contains(document.activeElement)) {
						setDemoExpanded(false);
					}
				});
			}

			nav.addEventListener('click', function (event) {
				var demosLink = event.target.closest('.kajob-home-demo-menu > a');
				if (demosLink && window.matchMedia('(max-width: 900px)').matches && !demosLink.parentElement.classList.contains('is-open')) {
					event.preventDefault();
					demosLink.parentElement.classList.add('is-open');
					demosLink.setAttribute('aria-expanded', 'true');
					return;
				}

				if (event.target.closest('a') && nav.classList.contains('is-open')) {
					nav.classList.remove('is-open');
					var openDemo = nav.querySelector('.kajob-home-demo-menu.is-open');
					if (openDemo) {
						openDemo.classList.remove('is-open');
						openDemo.querySelector(':scope > a').setAttribute('aria-expanded', 'false');
					}
					if (toggle) {
						toggle.setAttribute('aria-expanded', 'false');
					}
				}
			});
		}
	});
})();
