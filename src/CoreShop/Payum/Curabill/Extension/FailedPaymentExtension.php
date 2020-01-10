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

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Capture;
use Symfony\Component\Translation\TranslatorInterface;

final class FailedPaymentExtension implements ExtensionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FailedPaymentExtension constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Context $context
     */
    public function onPostExecute(Context $context)
    {
        $action = $context->getAction();
        $previousActionClassName = get_class($action);

        if (false === stripos($previousActionClassName, 'CaptureAction')) {
            return;
        }

        /** @var Capture $request */
        $request = $context->getRequest();
        if (false === $request instanceof Capture) {
            return;
        }

        $details = ArrayObject::ensureArrayObject($request->getModel());
        if (!isset($details['transaction_error'])) {
            return;
        }

       $details['coreshop_payment_note'] = $this->translator->trans('curabill.ui.failed_message');

        $request->setModel($details);
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
