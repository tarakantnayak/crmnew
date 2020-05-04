<?php
/**
 * Requirements validation.
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 3.0 (licenses/LicenseEN.txt or yetiforce.com)
 */
if (version_compare(PHP_VERSION, '7.1', '<')) {
	throw new \Exception('Wrong PHP version, recommended version >= 7.1');
}
if (isset($checkLibrary)) {
	$result = \App\Utils\ConfReport::getByType(['libraries']);
	foreach (\App\Utils\ConfReport::getByType(['libraries'])['libraries'] as $name => $value) {
		if (!empty($value['mandatory']) && !$value['status']) {
			throw new \Exception("The {$name} library is missing");
		}
	}
}
