(function ($) {
	'use strict';

	var cfg = window.biopentraShopCategoryDescription || {};
	var filterWidgetId = cfg.filterWidgetId || 'b3a2918';
	var allSlug = '__all';

	function getSection() {
		return document.getElementById('mp-category-description');
	}

	function getFilterRoot() {
		return document.querySelector('.elementor-element-' + filterWidgetId);
	}

	function getActiveFilterSlug() {
		var root = getFilterRoot();
		if (!root) {
			return '';
		}
		var active = root.querySelector('.e-filter-item[aria-pressed="true"]');
		if (!active) {
			return '';
		}
		var slug = active.getAttribute('data-filter') || '';
		if (!slug || slug === allSlug) {
			return '';
		}
		return slug;
	}

	function showCategoryDescription(slug) {
		var section = getSection();
		if (!section) {
			return;
		}

		var panels = section.querySelectorAll('.mp-category-description__panel');
		var hasVisible = false;

		panels.forEach(function (panel) {
			var panelSlug = panel.getAttribute('data-category-slug') || '';
			var match = slug && panelSlug === slug;
			if (match) {
				panel.removeAttribute('hidden');
				hasVisible = true;
			} else {
				panel.setAttribute('hidden', '');
			}
		});

		if (hasVisible) {
			section.removeAttribute('hidden');
		} else {
			section.setAttribute('hidden', '');
		}
	}

	function syncFromFilter() {
		showCategoryDescription(getActiveFilterSlug());
	}

	function bindFilterClicks() {
		var root = getFilterRoot();
		if (!root || root.dataset.mpCategoryDescBound === '1') {
			return;
		}
		root.dataset.mpCategoryDescBound = '1';
		root.addEventListener(
			'click',
			function (event) {
				var btn = event.target.closest('.e-filter-item');
				if (!btn || !root.contains(btn)) {
					return;
				}
				window.requestAnimationFrame(function () {
					window.requestAnimationFrame(syncFromFilter);
				});
			},
			true
		);
	}

	function observeFilterState() {
		var root = getFilterRoot();
		if (!root || !window.MutationObserver) {
			return;
		}
		var filterBar = root.querySelector('.e-filter');
		if (!filterBar || filterBar.dataset.mpCategoryDescObserved === '1') {
			return;
		}
		filterBar.dataset.mpCategoryDescObserved = '1';
		var observer = new MutationObserver(function () {
			syncFromFilter();
		});
		observer.observe(filterBar, {
			attributes: true,
			subtree: true,
			attributeFilter: ['aria-pressed', 'hidden'],
		});
	}

	function init() {
		if (!getSection()) {
			return;
		}
		showCategoryDescription(cfg.initialSlug || '');
		bindFilterClicks();
		observeFilterState();
	}

	/* Re-sync when loop cards re-init after Elementor taxonomy filter AJAX (shop-loop-filter.js). */
	$(document).on('biopentra-loop-cards-init', function () {
		syncFromFilter();
	});

	$(window).on('elementor/frontend/init', function () {
		init();
	});

	$(function () {
		init();
	});
})(window.jQuery);
