<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\worldpayaccess\gateways;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\omnipay\base\CreditCardGateway;
use craft\commerce\Plugin;
use craft\web\View;
use Omnipay\Common\AbstractGateway;
use Omnipay\WorldpayAccess\Gateway as OmnipayGateway;
use Omnipay\WorldpayAccess\Message\RefundRequest;

/**
 * @property string $settingsHtml
 */
class Gateway extends CreditCardGateway
{
    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $checkoutId;

    /**
     * @var bool
     */
    public $testMode = true;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('worldpay-access', 'Worldpay Access');
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $defaults = [
            'paymentForm' => $this->getPaymentFormModel()
        ];

        $params = array_merge($defaults, $params);

        return Craft::$app->getView()->renderTemplate(
            'commerce/_components/gateways/_creditCardFields', $params, View::TEMPLATE_MODE_CP
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('worldpay-access/_settings', [
            'gateway' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [['username', 'password', 'checkoutId'], 'required'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function createGateway(): AbstractGateway
    {
        /** @var OmnipayGateway $gateway */
        $gateway = static::createOmnipayGateway($this->getGatewayClassName());

        $gateway->setUsername(Craft::parseEnv($this->username));
        $gateway->setPassword(Craft::parseEnv($this->password));
        $gateway->setCheckoutId(Craft::parseEnv($this->checkoutId));

        // Set entity reference to the current site handle
        $gateway->setEntityReference(Craft::$app->getSites()->getCurrentSite()->handle);

        return $gateway;
    }

    /**
     * @inheritdoc
     */
    protected function createPaymentRequest(Transaction $transaction, $card = null, $itemBag = null): array
    {
        $request = parent::createPaymentRequest($transaction, $card, $itemBag);
        $request['testMode'] = $this->testMode;

        return $request;
    }

    /**
     * @inheritdoc
     */
    protected function createRequest(Transaction $transaction, BasePaymentForm $form = null)
    {
        $request = parent::createRequest($transaction, $form);
        $request['testMode'] = $this->testMode;

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        $request = $this->createRequest($transaction);

        /** @var RefundRequest $refundRequest */
        $refundRequest = $this->prepareRefundRequest($request, $transaction->reference);

        // Set payment response links from parent
        $parent = Plugin::getInstance()->getTransactions()->getTransactionById($transaction->parentId);

        if ($parent !== null) {
            $response = json_decode($parent->response, true);
            $refundRequest->setPurchaseResponseLinks($response['_links'] ?? []);
        }

        return $this->performRequest($refundRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    protected function getGatewayClassName()
    {
        return 'WorldpayAccess';
    }
}
