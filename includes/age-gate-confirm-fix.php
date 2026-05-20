<?php
/**
 * Age Gate (JS mode): sync hidden age_gate[confirm] from the actual submitter before the bundled script
 * builds FormData — avoids the last duplicate key winning as an empty value (Yes treated as failure
 * and sent to the “failed” redirect URL).
 *
 * Also limits the failure redirect to explicit “under 18” (confirm === 0) so other errors do not
 * send users to the restricted page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_print_footer_scripts',
	static function () {
		if ( ! wp_script_is( 'age-gate', 'enqueued' ) ) {
			return;
		}
		$js = <<<'JS'
(function () {
	document.addEventListener(
		'submit',
		function (e) {
			var form = e.target;
			if (!form || !form.matches('.age-gate__form, .age-gate-form')) {
				return;
			}
			var hidden = form.querySelector('input[name="age_gate[confirm]"]');
			var sub = e.submitter;
			if (!hidden || !sub || sub.type !== 'submit') {
				return;
			}
			var v = sub.value;
			if ((v === '' || v == null) && sub.dataset && sub.dataset.submit) {
				v = sub.dataset.submit === 'yes' ? '1' : sub.dataset.submit === 'no' ? '0' : v;
			}
			if (sub.getAttribute('name') === 'age_gate[confirm]' && v !== '' && v != null) {
				hidden.value = String(v);
			}
		},
		true
	);
})();
JS;
		wp_add_inline_script( 'age-gate', $js, 'before' );
	},
	1
);

add_filter(
	'age_gate/failed/redirect',
	static function ( $url, $data ) {
		unset( $data );
		$ag = array();
		if ( isset( $_REQUEST['age_gate'] ) && is_array( $_REQUEST['age_gate'] ) ) {
			$ag = wp_unslash( $_REQUEST['age_gate'] );
		}
		$confirm = $ag['confirm'] ?? null;
		if ( $confirm === '0' || $confirm === 0 ) {
			return $url;
		}
		return '';
	},
	10,
	2
);
