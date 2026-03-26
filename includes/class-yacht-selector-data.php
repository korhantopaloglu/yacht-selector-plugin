<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Data {

	public function get_sample_data() {
		return [
			[
				'name' => 'Sample Yacht',
				'length' => '24m',
				'price' => '€1200/day',
			],
		];
	}
}