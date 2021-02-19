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

    public $sourceAsset = '';
    public $sourcePath = '';
    public $destinationFolderId = '';

    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getPdfTransform(Asset $asset)
    {
        $this->sourceAsset = $asset;

        // Get the PDF's raster
        $sourceJpg = $this->createCounterpart();

        return 'hi';
    }

    /*
     * @return mixed
     */
    public function createCounterpart()
    {

        $folderId = Craft::$app->assets->getRootFolderByVolumeId(2)->id;
        $rasterizedPdfPath = $this->rasterizePdf($this->sourceAsset);
        $counterpart = new Asset();
        $counterpart->tempFilePath = $rasterizedPdfPath;
        $counterpart->filename = 'poop.jpg';
        $counterpart->folderId = $folderId;
        $counterpart->volumeId = 2;
        Craft::$app->getElements()->saveElement($counterpart);
    }

    /*
     * @return mixed
     */
    public function getSourcePdfPath()
    {
        $volumePath = Yii::getAlias($this->sourceAsset->getVolume()->path);
        $folderPath = $this->sourceAsset->getPath();
        return $volumePath . '/' . $folderPath;
    }

    /*
     * @return mixed
     */
    public function getTempPath($filename)
    {
        $tempFolder = Craft::$app->path->getTempPath();
        return $tempFolder . '/' . $filename . '.jpg';
    }

//    /*
//     * @return mixed
//     */
//    public function getFileNameWithoutExtension(Asset $asset) {
//        $filename = $asset->filename;
//        return pathinfo($filename)[]
//    }

    /*
     * @return mixed
     */
    private function rasterizePdf()
    {
        $sourcePath = $this->getSourcePdfPath($this->sourceAsset);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $tempPath = $this->getTempPath($filename);

        $im = new Imagick();
        $im->setResolution(144, 144);
        $im->SetColorspace(Imagick::COLORSPACE_SRGB);
        $im->readimage($sourcePath);
        $im->setImageFormat('jpeg');
        $im->writeImage($tempPath);
        $im->clear();
        $im->destroy();

        return $tempPath;
    }
}
