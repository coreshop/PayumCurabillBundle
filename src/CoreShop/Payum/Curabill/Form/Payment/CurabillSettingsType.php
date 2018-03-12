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

namespace CoreShop\Payum\CurabillBundle\Form\Payment;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CurabillSettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('birthdate', BirthdayType::class, [
                'label'       => 'coreshop.payment.curabill.settings.birthdate',
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
            ])
            ->add('deliveryMethod', ChoiceType::class, [
                'label'       => 'coreshop.payment.curabill.settings.delivery_method',
                'constraints' => [
                    new NotBlank([
                        'groups' => 'coreshop',
                    ]),
                ],
                'choices'     => [
                    'coreshop.payment.curabill.settings.delivery_method.email'  => 'email',
                    'coreshop.payment.curabill.settings.delivery_method.postal' => 'postal'
                ]
            ]);

        $builder->get('birthdate')
            ->addModelTransformer(new CallbackTransformer(function ($data) {
                if (is_string($data)) {
                    return new \DateTime((string)$data);
                }
                return $data;
            }, function (\DateTime $data) {
                return $data->format('Y-m-d');
            }));
    }
}