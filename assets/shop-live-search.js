(function () {
	'use strict';

	var cfg = window.biopentraShopSearch || {};
	var root = cfg.restSearch || '';
	var minLen = typeof cfg.minLen === 'number' ? cfg.minLen : 2;
	var debounceMs = typeof cfg.debounceMs === 'number' ? cfg.debounceMs : 280;
	var str = cfg.i18n || {};

	function t(key, fallback) {
		return str[key] != null ? str[key] : fallback;
	}

	function titleText(item) {
		var title = item.title;
		if (typeof title === 'string') {
			return title;
		}
		if (title && typeof title.rendered === 'string') {
			var d = document.createElement('div');
			d.innerHTML = title.rendered;
			return d.textContent || '';
		}
		return '';
	}

	function buildUrl(q) {
		var u = new URL(root, window.location.origin);
		u.searchParams.set('search', q);
		u.searchParams.set('per_page', '8');
		u.searchParams.set('type', 'post');
		u.searchParams.set('subtype', 'product');
		return u.toString();
	}

	function init() {
		var form = document.querySelector('form.biopentra-shop-search');
		var input = document.getElementById('biopentra-shop-s');
		if (!form || !input || !root) {
			return;
		}

		var panel = document.createElement('div');
		panel.className = 'biopentra-shop-live';
		panel.setAttribute('role', 'listbox');
		panel.setAttribute('aria-label', t('suggestionsLabel', 'Product suggestions'));
		panel.hidden = true;
		form.appendChild(panel);

		var timer = null;
		var seq = 0;
		var ctrl = null;

		var params = new URLSearchParams(window.location.search);
		var pre = params.get('s');
		if (pre) {
			input.value = pre;
		}

		function hidePanel() {
			panel.hidden = true;
			panel.innerHTML = '';
			input.removeAttribute('aria-expanded');
		}

		function showLoading() {
			form.classList.add('is-loading');
			panel.hidden = false;
			panel.innerHTML =
				'<div class="biopentra-shop-live__status">' +
				escapeHtml(t('searching', 'Searching…')) +
				'</div>';
			input.setAttribute('aria-expanded', 'true');
		}

		function escapeHtml(s) {
			return String(s)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
		}

		function render(items) {
			form.classList.remove('is-loading');
			if (!items || !items.length) {
				panel.hidden = false;
				panel.innerHTML =
					'<div class="biopentra-shop-live__status biopentra-shop-live__status--empty">' +
					escapeHtml(t('noResults', 'No matching products')) +
					'</div>';
				input.setAttribute('aria-expanded', 'true');
				return;
			}

			var ul = document.createElement('ul');
			ul.className = 'biopentra-shop-live__list';
			items.forEach(function (item) {
				var title = titleText(item);
				if (!title || !item.url) {
					return;
				}
				var li = document.createElement('li');
				li.className = 'biopentra-shop-live__item';
				li.setAttribute('role', 'option');
				var a = document.createElement('a');
				a.className = 'biopentra-shop-live__link';
				a.href = item.url;
				a.textContent = title;
				li.appendChild(a);
				ul.appendChild(li);
			});
			panel.innerHTML = '';
			panel.appendChild(ul);
			panel.hidden = false;
			input.setAttribute('aria-expanded', 'true');
		}

		function runSearch(q) {
			var my = ++seq;
			if (ctrl) {
				ctrl.abort();
			}
			ctrl = new AbortController();

			fetch(buildUrl(q), {
				method: 'GET',
				credentials: 'same-origin',
				signal: ctrl.signal,
				headers: { Accept: 'application/json' },
			})
				.then(function (res) {
					if (!res.ok) {
						throw new Error('bad status');
					}
					return res.json();
				})
				.then(function (data) {
					if (my !== seq) {
						return;
					}
					render(Array.isArray(data) ? data : []);
				})
				.catch(function () {
					if (my !== seq) {
						return;
					}
					form.classList.remove('is-loading');
					panel.hidden = false;
					panel.innerHTML =
						'<div class="biopentra-shop-live__status biopentra-shop-live__status--err">' +
						escapeHtml(t('searchError', 'Search is temporarily unavailable')) +
						'</div>';
					input.setAttribute('aria-expanded', 'true');
				});
		}

		function schedule() {
			if (timer) {
				clearTimeout(timer);
			}
			var q = (input.value || '').trim();
			if (q.length < minLen) {
				if (ctrl) {
					ctrl.abort();
				}
				form.classList.remove('is-loading');
				hidePanel();
				return;
			}
			timer = setTimeout(function () {
				timer = null;
				showLoading();
				runSearch(q);
			}, debounceMs);
		}

		input.addEventListener('input', schedule);
		input.addEventListener('focus', function () {
			if (panel.innerHTML && input.value.trim().length >= minLen) {
				panel.hidden = false;
				input.setAttribute('aria-expanded', 'true');
			}
		});

		input.addEventListener('blur', function () {
			setTimeout(function () {
				var ae = document.activeElement;
				if (ae && panel.contains(ae)) {
					return;
				}
				if (!panel.matches(':hover')) {
					hidePanel();
				}
			}, 200);
		});
		panel.addEventListener('mousedown', function (e) {
			if (e.target.closest('a')) {
				e.preventDefault();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && document.activeElement === input) {
				hidePanel();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
