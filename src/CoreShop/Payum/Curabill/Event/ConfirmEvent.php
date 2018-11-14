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

namespace CoreShop\Payum\CurabillBundle\Event;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Payment\Model\Payment;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use DachcomDigital\Payum\Curabill\Request\Api\Confirm;
use Payum\Core\Payum;

class ConfirmEvent
{
    /**
     * @var Payum
     */
    protected $payum;

    /**
     * @var PaymentRepositoryInterface
     */
    protected $paymentRepository;

    /**
     * ConfirmEvent constructor.
     *
     * @param Payum                      $payum
     * @param PaymentRepositoryInterface $paymentRepository
     */
    public function __construct(Payum $payum, PaymentRepositoryInterface $paymentRepository)
    {
        $this->payum = $payum;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param OrderInterface $order
     *
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function confirm(OrderInterface $order)
    {
        $payments = $this->paymentRepository->findForPayable($order);

        $payment = null;
        /** @var PaymentInterface $orderPayment */
        foreach ($payments as $orderPayment) {
            $factoryName = $orderPayment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
            if ($factoryName === 'curabill') {
                $payment = $orderPayment;
                break;
            }
        }

        if (!$payment instanceof PaymentInterface) {
            return;
        }

        if ($payment->getState() !== Payment::STATE_COMPLETED) {
            return;
        }

        $curabill = $this->payum->getGateway('curabill');
        $curabill->execute(new Confirm($payment));

    }
}