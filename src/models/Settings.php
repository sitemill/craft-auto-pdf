<?php
/**
 * Auto PDF plugin for Craft CMS 3.x
 *
 * Seamlessly create PDF thumbnails using Craft's built in image transformer.
 *
 * @link      sitemill.co
 * @copyright Copyright (c) 2021 Sitemill
 */

namespace sitemill\autopdf\models;

use sitemill\autopdf\AutoPdf;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use craft\validators\ArrayValidator;


/**
 * @author    Sitemill
 * @package   AutoPdf
 * @since     1.0.0
 */
class Settings extends Model
{
    public $pdfVolumeId = '2';
    public $conversionOptions = '-density 72 -colorspace sRGB -background white';
}