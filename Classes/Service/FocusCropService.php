<?php
/**
 * Crop images via focus crop
 *
 * @package Focuspoint\Service
 * @author  Tim Lochmüller
 */

namespace HDNET\Focuspoint\Service;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Crop images via focus crop
 *
 * @author Tim Lochmüller
 */
class FocusCropService extends AbstractService
{

    /**
     * Graphical functions
     *
     * @var \TYPO3\CMS\Core\Imaging\GraphicalFunctions
     * @inject
     */
    protected $graphicalFunctions;

    /**
     * Get the cropped image
     *
     * @param string $fileReference
     * @param string $ratio
     *
     * @return string The new filename
     */
    public function getCroppedImageSrcByFileReference($fileReference, $ratio)
    {
        if ($fileReference instanceof FileReference) {
            $fileReference = $fileReference->getOriginalResource();
        }
        if ($fileReference instanceof \TYPO3\CMS\Core\Resource\FileReference) {
            return $this->getCroppedImageSrcByFile($fileReference->getOriginalFile(), $ratio);
        }
        throw new \InvalidArgumentException('The given argument is not a valid file reference', 123671283);
    }

    /**
     * Get the cropped image by File Object
     *
     * @param FileInterface $file
     * @param string        $ratio
     *
     * @return string The new filename
     */
    public function getCroppedImageSrcByFile(FileInterface $file, $ratio)
    {
        $absoluteImageName = GeneralUtility::getFileAbsFileName($file->getPublicUrl());
        $focusPointX = MathUtility::forceIntegerInRange((int)$file->getProperty('focus_point_x'), -100, 100, 0);
        $focusPointY = MathUtility::forceIntegerInRange((int)$file->getProperty('focus_point_y'), -100, 100, 0);

        $tempImageFolder = 'typo3temp/focuscrop/';
        $tempImageName = $tempImageFolder . $file->getSha1() . '-' . str_replace(':', '-',
                $ratio) . '-' . $focusPointX . '-' . $focusPointY . '.' . $file->getExtension();
        $absoluteTempImageName = GeneralUtility::getFileAbsFileName($tempImageName);
        if (is_file($absoluteTempImageName)) {
            return $tempImageName;
        }

        $absoluteTempImageFolder = GeneralUtility::getFileAbsFileName($tempImageFolder);
        if (!is_dir($absoluteTempImageFolder)) {
            GeneralUtility::mkdir_deep($absoluteTempImageFolder);
        }

        $this->graphicalFunctions = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');

        $imageSizeInformation = getimagesize($absoluteImageName);
        $width = $imageSizeInformation[0];
        $height = $imageSizeInformation[1];

        // dimensions
        /** @var \HDNET\Focuspoint\Service\DimensionService $service */
        $dimensionService = GeneralUtility::makeInstance('HDNET\\Focuspoint\\Service\\DimensionService');
        list($focusWidth, $focusHeight) = $dimensionService->getFocusWidthAndHeight($width, $height, $ratio);
        $cropMode = $dimensionService->getCropMode($width, $height, $ratio);
        list($sourceX, $sourceY) = $dimensionService->calculateSourcePosition($cropMode, $width, $height, $focusWidth,
            $focusHeight, $focusPointX, $focusPointY);

        // generate image
        $sourceImage = $this->graphicalFunctions->imageCreateFromFile($absoluteImageName);
        $destinationImage = imagecreatetruecolor($focusWidth, $focusHeight);
        $this->graphicalFunctions->imagecopyresized($destinationImage, $sourceImage, 0, 0, $sourceX, $sourceY, $focusWidth,
            $focusHeight, $focusWidth, $focusHeight);
        $this->graphicalFunctions->ImageWrite($destinationImage, $absoluteTempImageName,
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['jpg_quality']);
        return $tempImageName;
    }
}
