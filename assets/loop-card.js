(function () {
	'use strict';

	/**
	 * Move focus after the browser's own native "focus follows activation" step
	 * for the button that was actually clicked or keyboard-activated — a plain
	 * focus() call in the same task, or deferred only to the next microtask/
	 * animation frame, reliably loses that race in this environment (measured:
	 * requestAnimationFrame x2 still loses for keyboard-triggered Enter/Space;
	 * setTimeout needs >=50ms measured under normal load). 300ms clears the
	 * overlay opacity/visibility transition (0.22s in loop-card.css) before focus().
	 */
	var FOCUS_HANDOFF_DELAY_MS = 300;
	function afterActivationSettles(fn) {
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				setTimeout(fn, FOCUS_HANDOFF_DELAY_MS);
			});
		});
	}

	function parsePayload(el) {
		try {
			return JSON.parse(el.getAttribute('data-biopentra-product') || '{}');
		} catch (e) {
			return null;
		}
	}

	function ajaxUrl() {
		return (window.biopentraLoopCard && biopentraLoopCard.wc_ajax_url
			? biopentraLoopCard.wc_ajax_url
			: ''
		).replace('%%endpoint%%', 'add_to_cart');
	}

	function t(key) {
		return (
			(window.biopentraLoopCard &&
				biopentraLoopCard.i18n &&
				biopentraLoopCard.i18n[key]) ||
			key
		);
	}

	function prefersFinePointer() {
		return (
			window.matchMedia &&
			window.matchMedia('(hover: hover) and (pointer: fine)').matches
		);
	}

	function buildOverlay(root, data) {
		var actionBtn = document.createElement('button');
		actionBtn.type = 'button';
		actionBtn.className = 'biopentra-loop-card-action';
		actionBtn.setAttribute('aria-label', t('quickShop'));
		actionBtn.innerHTML =
			'<span class="biopentra-loop-card-action__icon" aria-hidden="true"></span>';

		var ov = document.createElement('div');
		ov.className = 'biopentra-loop-overlay';
		ov.setAttribute('role', 'dialog');
		ov.setAttribute('aria-label', 'Product actions');

		var top = document.createElement('div');
		top.className = 'biopentra-loop-overlay__top';
		var closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'biopentra-loop-overlay__close';
		closeBtn.innerHTML =
			'<span aria-hidden="true">\u00d7</span> ' + t('close');
		top.appendChild(closeBtn);

		var main = document.createElement('div');
		main.className = 'biopentra-loop-overlay__main';

		var btnView = document.createElement('a');
		btnView.className =
			'biopentra-loop-overlay__btn biopentra-loop-overlay__btn--ghost';
		btnView.href = data.permalink;
		btnView.textContent = t('viewProduct');

		var btnQuick = document.createElement('button');
		btnQuick.type = 'button';
		btnQuick.className =
			'biopentra-loop-overlay__btn biopentra-loop-overlay__btn--primary';
		btnQuick.textContent = t('quickShop');

		main.appendChild(btnView);
		main.appendChild(btnQuick);

		var quick = document.createElement('div');
		quick.className = 'biopentra-loop-overlay__quick';

		var quickTop = document.createElement('div');
		quickTop.className = 'biopentra-loop-overlay__quick-top';
		var backBtn = document.createElement('button');
		backBtn.type = 'button';
		backBtn.className = 'biopentra-loop-overlay__back';
		backBtn.textContent = '\u2190 ' + t('back');
		quickTop.appendChild(backBtn);

		var label = document.createElement('div');
		label.className = 'biopentra-loop-overlay__label';
		label.textContent =
			data.variations && data.variations.length ? t('strength') : '';

		var pills = document.createElement('div');
		pills.className = 'biopentra-loop-overlay__pills';

		var clearBtn = document.createElement('button');
		clearBtn.type = 'button';
		clearBtn.className = 'biopentra-loop-overlay__clear';
		clearBtn.textContent = t('clear');

		var addBtn = document.createElement('button');
		addBtn.type = 'button';
		addBtn.className = 'biopentra-loop-overlay__add';
		addBtn.textContent = t('addToCart');

		var msg = document.createElement('div');
		msg.className = 'biopentra-loop-overlay__msg';

		quick.appendChild(quickTop);
		quick.appendChild(label);
		quick.appendChild(pills);
		quick.appendChild(clearBtn);
		quick.appendChild(addBtn);
		quick.appendChild(msg);

		ov.appendChild(top);
		ov.appendChild(main);
		ov.appendChild(quick);

		root.appendChild(actionBtn);
		root.appendChild(ov);

		return {
			actionBtn: actionBtn,
			ov: ov,
			closeBtn: closeBtn,
			main: main,
			btnView: btnView,
			btnQuick: btnQuick,
			quick: quick,
			backBtn: backBtn,
			label: label,
			pills: pills,
			clearBtn: clearBtn,
			addBtn: addBtn,
			msg: msg,
			selectedId: null,
		};
	}

	function renderPills(ui, data) {
		ui.pills.innerHTML = '';
		if (!data.variations || !data.variations.length) {
			ui.label.style.display = 'none';
			ui.clearBtn.style.display = 'none';
			return;
		}
		ui.label.style.display = '';
		ui.clearBtn.style.display = '';

		data.variations.forEach(function (v) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'biopentra-loop-pill';
			b.textContent = v.label;
			b.dataset.variationId = String(v.variation_id);
			if (!v.in_stock) {
				b.disabled = true;
				b.classList.add('is-disabled');
			}
			if (ui.selectedId === v.variation_id) {
				b.classList.add('is-selected');
			}
			b.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (!v.in_stock) return;
				ui.selectedId = v.variation_id;
				Array.prototype.forEach.call(
					ui.pills.querySelectorAll('.biopentra-loop-pill'),
					function (x) {
						x.classList.toggle(
							'is-selected',
							x.dataset.variationId === String(ui.selectedId)
						);
					}
				);
				ui.addBtn.disabled = false;
				ui.msg.textContent = '';
			});
			ui.pills.appendChild(b);
		});
	}

	function resetQuickState(ui, data) {
		ui.selectedId = null;
		ui.ov.classList.remove('biopentra-loop-overlay--quick');
		renderPills(ui, data);
		ui.addBtn.disabled = !!(data.variations && data.variations.length);
		ui.msg.textContent = '';
	}

	function setOverlayState(root, ui, data, state) {
		var open = state !== 'closed';
		var wasOpen = ui.ov.classList.contains('is-open');
		if (state === 'closed') {
			resetQuickState(ui, data);
		}
		ui.ov.classList.toggle('is-open', open);
		ui.ov.classList.toggle('biopentra-loop-overlay--quick', state === 'quick');
		ui.ov.dataset.state = state;
		root.classList.toggle('is-overlay-open', open);
		root.classList.toggle('is-overlay-quick', state === 'quick');
		ui.actionBtn.setAttribute('aria-expanded', open ? 'true' : 'false');

		// Focus management: move focus into the overlay when it opens, and
		// back to the "+" that opened it when it fully closes. Only on the
		// closed<->open transition itself, not on menu<->quick sub-state
		// changes, so focus never jumps unexpectedly mid-interaction.
		// See afterActivationSettles() above for why this isn't a plain focus() call.
		if (open && !wasOpen) {
			afterActivationSettles(function () {
				ui.closeBtn.focus({ preventScroll: true });
			});
		} else if (!open && wasOpen) {
			afterActivationSettles(function () {
				ui.actionBtn.focus({ preventScroll: true });
			});
		}
	}

	function openOverlay(root, ui, data, state) {
		if (state !== 'quick') {
			resetQuickState(ui, data);
		}
		setOverlayState(root, ui, data, state || 'menu');
	}

	function linkCardContent(root, data) {
		var imageContainer = root.querySelector(
			'.elementor-widget-theme-post-featured-image .elementor-widget-container'
		);
		var image = imageContainer
			? imageContainer.querySelector('img')
			: root.querySelector('.elementor-widget-theme-post-featured-image img');
		if (image && !image.closest('a')) {
			var imageLink = document.createElement('a');
			imageLink.className = 'biopentra-loop-card-media-link';
			imageLink.href = data.permalink;
			// The title link is the card's one primary tab stop (stretched to
			// cover the whole surface below); the image stays clickable but is
			// removed from tab order so keyboard users don't hit the same
			// destination twice per card.
			imageLink.setAttribute('tabindex', '-1');
			imageLink.setAttribute('aria-label', image.getAttribute('alt') || t('viewProduct'));
			image.parentNode.insertBefore(imageLink, image);
			imageLink.appendChild(image);
		}

		var title = root.querySelector(
			'.elementor-widget-woocommerce-product-title .elementor-heading-title'
		);
		if (title && !title.querySelector('a')) {
			var titleLink = document.createElement('a');
			// biopentra-loop-card-stretch: stretched-link pattern (see loop-card.css)
			// — makes the whole card surface a click target for this one real
			// anchor, without JS navigation and without nested anchors.
			titleLink.className = 'biopentra-loop-card-title-link biopentra-loop-card-stretch';
			titleLink.href = data.permalink;
			while (title.firstChild) {
				titleLink.appendChild(title.firstChild);
			}
			title.appendChild(titleLink);
		}
	}

	function addToCart(ui, data) {
		var fd = new FormData();
		fd.append('quantity', '1');

		if (data.variations && data.variations.length) {
			if (!ui.selectedId) {
				ui.msg.textContent = t('chooseFirst');
				return;
			}
			var chosen = data.variations.filter(function (v) {
				return v.variation_id === ui.selectedId;
			})[0];
			if (!chosen || !chosen.in_stock) return;
			// WC_AJAX::add_to_cart only resolves variation when product_id is the variation
			// post ID; it does not read a separate variation_id field (parent + variation_id fails).
			fd.append('product_id', String(chosen.variation_id));
		} else {
			fd.append('product_id', String(data.product_id));
		}

		ui.addBtn.disabled = true;
		ui.addBtn.textContent = t('adding');
		ui.actionBtn.disabled = true;
		ui.actionBtn.classList.add('is-loading');
		ui.msg.textContent = '';

		fetch(ajaxUrl(), {
			method: 'POST',
			credentials: 'same-origin',
			body: fd,
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (res) {
				if (res && res.error && res.product_url) {
					window.location.href = res.product_url;
					return;
				}
				ui.addBtn.textContent = t('addToCart');
				ui.addBtn.disabled = false;
				ui.actionBtn.disabled = false;
				ui.actionBtn.classList.remove('is-loading');
				var $ = window.jQuery;
				if ($ && $.fn && $.fn.trigger) {
					$(document.body).trigger('added_to_cart', [
						res.fragments,
						res.cart_hash,
						$(ui.addBtn),
					]);
				}
				resetQuickState(ui, data);
				setOverlayState(ui.ov.parentNode, ui, data, 'closed');
			})
			.catch(function () {
				ui.msg.textContent = 'Error';
				ui.addBtn.textContent = t('addToCart');
				ui.addBtn.disabled = false;
				ui.actionBtn.disabled = false;
				ui.actionBtn.classList.remove('is-loading');
			});
	}

	function enhance(root) {
		if (root.getAttribute('data-biopentra-enhanced') === '1') {
			return;
		}
		var data = parsePayload(root);
		if (!data || !data.product_id) {
			return;
		}
		root.setAttribute('data-biopentra-enhanced', '1');

		linkCardContent(root, data);

		var ui = buildOverlay(root, data);
		ui._data = data;
		ui.actionBtn.setAttribute('aria-haspopup', 'dialog');
		ui.actionBtn.setAttribute('aria-expanded', 'false');

		var fine = prefersFinePointer();
		var oosOnly = !data.any_in_stock;

		if (oosOnly) {
			root.classList.add('is-all-oos');
			ui.ov.classList.add('biopentra-loop-overlay--oos-only');
			var badge = document.createElement('div');
			badge.className = 'biopentra-loop-card-oos-badge';
			badge.textContent = t('outOfStock');
			root.appendChild(badge);
			ui.btnQuick.style.display = 'none';
			ui.quick.setAttribute('hidden', 'hidden');
			ui.quick.style.display = 'none';
			ui.main.classList.add('biopentra-loop-overlay__main--solo');
			btnViewProminent(ui.btnView);
			ui.actionBtn.classList.add('is-disabled');
			ui.actionBtn.setAttribute('aria-label', t('outOfStock'));
		} else {
			renderPills(ui, data);

			if (!data.variations || !data.variations.length) {
				ui.addBtn.disabled = false;
			} else {
				ui.addBtn.disabled = true;
			}

			ui.btnQuick.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (data.variations && data.variations.length) {
					openOverlay(root, ui, data, 'quick');
					return;
				}
				addToCart(ui, data);
			});

			ui.backBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				setOverlayState(root, ui, data, 'closed');
			});

			ui.clearBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				ui.selectedId = null;
				renderPills(ui, data);
				ui.addBtn.disabled = !!(data.variations && data.variations.length);
				ui.msg.textContent = '';
			});

			ui.addBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				addToCart(ui, data);
			});
		}

		ui.actionBtn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			openOverlay(root, ui, data, 'menu');
		});

		root.addEventListener(
			'click',
			function (e) {
				if (e.target.closest('.biopentra-loop-overlay')) return;
				if (e.target.closest('a')) return;
				if (e.target.closest('button')) return;
				if (!fine && ui.ov.classList.contains('is-open')) {
					e.preventDefault();
					setOverlayState(root, ui, data, 'closed');
				}
			},
			false
		);

		ui.closeBtn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			setOverlayState(root, ui, data, 'closed');
		});

		ui.ov.addEventListener('click', function (e) {
			e.stopPropagation();
		});

		ui.ov.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' || e.key === 'Esc') {
				e.stopPropagation();
				setOverlayState(root, ui, data, 'closed');
			}
		});

		document.addEventListener(
			'keydown',
			function (e) {
				if (!ui.ov.classList.contains('is-open')) {
					return;
				}
				if (e.key !== 'Escape' && e.key !== 'Esc') {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				setOverlayState(root, ui, data, 'closed');
			},
			true
		);
	}

	function btnViewProminent(a) {
		a.classList.remove('biopentra-loop-overlay__btn--ghost');
		a.classList.add('biopentra-loop-overlay__btn--primary');
	}

	function init(scope) {
		var root = scope && scope.nodeType === 1 ? scope : document;
		root
			.querySelectorAll('.biopentra-loop-card-root[data-biopentra-product]')
			.forEach(enhance);

		// Milestone D1 — promote first visible card image as LCP candidate.
		// Elementor often forces loading=lazy in cached widget HTML; correct at enhance time.
		if (!document.documentElement.hasAttribute('data-bp-lcp-card')) {
			var firstImg = document.querySelector(
				'.biopentra-loop-card-root .elementor-widget-theme-post-featured-image img, .biopentra-loop-card-root img'
			);
			if (firstImg) {
				firstImg.setAttribute('loading', 'eager');
				firstImg.setAttribute('fetchpriority', 'high');
				var sizes = firstImg.getAttribute('sizes') || '';
				if (sizes.indexOf('auto,') === 0) {
					firstImg.setAttribute('sizes', sizes.replace(/^auto,\s*/i, ''));
				}
				document.documentElement.setAttribute('data-bp-lcp-card', '1');
			}
		}
	}

	window.biopentraLoopCardInit = init;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			init();
		});
	} else {
		init();
	}
})();
