(function () {
	'use strict';

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
		btnQuick.textContent =
			data.variations && data.variations.length
				? t('quickShop')
				: t('addToCart');

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

		root.appendChild(ov);

		return {
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
				var $ = window.jQuery;
				if ($ && $.fn && $.fn.trigger) {
					$(document.body).trigger('added_to_cart', [
						res.fragments,
						res.cart_hash,
						$(ui.addBtn),
					]);
				}
				resetQuickState(ui, data);
				ui.ov.classList.remove('is-open');
			})
			.catch(function () {
				ui.msg.textContent = 'Error';
				ui.addBtn.textContent = t('addToCart');
				ui.addBtn.disabled = false;
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

		var ui = buildOverlay(root, data);
		ui._data = data;

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
					ui.ov.classList.add('biopentra-loop-overlay--quick');
					return;
				}
				addToCart(ui, data);
			});

			ui.backBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				resetQuickState(ui, data);
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

		root.addEventListener(
			'click',
			function (e) {
				if (e.target.closest('.biopentra-loop-overlay')) return;
				if (e.target.closest('a')) return;
				if (e.target.closest('button')) return;
				if (!fine) {
					e.preventDefault();
					ui.ov.classList.toggle('is-open');
				}
			},
			false
		);

		if (fine) {
			root.addEventListener('mouseleave', function () {
				resetQuickState(ui, data);
			});
		}

		ui.closeBtn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			resetQuickState(ui, data);
			ui.ov.classList.remove('is-open');
		});

		ui.ov.addEventListener('click', function (e) {
			e.stopPropagation();
		});
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
