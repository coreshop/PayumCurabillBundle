/*
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 *
 */

pimcore.registerNS('coreshop.provider.gateways.curabill');
coreshop.provider.gateways.curabill = Class.create(coreshop.provider.gateways.abstract, {

    optionalFields: [

    ],

    getLayout: function (config) {

        var storeEnvironments = new Ext.data.ArrayStore({
            fields: ['environment', 'environmentName'],
            data: [
                ['test', 'Test'],
                ['production', 'Production']
            ]
        }),
            paymentMethodsStore = new Ext.data.ArrayStore({
            fields: ['paymentMethod', 'paymentMethodName'],
            data: [
                ['invoice', 'Invoice'],
                ['curapay', 'Curapay'],
                ['instalment_payment', 'Instalment Payment']
            ]
        });

        var optionalFields = [{
            xtype: 'label',
            anchor: '100%',
            style: 'display:block; padding:5px; background:#f5f5f5; border:1px solid #eee; font-weight: 300;',
            html: 'Parameter Cookbook: not available'
        }];

        Ext.Array.each(this.optionalFields, function (field) {
            var value = config.optionalParameters && config.optionalParameters[field] ? config.optionalParameters[field] : '';
            optionalFields.push({
                xtype: 'textfield',
                fieldLabel: field,
                name: 'gatewayConfig.config.optionalParameters.' + field,
                length: 255,
                flex: 1,
                labelWidth: 250,
                anchor: '100%',
                value: value
            })
        });

        return [
            {
                xtype: 'combobox',
                fieldLabel: t('curabill.config.environment'),
                name: 'gatewayConfig.config.environment',
                value: config.environment ? config.environment : '',
                store: storeEnvironments,
                triggerAction: 'all',
                valueField: 'environment',
                displayField: 'environmentName',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true
            },
             {
                xtype: 'combobox',
                fieldLabel: t('curabill.config.payment_method'),
                name: 'gatewayConfig.config.paymentMethod',
                value: config.paymentMethods ? config.paymentMethods : '',
                store: paymentMethodsStore,
                triggerAction: 'all',
                valueField: 'paymentMethod',
                displayField: 'paymentMethodName',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.username'),
                name: 'gatewayConfig.config.username',
                length: 255,
                value: config.username ? config.username : ''
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.transaction_token'),
                name: 'gatewayConfig.config.transactionToken',
                length: 255,
                value: config.transactionToken ? config.transactionToken : ''
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.shop_code'),
                name: 'gatewayConfig.config.shopCode',
                length: 255,
                value: config.shopCode ? config.shopCode : ''
            },
            {
                xtype: 'fieldset',
                title: t('curabill.config.optional_parameter'),
                collapsible: true,
                collapsed: true,
                autoHeight: true,
                labelWidth: 250,
                anchor: '100%',
                flex: 1,
                defaultType: 'textfield',
                items: optionalFields
            }
        ];
    }
});
