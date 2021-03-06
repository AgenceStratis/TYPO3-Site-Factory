<?php
namespace Romm\SiteFactory\Duplication\Process;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Romain CANON <romain.canon@exl-group.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Romm\SiteFactory\Duplication\AbstractDuplicationProcess;

/**
 * Class containing functions called when a site is being duplicated.
 * See function "run" for more information.
 */
class UploadedFilesProcess extends AbstractDuplicationProcess {
	/**
	 * Gets all the fields which contains files, and upload them to the given
	 * file mount.
	 */
	public function run() {
		/** @var \Romm\SiteFactory\Form\Fields\AbstractField[] $filesFields */
		$filesFields = array();
		foreach ($this->getFields() as $field)
			if ($field->getSettings('moveToFileMount') && $field->getValue() != '') {
				if (substr($field->getValue(), 0, 4) == 'new:') {
					$field->setValue(substr($field->getValue(), 4, strlen($field->getValue()) - 4));
					$filesFields[] = $field;
				}
			}

		if (!empty($filesFields)) {
			$fileMountUid = $this->getDuplicationData('fileMountUid');

			if ($fileMountUid) {
				/** @var \TYPO3\CMS\Extbase\Domain\Repository\FileMountRepository $fileMountRepository */
				$fileMountRepository = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FileMountRepository');

				/** @var \TYPO3\CMS\Extbase\Domain\Model\FileMount $fileMount */
				$fileMount = $fileMountRepository->findByUid($fileMountUid);
				if ($fileMount) {
					$filesMoved = array();

					/** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
					$resourceFactory = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
					$storage = $resourceFactory->getDefaultStorage();

					/** @var \TYPO3\CMS\Core\Resource\Folder $folder */
					$folderPath =  substr($fileMount->getPath(), 1, strlen($fileMount->getPath()));
					$folder = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\Folder', $storage, $folderPath, 'SiteFactory');

					/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
					$driver = $resourceFactory->getDriverObject($storage->getDriverType(), $storage->getConfiguration());
					$driver->processConfiguration();

					foreach ($filesFields as $field) {
						$name = $field->getName();
						$path = $field->getValue();
						$fileExtension = substr(strrchr($path, '.'), 1);
						$identifier = $folderPath . $name . '.' . $fileExtension;

						if (file_exists($path)) {
							/** @var \TYPO3\CMS\Core\Resource\File $file */
							if ($driver->fileExists($identifier)) {
								$file = $storage->getFile($identifier);
								$storage->replaceFile($file, $path);

								/** @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository $processedFileRepository */
								$processedFileRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
								/** @var \TYPO3\CMS\Core\Resource\ProcessedFile[] $processedFiles */
								$processedFiles = $processedFileRepository->findAllByOriginalFile($file);

								foreach($processedFiles as $processedFile)
									$processedFile->delete();
							}
							else
								$file = $storage->addFile($path, $folder, $name . '.' . $fileExtension, 'replace');

							$this->getField($field->getName())->setValue($driver->getPublicUrl($identifier));
							$filesMoved[$name] = $file->getName();
						}
					}

					if (!empty($filesMoved)) {
						$this->addNotice(
							'duplication_process.uploaded_files.notice.success',
							1435421057,
							array($folder->getPublicUrl(), '"' . implode('", ', $filesMoved) . '"')
						);
					}
				}
			}
		}
	}
}
