<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Payum\CurabillBundle\Extension;

use CoreShop\Bundle\PayumBundle\Model\GatewayConfig;
use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Currency\Model\CurrencyInterface;
use CoreShop\Component\Core\Model\CustomerInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\SaleItemInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Order\Repository\OrderRepositoryInterface;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Pimcore\Model\DataObject\Fieldcollection\Data\CoreShopTaxItem;

final class InvoiceTransformerExtension implements ExtensionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var array
     */
    protected $validLanguages = ['de', 'fr', 'it'];

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

        /** @var GatewayConfig $gatewayConfig */
        $gatewayConfig = $payment->getPaymentProvider()->getGatewayConfig();
        $gatewayData = $gatewayConfig->getConfig();

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $additionalData = [
            'birtdate'       => $details['birthdate'],
            'deliveryMethod' => $details['deliveryMethod'],
            'invoiceParty'   => $gatewayData['invoiceParty']
        ];

        $this->setInvoiceHeader($order, $request, $additionalData);
        $this->setInvoiceItems($order, $request);
        $this->setInvoiceFooter($order, $request);

    }

    /**
     * @param OrderInterface     $order
     * @param InvoiceTransformer $request
     * @param array              $additionalData
     */
    private function setInvoiceHeader(OrderInterface $order, InvoiceTransformer $request, $additionalData)
    {
        $orderDate = \Carbon\Carbon::createFromTimestamp($order->getCreationDate());

        $invoiceHeaderData = [
            'basicInformation' => [
                'documentType'           => $request->getDocumentType(),
                'documentNumber'         => $order->getId(),
                'documentDate'           => $orderDate->format('Y-m-d'),
                'documentCurrency'       => $order->getCurrency()->getIsoCode(),
                'orderNumberCustomer'    => $order->getOrderNumber(),
                'contractIdentification' => '',
                'invoiceDeliveryMethod'  => ucfirst($additionalData['deliveryMethod']),
            ]
        ];

        $invoicingParty = $this->generateInvoicingInformation($additionalData['invoiceParty']);
        if ($invoicingParty !== false) {
            $invoiceHeaderData['invoicingParty'] = $invoicingParty;
        }

        $billToParty = $this->generateBillToInformation($order, $additionalData['birtdate']);
        if ($billToParty !== false) {
            $invoiceHeaderData['billtoParty'] = $billToParty;
        }

        $deliveryInformation = $this->generateDeliveryInformation($order, $additionalData['birtdate']);
        if ($deliveryInformation !== false) {
            $invoiceHeaderData['deliveryInformation'] = $deliveryInformation;
        }

        $request->setInvoiceHeader($invoiceHeaderData);

    }

    /**
     * @param OrderInterface     $order
     * @param InvoiceTransformer $request
     */
    private function setInvoiceItems(OrderInterface $order, InvoiceTransformer $request)
    {
        $items = [];

        $order->getCurrency();

        /** @var SaleItemInterface $item */
        foreach ($order->getItems() as $item) {

            $invoicedPricePerUnitExclVat = $this->getDecimalPrice($item->getItemPrice(false), $order->getCurrency());
            $invoicedPricePerUnitInclVat = $this->getDecimalPrice($item->getItemPrice(true), $order->getCurrency());

            $taxAmount = $this->getDecimalPrice($item->getTotalTax(), $order->getCurrency());
            $totalAmountExclVat = $this->getDecimalPrice($item->getTotal(false), $order->getCurrency());
            $totalAmountInclVat = $this->getDecimalPrice($item->getTotal(true), $order->getCurrency());

            $items[] = [
                //'positionReference'          => null,
                'productQuantityInformation' => [
                    'description'      => $item->getName(),
                    'quantityUnit'     => 'Stk',
                    'invoicedQuantity' => $item->getQuantity(),
                ],
                'priceInformation'           => [
                    'invoicedPricePerUnitExclVat' => $invoicedPricePerUnitExclVat,
                    'invoicedPricePerUnitInclVat' => $invoicedPricePerUnitInclVat,
                    'vatRate'                     => '7.7',
                    //'reasonForTaxReduction' => '', //?
                    'taxBaseAmount'               => $totalAmountExclVat,
                    'taxAmount'                   => $taxAmount,
                    'totalAmount'                 => $totalAmountInclVat,
                ],
                //'additionalInformation'      => null,

            ];
        }

        // add shipment
        $carrier = $order->getCarrier();

        if ($carrier instanceof CarrierInterface) {
            $items[] = [
                'productQuantityInformation' => [
                    'description'      => $carrier->getTitle(),
                    'quantityUnit'     => 'Stk',
                    'invoicedQuantity' => 1,
                ],
                'priceInformation'           => [
                    'invoicedPricePerUnitExclVat' => $this->getDecimalPrice($order->getShipping(false), $order->getCurrency()),
                    'invoicedPricePerUnitInclVat' => $this->getDecimalPrice($order->getShipping(true), $order->getCurrency()),
                    'vatRate'                     => $order->getShippingTaxRate(),
                    'taxBaseAmount'               => $this->getDecimalPrice($order->getShipping(false), $order->getCurrency()),
                    'taxAmount'                   => $this->getDecimalPrice($order->getShippingTax(), $order->getCurrency()),
                    'totalAmount'                 => $this->getDecimalPrice($order->getShipping(true), $order->getCurrency()),
                ]
            ];
        }

        $request->setInvoiceItems($items);
    }

    /**
     * @param OrderInterface     $order
     * @param InvoiceTransformer $request
     */
    private function setInvoiceFooter(OrderInterface $order, InvoiceTransformer $request)
    {
        $taxes = $order->getTaxes();

        $taxInformation = [];
        /** @var CoreShopTaxItem $tax */
        foreach ($taxes as $tax) {

            $taxAmount = $this->getDecimalPrice($tax->getAmount(), $order->getCurrency());

            $taxInformation[] = [
                'vatInformation' => [
                    'vatRate'       => $tax->getRate(),
                    'taxBaseAmount' => $taxAmount,
                    'taxAmount'     => $taxAmount,
                ]
            ];
        }

        $orderTotalWithoutTax = $this->getDecimalPrice($order->getTotal(false), $order->getCurrency());
        $orderTotalWithTax = $this->getDecimalPrice($order->getTotal(true), $order->getCurrency());

        $request->setInvoiceFooter([
            [
                $taxInformation
            ],
            'invoiceTotals' => [
                //refundFlat => '', //?
                'orderTotalWithoutTax' => $orderTotalWithoutTax,
                'orderTotalWithTax'    => $orderTotalWithTax,
                //'instalmentTotalAmount' => '',  //?
                //'roundingDifference' => '',  //?
            ],
        ]);
    }

    /**
     * @param $invoiceData
     *
     * @return bool|array
     */
    private function generateInvoicingInformation($invoiceData)
    {
        if (!is_array($invoiceData) || count($this->removeNullValues($invoiceData)) === 0) {
            return false;
        }

        $invoicingParty = [
            'providerNumber'               => $invoiceData['providerNumber'], //?
            'customerSystemIdentification' => $invoiceData['customerSystemIdentification'] //?
        ];

        if (!empty($invoiceParty['vatNumber'])) {
            $invoicingParty['vatNumber'] = $invoiceData['vatNumber'];
        }

        if (!empty($invoiceData['companyAddress']['companyName'])) {
            $invoicingParty['companyAddress']['companyName'] = $invoiceData['companyAddress']['companyName'];
        }
        if (!empty($invoiceData['companyAddress']['street'])) {
            $invoicingParty['companyAddress']['street'] = $invoiceData['companyAddress']['street'];
        }
        if (!empty($invoiceData['companyAddress']['zip'])) {
            $invoicingParty['companyAddress']['zip'] = $invoiceData['companyAddress']['zip'];
        }
        if (!empty($invoiceData['companyAddress']['city'])) {
            $invoicingParty['companyAddress']['city'] = $invoiceData['companyAddress']['city'];
        }
        if (!empty($invoiceData['companyAddress']['country'])) {
            $invoicingParty['companyAddress']['country'] = $invoiceData['companyAddress']['country'];
        }
        if (!empty($invoiceData['companyAddress']['phoneNumber'])) {
            $invoicingParty['companyAddress']['phoneNumber'] = $invoiceData['companyAddress']['phoneNumber'];
        }
        if (!empty($invoiceData['companyAddress']['faxNumber'])) {
            $invoicingParty['companyAddress']['faxNumber'] = $invoiceData['companyAddress']['faxNumber'];
        }
        if (!empty($invoiceData['companyAddress']['mobileNumber'])) {
            $invoicingParty['companyAddress']['mobileNumber'] = $invoiceData['companyAddress']['mobileNumber'];
        }
        if (!empty($invoiceData['companyAddress']['email'])) {
            $invoicingParty['companyAddress']['email'] = $invoiceData['companyAddress']['email'];
        }
        if (!empty($invoiceData['contactPerson']['firstName'])) {
            $invoicingParty['contactPerson']['firstname'] = $invoiceData['contactPerson']['firstName'];
        }
        if (!empty($invoiceData['contactPerson']['firstName'])) {
            $invoicingParty['contactPerson']['lastname'] = $invoiceData['contactPerson']['lastName'];
        }
        if (!empty($invoiceData['contactPerson']['phoneNumber'])) {
            $invoicingParty['contactPerson']['phoneNumber'] = $invoiceData['contactPerson']['phoneNumber'];
        }
        if (!empty($invoiceData['contactPerson']['email'])) {
            $invoicingParty['contactPerson']['email'] = $invoiceData['contactPerson']['email'];
        }

        return $invoicingParty;
    }

    /**
     * @param OrderInterface $order
     * @param string         $birthday
     *
     * @return bool|array
     */
    private function generateBillToInformation(OrderInterface $order, $birthday)
    {
        /** @var CustomerInterface $customer */
        $customer = $order->getCustomer();

        /** @var AddressInterface $invoiceAddress */
        $invoiceAddress = $order->getInvoiceAddress();

        $billToParty = [];

        $billToParty['customerId'] = $customer->getId();
        $billToParty['language'] = $this->getLanguage($order);

        $addressType = 'privateAddress';
        $isCompany = method_exists($invoiceAddress, 'getTaxIdNumber') && !empty($invoiceAddress->getTaxIdNumber());

        if ($isCompany) {
            $addressType = 'companyAddress';
            $billToParty['vatNumber'] = $invoiceAddress->getTaxIdNumber();
            $billToParty[$addressType]['companyName'] = $invoiceAddress->getCompany();
        } else {
            if (!empty($invoiceAddress->getLastname())) {
                $billToParty[$addressType]['lastname'] = $invoiceAddress->getLastname();
            }
            if (!empty($invoiceAddress->getFirstname())) {
                $billToParty[$addressType]['firstname'] = $invoiceAddress->getFirstname();
            }
        }

        if (!empty($invoiceAddress->getStreet())) {
            $billToParty[$addressType]['street'] = $invoiceAddress->getStreet() . ' ' . $invoiceAddress->getNumber();
        }
        if (!empty($invoiceAddress->getPostcode())) {
            $billToParty[$addressType]['zip'] = $invoiceAddress->getPostcode();
        }
        if (!empty($invoiceAddress->getCity())) {
            $billToParty[$addressType]['city'] = $invoiceAddress->getCity();
        }
        if (!empty($invoiceAddress->getCountry())) {
            $billToParty[$addressType]['country'] = $invoiceAddress->getCountry()->getIsoCode();
        }

        if (!$isCompany && !empty($birthday)) {
            $billToParty[$addressType]['birthday'] = $birthday;
        }

        //$billToParty['checkAge'] = 'false';

        return $billToParty;

    }

    /**
     * @param OrderInterface $order
     * @param string         $birthday
     *
     * @return bool|array
     */
    private function generateDeliveryInformation(OrderInterface $order, $birthday)
    {
        /** @var AddressInterface $invoiceAddress */
        $invoiceAddress = $order->getInvoiceAddress();

        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        $deliveryInformation = [];

        $addressType = 'privateAddress';
        $shippingIsCompany = method_exists($shippingAddress, 'getTaxIdNumber') && !empty($shippingAddress->getTaxIdNumber());

        if ($shippingIsCompany) {
            $addressType = 'companyAddress';
            $deliveryInformation[$addressType]['companyName'] = $invoiceAddress->getCompany();
        } else {
            if (!empty($invoiceAddress->getLastname())) {
                $deliveryInformation[$addressType]['lastname'] = $shippingAddress->getLastname();
            }
            if (!empty($invoiceAddress->getFirstname())) {
                $deliveryInformation[$addressType]['firstname'] = $shippingAddress->getFirstname();
            }
        }

        if (!empty($shippingAddress->getStreet())) {
            $deliveryInformation[$addressType]['street'] = $shippingAddress->getStreet() . ' ' . $shippingAddress->getNumber();
        }
        if (!empty($shippingAddress->getPostcode())) {
            $deliveryInformation[$addressType]['zip'] = $shippingAddress->getPostcode();
        }
        if (!empty($shippingAddress->getCity())) {
            $deliveryInformation[$addressType]['city'] = $shippingAddress->getCity();
        }
        if (!empty($shippingAddress->getCountry())) {
            $deliveryInformation[$addressType]['country'] = $shippingAddress->getCountry()->getIsoCode();
        }
        if (!$shippingIsCompany && !empty($birthday)) {
            $deliveryInformation[$addressType]['birthday'] = $birthday;
        }

        return $deliveryInformation;
    }

    /***
     * @param $order
     *
     * @return string
     */
    private function getLanguage(OrderInterface $order)
    {
        $defaultLanguage = 'en';
        $gatewayOrderLanguage = $defaultLanguage;

        if (!empty($order->getLocaleCode())) {
            $orderLanguage = $order->getLocaleCode();
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
     * @param                   $amount
     * @param CurrencyInterface $currency
     *
     * @return float|int
     */
    private function getDecimalPrice($amount, CurrencyInterface $currency)
    {
        return abs($amount / 100);
    }

    /**
     * @param $array
     *
     * @return mixed
     */
    private function removeNullValues($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = $this->removeNullValues($value);
            }
        }
        return array_filter($array);
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
