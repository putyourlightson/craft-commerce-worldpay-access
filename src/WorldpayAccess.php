<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\worldpayaccess;

use craft\base\Plugin;
use putyourlightson\worldpayaccess\gateways\Gateway;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;

class WorldpayAccess extends Plugin
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES,  function(RegisterComponentTypesEvent $event) {
            $event->types[] = Gateway::class;
        });
    }
}
