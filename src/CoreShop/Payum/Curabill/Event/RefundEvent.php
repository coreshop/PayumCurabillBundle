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

namespace CoreShop\Payum\CurabillBundle\Event;

use CoreShop\Component\Payment\Model\Payment;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use DachcomDigital\Payum\Curabill\Request\Api\Refund;
use Payum\Core\Payum;

class RefundEvent
{
    /**
     * @var Payum
     */
    protected $payum;

    /**
     * @var RepositoryInterface
     */
    protected $paymentRepository;

    /**
     * ConfirmEvent constructor.
     *
     * @param Payum               $payum
     * @param RepositoryInterface $paymentRepository
     */
    public function __construct(Payum $payum, RepositoryInterface $paymentRepository)
    {
        $this->payum = $payum;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param PaymentInterface $payment
     *
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function refund(PaymentInterface $payment)
    {
        if ($payment->getState() !== Payment::STATE_COMPLETED) {
            return;
        }

        $factoryName = $payment->getPaymentProvider()->getGatewayConfig()->getFactoryName();
        if ($factoryName !== 'curabill') {
            return;
        }

        $saferpay = $this->payum->getGateway('curabill');
        $saferpay->execute(new Refund($payment));

    }
}
