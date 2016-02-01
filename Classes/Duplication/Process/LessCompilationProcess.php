<?php

// http://romain-canon.com/tickets/extension-typo3-usine-a-sites/
// https://github.com/romaincanon/TYPO3-Site-Factory/tree/master/Classes/Duplication/Process
// TODO: regenerate css files if less file is newer than css ones

namespace Stratis\SiteFactory\Duplication\Process;

use Romm\SiteFactory\Duplication\AbstractDuplicationProcess;
use Stratis\SiteFactory\Utility\LessCompilerUtility;

class LessCompilationProcess extends AbstractDuplicationProcess {

	public function run() {

		$lessc = new LessCompilerUtility();
		$extConf = $this->extensionConfiguration;

		$lessFile = PATH_site . $extConf['lessFile']['value'];
		$cssPath = PATH_site . $extConf['cssPath']['value'];

		$cssName = $extConf['cssName']['value'];
		$cssName = str_replace('{id}', $this->getDuplicatedPageUid(), $cssName);

		$variables = $extConf['variables']['value'];
		$variables = strlen($variables) ? explode(',', $variables) : array();

		if (! is_dir($cssPath)) {
			// throw error: css destination directory does not exists
			die;
		}

		// apply values from fields
		foreach ($this->getFieldsValues() as $key => $value) {
			if (in_array($key, $variables)) {
				$variables[ $key ] = $value;
			}
		}

		// run compilation
		$lessc->compile($lessFile, $cssPath . $cssName, $variables);
	}
}