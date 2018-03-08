<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\CurabillBundle\Extension;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;

final class InvoiceTransformerExtension implements ExtensionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var array
     */
    protected $validLanguages = ['en', 'de', 'fr', 'it'];

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Context $context
     */
    public function onPostExecute(Context $context)
    {
        $action = $context->getAction();

        $previousActionClassName = get_class($action);

        if (false === stripos($previousActionClassName, 'InvoiceTransformerAction')) {
            return;
        }

        /** @var InvoiceTransformer $request */
        $request = $context->getRequest();

        if (false === $request instanceof InvoiceTransformer) {
            return;
        }

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        if (false === $payment instanceof PaymentInterface) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $this->orderRepository->find($payment->getOrderId());

        $this->setInvoiceHeader($order, $request);
        $this->setInvoiceItems($order, $request);
        $this->setInvoiceFooter($order, $request);

    }

    /**
     * @param OrderInterface     $order
     * @param InvoiceTransformer $request
     */
    private function setInvoiceHeader($order, InvoiceTransformer $request)
    {
        $customer = $order->getCustomer();
        $address = $order->getInvoiceAddress();
        /** @var \CoreShop\Component\Core\Model\Country $country */
        $country = $address->getCountry();

//        $request->setInvoiceHeader($customer->getGender());
//        $request->setEmail($customer->getEmail());
//        $request->setFirstName($address->getFirstname());
//        $request->setLastName($address->getLastName());
//        $request->setStreet($address->getStreet() . ' ' . $address->getNumber());
//        $request->setCity($address->getCity());
//        $request->setZip($address->getPostcode());
//        $request->setCountry($country->getIsoCode());
    }

    private function setInvoiceItems($order, InvoiceTransformer $request)
    {
        //$request->setInvoiceItems([]);
    }

    private function setInvoiceFooter($order, InvoiceTransformer $request)
    {
        //$request->setInvoiceFooter([]);
    }

    /***
     * @param $order
     * @param $request
     * @return string
     */
    private function getLanguage(OrderInterface $order, $request)
    {
        $defaultLanguage = 'en';
        $gatewayOrderLanguage = $defaultLanguage;

        if (!empty($order->getOrderLanguage())) {
            $orderLanguage = $order->getOrderLanguage();
            if (strpos($orderLanguage, '_') !== false) {
                $orderLanguage = explode('_', $orderLanguage);
                $gatewayOrderLanguage = $orderLanguage[0];
            } else {
                $gatewayOrderLanguage = $orderLanguage;
            }
        }

        if (!in_array($gatewayOrderLanguage, $this->validLanguages)) {
            $gatewayOrderLanguage = $defaultLanguage;
        }

        return strtolower($gatewayOrderLanguage);

    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context)
    {
    }
}
