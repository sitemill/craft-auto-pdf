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

use sitemill\autopdf\AutoPdf;

use Imagine\Imagick\Imagick;

use Craft;
use craft\helpers\App;
use craft\elements\Asset;
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
    public function getCounterpart(Asset $asset, bool $generate = true)
    {

        // Build unique filename for counterpart
        $counterpartFilename = pathinfo($asset->filename, PATHINFO_FILENAME) . '-' . $asset->id . '.jpg';

        // Check for existing counterpart
        $counterpart = Asset::find()->filename($counterpartFilename)->one();

        // If not counterpart, let's make it
        if (!$counterpart && $generate && Craft::$app->volumes->getVolumeById($this->settings->pdfVolume)) {
            $tempPdf = $asset->getCopyOfFile();
            $tempImage = $this->rasterizePdf($tempPdf, $counterpartFilename);
            if (file_exists($tempImage)) {
                if ($this->setCounterpart($tempImage, $counterpartFilename)) {
                    unlink($tempPdf);
                    $counterpart = Asset::find()->filename($counterpartFilename)->one();
                }
            } else {
                throw new Exception(Craft::t('auto-pdf', 'Temporary file does not exist.'));
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
        $counterpart->folderId = Craft::$app->assets->getRootFolderByVolumeId($this->settings->pdfVolume)->id;
        return Craft::$app->getElements()->saveElement($counterpart);
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
        return $tempFolder . '/' . $filename;
    }

    /*
    * @return mixed
    */
    public function rasterizePdf($sourcePath, $filename)
    {
        $destinationPath = $this->getTempPath($filename);
        App::maxPowerCaptain();
        $im = new Imagick();
        $im->setResolution($this->settings->resolution, $this->settings->resolution);
        $im->setBackgroundColor('white');
        $im->readimage($sourcePath . '[0]');
        $im->setGravity(Imagick::GRAVITY_CENTER);
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $im->setImageCompressionQuality($this->settings->compressionQuality);
        $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $im->writeImage($this->getTempPath($filename));
        $im->clear();
        $im->destroy();
        return $destinationPath;
    }

//  TODO: force re-rasterize on asset replace
}
