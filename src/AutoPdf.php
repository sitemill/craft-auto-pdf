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

use craft\events\GetAssetUrlEvent;
use craft\helpers\UrlHelper;
use sitemill\autopdf\models\Settings;
use sitemill\autopdf\services\AutoPdfService as AutoPdfServiceService;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Assets;
use craft\events\PluginEvent;


use yii\base\Event;

/**
 * Class AutoPdf
 *
 * @author    Sitemill
 * @package   AutoPdf
 * @since     1.0.0
 *
 * @property  AutoPdfServiceService $autoPdfService
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
        $settings = $this->getSettings();
        Event::on(Assets::class,
            Assets::EVENT_GET_ASSET_URL,
            function(GetAssetUrlEvent $event) {
                if ($event->asset !== null && $event->transform !== null && $event->asset->kind === 'pdf' && $event->transform) {


                    $event->url = AutoPdf::$plugin->autoPdfService->getPdfTransform($event->asset);


//                    var_dump($event->asset);



                }
            }

        );
    }

}
