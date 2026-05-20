(function ($) {
	'use strict';

	var cfg = window.biopentraShopLoopFilter || {};
	var loopWidgetId = cfg.loopWidgetId || 'ed52b7f';
	var patched = false;
	var loading = false;
	var scrollObserver = null;

	function t(key) {
		return (cfg.i18n && cfg.i18n[key]) || key;
	}

	function getLoopWidget() {
		return document.querySelector('.elementor-element-' + loopWidgetId);
	}

	function triggerWooRefresh() {
		if (!$ || !$.fn || !$.fn.trigger) {
			return;
		}
		$(document.body).trigger('wc_fragments_refreshed');
		$(document.body).trigger('updated_wc_div');
	}

	function reinitLoopCards(root) {
		var scope = root || getLoopWidget() || document;
		if (typeof window.biopentraLoopCardInit === 'function') {
			window.biopentraLoopCardInit(scope);
		}
		triggerWooRefresh();
		$(document).trigger('biopentra-loop-cards-init', [scope]);
	}

	function getAnchor(widget) {
		return widget ? widget.querySelector('.e-load-more-anchor') : null;
	}

	function getLoadMoreButton(widget) {
		return widget ? widget.querySelector('.e-loop__load-more .elementor-button') : null;
	}

	function canLoadMore(widget) {
		var anchor = getAnchor(widget);
		if (!anchor) {
			return false;
		}
		var page = parseInt(anchor.getAttribute('data-page'), 10);
		var max = parseInt(anchor.getAttribute('data-max-page'), 10);
		if (!page || !max) {
			return false;
		}
		return page < max;
	}

	function clearLoadMoreError(widget) {
		if (!widget) {
			return;
		}
		var err = widget.querySelector('.biopentra-shop-load-more-error');
		if (err) {
			err.remove();
		}
	}

	function showLoadMoreError(widget) {
		if (!widget) {
			return;
		}
		clearLoadMoreError(widget);
		var el = document.createElement('p');
		el.className = 'biopentra-shop-load-more-error';
		el.setAttribute('role', 'alert');
		el.textContent = t('loadMoreError');
		var anchor = getAnchor(widget);
		if (anchor && anchor.parentNode) {
			anchor.parentNode.insertBefore(el, anchor.nextSibling);
		} else {
			var container = widget.querySelector('.elementor-widget-container');
			if (container) {
				container.appendChild(el);
			}
		}
	}

	function showLoadError(widget) {
		if (!widget || widget.querySelector('.biopentra-shop-loop-error')) {
			return;
		}
		var el = document.createElement('p');
		el.className = 'biopentra-shop-loop-error';
		el.setAttribute('role', 'alert');
		el.textContent = t('loadError');
		var container = widget.querySelector('.elementor-widget-container');
		if (container) {
			container.prepend(el);
		}
	}

	function setLoading(widget, isLoading) {
		loading = isLoading;
		if (!widget) {
			return;
		}
		widget.classList.toggle('e-load-more-pagination-loading', isLoading);
		widget.classList.toggle('biopentra-shop-loading-more', isLoading);
	}

	function syncPaginationEndState(widget) {
		if (!widget) {
			return;
		}
		var btn = getLoadMoreButton(widget);
		if (!canLoadMore(widget)) {
			widget.classList.add('e-load-more-pagination-end');
			if (btn) {
				btn.style.display = 'none';
			}
			disconnectInfiniteScroll();
		} else {
			widget.classList.remove('e-load-more-pagination-end');
			if (btn) {
				btn.style.display = '';
			}
		}
	}

	function appendDynamicStyles(widget, doc) {
		var styles = doc.querySelectorAll(
			'[data-id="' + loopWidgetId + '"] style[id^="loop-dynamic"]'
		);
		styles.forEach(function (styleEl) {
			widget.appendChild(styleEl);
		});
	}

	function mergeNextPage(widget, doc) {
		var container = widget.querySelector('.elementor-loop-container');
		if (!container) {
			return false;
		}

		var posts = doc.querySelectorAll(
			'[data-id="' + loopWidgetId + '"] .elementor-loop-container > .e-loop-item'
		);
		if (!posts.length) {
			return false;
		}

		posts.forEach(function (post) {
			container.appendChild(post);
		});

		var newAnchor = doc.querySelector('[data-id="' + loopWidgetId + '"] .e-load-more-anchor');
		var anchor = getAnchor(widget);
		if (newAnchor && anchor) {
			anchor.setAttribute('data-page', newAnchor.getAttribute('data-page') || '');
			anchor.setAttribute('data-max-page', newAnchor.getAttribute('data-max-page') || '');
			anchor.setAttribute('data-next-page', newAnchor.getAttribute('data-next-page') || '');
		}

		appendDynamicStyles(widget, doc);

		if (window.elementorFrontend && elementorFrontend.elementsHandler) {
			elementorFrontend.elementsHandler.runReadyTrigger(widget);
		}

		reinitLoopCards(widget);
		syncPaginationEndState(widget);
		return true;
	}

	function loadNextPage(widget) {
		if (!widget || loading || !canLoadMore(widget)) {
			return Promise.resolve(false);
		}

		var anchor = getAnchor(widget);
		var url = anchor ? anchor.getAttribute('data-next-page') : '';
		if (!url) {
			return Promise.resolve(false);
		}

		clearLoadMoreError(widget);
		setLoading(widget, true);

		return fetch(url, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('bad status');
				}
				return response.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				if (!mergeNextPage(widget, doc)) {
					throw new Error('empty');
				}
				return true;
			})
			.catch(function () {
				showLoadMoreError(widget);
				return false;
			})
			.finally(function () {
				setLoading(widget, false);
			});
	}

	function bindLoadMoreButton(widget) {
		var btn = getLoadMoreButton(widget);
		if (!btn || btn.dataset.biopentraLoadMoreBound === '1') {
			return;
		}
		btn.dataset.biopentraLoadMoreBound = '1';
		btn.addEventListener(
			'click',
			function (event) {
				event.preventDefault();
				event.stopImmediatePropagation();
				loadNextPage(widget);
			},
			true
		);
	}

	function disconnectInfiniteScroll() {
		if (scrollObserver) {
			scrollObserver.disconnect();
			scrollObserver = null;
		}
	}

	function bindInfiniteScroll(widget) {
		if (!cfg.infiniteScroll || !widget || !('IntersectionObserver' in window)) {
			return;
		}

		disconnectInfiniteScroll();

		var anchor = getAnchor(widget);
		if (!anchor || !canLoadMore(widget)) {
			return;
		}

		scrollObserver = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting || loading || !canLoadMore(widget)) {
						return;
					}
					loadNextPage(widget).then(function (ok) {
						if (ok && canLoadMore(widget)) {
							scrollObserver.observe(getAnchor(widget));
						}
					});
				});
			},
			{ root: null, rootMargin: cfg.scrollRootMargin || '0px 0px 240px 0px', threshold: 0 }
		);

		scrollObserver.observe(anchor);
	}

	function initShopPagination(widget) {
		if (!widget) {
			return;
		}
		loading = false;
		clearLoadMoreError(widget);
		syncPaginationEndState(widget);
		bindLoadMoreButton(widget);
		bindInfiniteScroll(widget);
	}

	function patchElementorLoopRefresh() {
		if (
			patched ||
			!window.elementorProFrontend ||
			!elementorProFrontend.modules ||
			!elementorProFrontend.modules.taxonomyFilter
		) {
			return;
		}

		var mod = elementorProFrontend.modules.taxonomyFilter;
		var original = mod.refreshLoopWidget.bind(mod);

		mod.refreshLoopWidget = function (widgetId, filterId) {
			var result = original(widgetId, filterId);
			if (!result || typeof result.then !== 'function') {
				return result;
			}
			return result
				.then(function (response) {
					if (widgetId === loopWidgetId) {
						var widget = getLoopWidget();
						if (widget) {
							var err = widget.querySelector('.biopentra-shop-loop-error');
							if (err) {
								err.remove();
							}
							if (!response || typeof response.data !== 'string') {
								showLoadError(widget);
							} else {
								loading = false;
								initShopPagination(widget);
							}
						}
					}
					return response;
				})
				.catch(function () {
					if (widgetId === loopWidgetId) {
						showLoadError(getLoopWidget());
					}
					return {};
				});
		};

		patched = true;
	}

	function bindElementorHooks() {
		if (!window.elementorFrontend || !elementorFrontend.hooks) {
			return;
		}
		var hooks = elementorFrontend.hooks;
		['frontend/element_ready/loop-grid', 'frontend/element_ready/loop-grid.product'].forEach(
			function (hook) {
				hooks.addAction(hook, function ($scope) {
					var el = $scope && $scope[0];
					if (!el || !el.classList.contains('elementor-element-' + loopWidgetId)) {
						return;
					}
					initShopPagination(el);
				});
			}
		);
	}

	$(window).on('elementor/frontend/init', function () {
		patchElementorLoopRefresh();
		bindElementorHooks();
		initShopPagination(getLoopWidget());
	});

	$(document).on('elementor-pro/loop-builder/after-insert-posts', function (_event, postsElements) {
		if (!postsElements || !postsElements.length) {
			return;
		}
		var widget = getLoopWidget();
		if (widget) {
			reinitLoopCards(widget);
		}
	});

	if (window.elementorFrontend && elementorFrontend.hooks) {
		patchElementorLoopRefresh();
		bindElementorHooks();
	}

	$(function () {
		initShopPagination(getLoopWidget());
	});
})(window.jQuery);
