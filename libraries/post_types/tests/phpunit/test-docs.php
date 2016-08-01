<?php

class Extended_CPT_Test_Docs extends \Johnbillion\DocsStandards\TestCase {

	protected function getTestFunctions() {
		return array(
			'cs_post_type',
		);
	}

	protected function getTestClasses() {
		return array(
			'Extended_CPT',
			'Extended_CPT_Admin',
		);
	}

}
