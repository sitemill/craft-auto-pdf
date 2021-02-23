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
use sitemill\autopdf\AutoPdf;
use Spatie\PdfToImage\Pdf;

use Craft;
use craft\base\Component;
use Yii;
use yii\base\Exception;

/**
 * @author    Sitemill
 * @package   AutoPdf
 * @since     1.0.0
 */
class AutoPdfService extends Component
{

    public $settings = [];
    // Public Methods
    // =========================================================================

    public function __construct()
    {
        $this->settings = AutoPdf::$plugin->getSettings();
        parent::__construct();
    }

    /*
    * @return mixed
    */
    public function getCounterpart(Asset $asset)
    {
        // Get filename of source asset
        $filename = $asset->filename;

        // Build unique filename for counterpart
        $counterpartFilename = $filename . '-' . $asset->id . '.jpg';

        // Check for existing counterpart
        $counterpart = Asset::find()->filename($counterpartFilename)->one();
        if (!$counterpart) {
            $tempFile = $this->rasterizePdf($asset->getCopyOfFile(), $filename);
            $counterpart = $this->setCounterpart($tempFile, $counterpartFilename);
        }
        return $counterpart;
    }

    /*
    * @return mixed
    */
    private function setCounterpart($filePath, $filename)
    {
        $volumeId = $this->settings->pdfVolume;
        if ($volumeId) {
            $volume = Craft::$app->volumes->getVolumeById($this->settings->pdfVolume);
            $volumeRootFolder = Craft::$app->assets->getRootFolderByVolumeId($volume->id);
            $counterpart = new Asset();
            $counterpart->tempFilePath = $filePath;
            $counterpart->filename = $filename;
            $counterpart->folderId = $volumeRootFolder->id;
            return Craft::$app->getElements()->saveElement($counterpart);
        } else {
            throw new Exception(Craft::t('auto-pdf', 'No volume set for Auto PDF.'));
        }
    }

    /*
     * @return mixed
     */
    public function getSourcePdfPath(Asset $asset): string
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


    /*
     * @return mixed
     */
    public function rasterizePdf($sourcePath,$filename)
    {
        $destinationPath = $this->getTempPath($filename);
        App::maxPowerCaptain();
        $pdf = new Pdf($sourcePath);
        $pdf->setCompressionQuality($this->settings->compressionQuality);
        $pdf->setResolution($this->settings->dpi);
        $pdf->setColorspace(1);
        $pdf->saveImage($destinationPath);
        return $destinationPath;

    }

//  TODO: force re-rasterize on asset replace
}
