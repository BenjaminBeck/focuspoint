<?php
/**
 * Override the ViewHelper with ratio
 *
 * @package Focuspoint\ViewHelpers
 * @author  Tim Lochmüller
 */

namespace HDNET\Focuspoint\ViewHelpers;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder;

/**
 * Override the ViewHelper with ratio
 *
 * @author Tim Lochmüller
 */
class ImageViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
{

    /**
     * Resize a given image (if required) and renders the respective img tag
     *
     * @see http://typo3.org/documentation/document-library/references/doc_core_tsref/4.2.0/view/1/5/#id4164427
     *
     * @param string                           $src                a path to a file, a combined FAL identifier or an uid (integer). If $treatIdAsReference is set, the integer is considered the uid of the sys_file_reference record. If you already got a FAL object, consider using the $image parameter instead
     * @param string                           $width              width of the image. This can be a numeric value representing the fixed width of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
     * @param string                           $height             height of the image. This can be a numeric value representing the fixed height of the image in pixels. But you can also perform simple calculations by adding "m" or "c" to the value. See imgResource.width for possible options.
     * @param integer                          $minWidth           minimum width of the image
     * @param integer                          $minHeight          minimum height of the image
     * @param integer                          $maxWidth           maximum width of the image
     * @param integer                          $maxHeight          maximum height of the image
     * @param boolean                          $treatIdAsReference given src argument is a sys_file_reference record
     * @param FileInterface|AbstractFileFolder $image              a FAL object
     * @param string                           $ratio
     * @param bool                             $realCrop
     *
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @return string Rendered tag
     */
    public function render(
        $src = null,
        $width = null,
        $height = null,
        $minWidth = null,
        $minHeight = null,
        $maxWidth = null,
        $maxHeight = null,
        $treatIdAsReference = false,
        $image = null,
        $ratio = '1:1',
        $realCrop = false
    ) {
        $internalImage = $this->getImage($src, $treatIdAsReference);
        if ($realCrop) {
            /** @var \HDNET\Focuspoint\Service\FocusCropService $service */
            $service = GeneralUtility::makeInstance('HDNET\\Focuspoint\\Service\\FocusCropService');
            $src = $service->getCroppedImageSrcByFile($internalImage, $ratio);
            $treatIdAsReference = false;
            $image = null;
        }

        parent::render($src, $width, $height, $minWidth, $minHeight, $maxWidth, $maxHeight, $treatIdAsReference, $image);

        if ($realCrop) {
            return $this->tag->render();
        }

        // Ratio calculation
        $focusPointY = $internalImage->getProperty('focus_point_y');
        $focusPointX = $internalImage->getProperty('focus_point_x');

        $focusTag = '<div class="focuspoint" data-image-imageSrc="' . $this->tag->getAttribute('src') . '" data-focus-x="' . ($focusPointX / 100) . '" data-focus-y="' . ($focusPointY / 100) . '" data-image-w="' . $this->tag->getAttribute('width') . '" data-image-h="' . $this->tag->getAttribute('height') . '">';
        return $focusTag . $this->tag->render() . '</div>';
    }

    /**
     * get the image
     *
     * @param $src
     * @param $treatIdAsReference
     *
     * @return \TYPO3\CMS\Core\Resource\File|FileInterface|\TYPO3\CMS\Core\Resource\FileReference|\TYPO3\CMS\Core\Resource\Folder
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException
     */
    protected function getImage($src, $treatIdAsReference)
    {
        $resourceFactory = ResourceFactory::getInstance();
        if (!MathUtility::canBeInterpretedAsInteger($src)) {
            return $resourceFactory->retrieveFileOrFolderObject($src);
        }
        if (!$treatIdAsReference) {
            return $resourceFactory->getFileObject($src);
        }
        $image = $resourceFactory->getFileReferenceObject($src);
        return $image->getOriginalFile();
    }
}
