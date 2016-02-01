<?php

namespace Stratis\SiteFactory\Utility;

/**
 * Less file compiler with custom vars overwrite
 * @todo: check if lessc exists for windows
 * @todo: create abstract class for multiples compilers
 * @todo: check if web process has rights to write into css dest dir
 */

class LessCompilerUtility {

	public function __construct() {

		if (exec('which lessc') == '') {
			// throw error: lessc is not installed
			die;
		}
	}

	/**
	* Parse vars to overwrite into a string
	*
	* @param array $vars
	* @return string
	*/
	protected function parse(array $vars) {

		$line = '';

		if (count($vars) == 0) {
			// throw info: no vars will be overwritten
		}

		foreach ($vars as $name => $value) {
			$line = $line . ' --modify-var="' . $name . '=' . $value . '"';
		}

		return $line;
	}

	/**
	* @param string    $source   Less source file
	* @param string    $dest     Css destination file
	* @param array     $vars     Vars to overwrite
	*/
	public function compile($source, $dest, $vars = array()) {

		if (! file_exists($source)) {
			// throw error: less file not found
			die;
		}

		if (file_exists($dest)) {
			// throw info: css file will be overwritten
		}

		// execute command-line
		exec('lessc ' .  $source . ' ' . $dest . $this->parse($vars));
	}
}
