<?php
/**
 * Auto PDF plugin for Craft CMS 3.x
 *
 * Seamlessly create PDF thumbnails using Craft's built in image transformer.
 *
 * @link      sitemill.co
 * @copyright Copyright (c) 2021 Sitemill
 */

namespace sitemill\autopdf;

use craft\elements\Asset;
use craft\events\AssetThumbEvent;
use craft\events\GetAssetUrlEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use sitemill\autopdf\models\Settings;
use sitemill\autopdf\services\AutoPdfService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Assets;
use craft\events\PluginEvent;
use craft\helpers\Assets as AssetsHelper;


use yii\base\Event;

/**
 * Class AutoPdf
 *
 * @author    Sitemill
 * @package   AutoPdf
 * @since     1.0.0
 *
 * @property  AutoPdfService $autoPdfService
 */
class AutoPdf extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var AutoPdf
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Do something on install
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Send them to our welcome screen
                    $request = Craft::$app->getRequest();
                    if ($request->isCpRequest) {
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl(
                            'settings/plugins/auto-pdf'
                        ))->send();
                    }
                }
            }
        );

        // Install our global event handlers
        $this->installEventHandlers();

        Craft::info(
            Craft::t(
                'auto-pdf',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new \sitemill\autopdf\models\Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'auto-pdf/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     *
     * Install our event handlers
     */
    protected function installEventHandlers()
    {
        Event::on(Assets::class,
            Assets::EVENT_GET_ASSET_URL,
            function(GetAssetUrlEvent $event) {
                $asset = $event->asset;
                $transform = $event->transform;
                if ($asset !== null && $transform !== null && $asset->kind === 'pdf' && $transform) {
                    // Get our counterpart
                    $counterpart = AutoPdf::$plugin->autoPdfService->getCounterpart($asset);
                    // Transform counterpart using standard asset transform
                    $event->url = $counterpart->getUrl($transform);
                }
            }
        );

        Event::on(Assets::class,
            Assets::EVENT_GET_THUMB_PATH,
            function(AssetThumbEvent $event) {
                $asset = $event->asset;
                if ($event->asset->kind === 'pdf') {
                    // Get our counterpart
                    $counterpart = AutoPdf::$plugin->autoPdfService->getCounterpart($asset);
                    // Get the width/height for CP thumb
                    if ($counterpart->getWidth() && $counterpart->getHeight()) {
                        [$width, $height] = \craft\helpers\Assets::scaledDimensions($counterpart->getWidth(), $counterpart->getHeight(), $event->width, $event->width);
                    } else {
                        $width = $height = $event->width;
                    }
                    // Transform counterpart using standard Craft transform
                    $event->path = Craft::$app->getAssets()->getThumbPath($counterpart, $width, $height);
                }
            }
        );

        if ($this->getSettings()->generatePdfOnAssetSave) {
            Event::on(
                Elements::class,
                Elements::EVENT_AFTER_SAVE_ELEMENT,
                function(Event $event) {
                    $element = $event->element;
                    if (($element instanceof \craft\elements\Asset) && $element->kind === 'pdf') {
                        // Trigger creation of counterpart
                        AutoPdf::$plugin->autoPdfService->getCounterpart($element);
                    }
                }
            );
        }
//        TODO: On delete asset, delete counterpart
    }

}
