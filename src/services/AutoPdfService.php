<?php
/**
 * Auto PDF plugin for Craft CMS 3.x
 *
 * Seamlessly create PDF thumbnails using Craft's built in image transformer.
 *
 * @link      sitemill.co
 * @copyright Copyright (c) 2021 Sitemill
 */

namespace sitemill\autopdf\services;

use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Assets;
use Imagick;
use sitemill\autopdf\AutoPdf;

use Craft;
use craft\base\Component;
use Yii;

/**
 * @author    Sitemill
 * @package   AutoPdf
 * @since     1.0.0
 */
class AutoPdfService extends Component
{

    public $sourceAsset = null;
    public $sourcePath = '';
    public $filename = '';
    public $tempPath = '';
    public $destinationFolderId = '';

    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getPdfTransform(Asset $asset, $transform)
    {
        // Get the counterpart asset
        $counterpart = $this->getCounterpart($asset);

        // Return the transform
        return $counterpart->getUrl($transform);
    }

    /*
     * @return mixed
     */
    public function getPdfThumb($event)
    {
        // Get the counterpart asset
        $counterpart = $this->getCounterpart($event->asset);

        if ($counterpart->getWidth() && $counterpart->getHeight()) {
            [$width, $height] = Assets::scaledDimensions($counterpart->getWidth(), $counterpart->getHeight(), $event->width, $event->width);
        } else {
            $width = $height = $event->width;
        }

        // Return the transform
        return Craft::$app->getAssets()->getThumbPath($counterpart, $width, $height);
    }

    /*
    * @return mixed
    */
    public function getCounterpart(Asset $asset)
    {
        // Get full path and filename of source asset
        $sourcePath = $this->getSourcePdfPath($asset);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);

        // Build unique filename for counterpart
        $counterpartFilename = $filename . '-' . $asset->id . '.jpg';

        // Check for existing counterpart
        $counterpart = Asset::find()->filename($counterpartFilename)->one();
        if (!$counterpart) {
            $tempPath = $this->getTempPath($filename);
            if ($this->rasterizePdf($sourcePath, $tempPath)) {
                $counterpart = $this->setCounterpart($tempPath, $counterpartFilename);
            }
        }

        return $counterpart;
    }

    /*
    * @return mixed
    */
    private function setCounterpart($filePath, $filename)
    {
        $counterpart = new Asset();
        $counterpart->tempFilePath = $filePath;
        $counterpart->filename = $filename;
        $counterpart->folderId = AutoPdf::$plugin->getSettings()->pdfFolderId;
        return Craft::$app->getElements()->saveElement($counterpart);
    }

    /*
     * @return mixed
     */
    public function getSourcePdfPath(Asset $asset)
    {
        $volumePath = $asset->getVolume()->settings['path'];
        $folderPath = $asset->getFolder()->path;
        return Yii::getAlias($volumePath) . $folderPath . '/' . $asset->filename;
    }

    /*
     * @return mixed
     */
    public function getTempPath($filename)
    {
        $tempFolder = Craft::$app->path->getTempPath();
        return $tempFolder . '/' . $filename . '.jpg';
    }

    /**
     * Returns the attached asset.
     */
    public function getSourceAsset()
    {
        if ($this->sourceAsset !== null) {
            return $this->sourceAsset;
        }

        if ($this->assetId === null) {
            return null;
        }

        return $this->_file;
    }

    /**
     * Sets the attached asset.
     */
    public function setSourceAsset(Asset $asset = null)
    {
        $this->sourceAsset = $asset;
    }


    /*
     * @return mixed
     */
    private function rasterizePdf($sourcePath, $destPath)
    {
        App::maxPowerCaptain();
        $im = new Imagick();
        $im->setBackgroundColor('white');
        $im->setResolution(144, 144);
        $im->SetColorspace(Imagick::COLORSPACE_SRGB);
        $im->readimage($sourcePath);
        $im->setImageFormat('jpeg');
        $im->writeImage($destPath);
        $im->clear();
        $im->destroy();
        return true;
    }


}
