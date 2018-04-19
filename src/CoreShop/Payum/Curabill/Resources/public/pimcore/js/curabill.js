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

    optionalFields: [],

    getLayout: function (config) {

        var storeEnvironments = new Ext.data.ArrayStore({
                fields: ['environment', 'environmentName'],
                data: [
                    ['test', 'Test'],
                    ['production', 'Production']
                ]
            });

        var optionalFields = [{
            xtype: 'label',
            anchor: '100%',
            style: 'display:block; padding:5px; background:#f5f5f5; border:1px solid #eee; font-weight: 300;',
            html: 'Parameter Cookbook: not available'
        }];

        var invoicePartyFields = [
            {
                xtype: 'label',
                anchor: '100%',
                style: 'display:block; padding:5px; background:#f5f5f5; border:1px solid #eee; font-weight: 300;',
                html: 'Optional. If you pass any invoice information here you need to fill out all the field.'
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.provider_number'),
                name: 'gatewayConfig.config.invoiceParty.providerNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.providerNumber ? config.invoiceParty.providerNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.customer_system_identification'),
                name: 'gatewayConfig.config.invoiceParty.customerSystemIdentification',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.customerSystemIdentification ? config.invoiceParty.customerSystemIdentification : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.vat_number'),
                name: 'gatewayConfig.config.invoiceParty.vatNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.vatNumber ? config.invoiceParty.vatNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.organisation_unit_name'),
                name: 'gatewayConfig.config.invoiceParty.organisationUnitName',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.organisationUnitName ? config.invoiceParty.organisationUnitName : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.contact_person.first_name'),
                name: 'gatewayConfig.config.invoiceParty.contactPerson.firstName',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.contactPerson.firstName ? config.invoiceParty.contactPerson.firstName : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.contact_person.last_name'),
                name: 'gatewayConfig.config.invoiceParty.contactPerson.lastName',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.contactPerson.lastName ? config.invoiceParty.contactPerson.lastName : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.contact_person.phone_number'),
                name: 'gatewayConfig.config.invoiceParty.contactPerson.phoneNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.contactPerson.phoneNumber ? config.invoiceParty.contactPerson.phoneNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.contact_person.email'),
                name: 'gatewayConfig.config.invoiceParty.contactPerson.email',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.contactPerson.email ? config.invoiceParty.contactPerson.email : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.company_name'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.companyName',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.companyName ? config.invoiceParty.companyAddress.companyName : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.street'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.street',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.street ? config.invoiceParty.companyAddress.street : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.zip'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.zip',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.zip ? config.invoiceParty.companyAddress.zip : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.city'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.city',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.city ? config.invoiceParty.companyAddress.city : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.country'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.country',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.country ? config.invoiceParty.companyAddress.country : ''
            }, {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.phone_number'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.phoneNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.phoneNumber ? config.invoiceParty.companyAddress.phoneNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.fax_number'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.faxNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.faxNumber ? config.invoiceParty.companyAddress.faxNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.mobile_number'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.mobileNumber',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.mobileNumber ? config.invoiceParty.companyAddress.mobileNumber : ''
            },
            {
                xtype: 'textfield',
                anchor: '100%',
                fieldLabel: t('curabill.config.invoice_party.company_address.email'),
                name: 'gatewayConfig.config.invoiceParty.companyAddress.email',
                length: 255,
                value: config.invoiceParty && config.invoiceParty.companyAddress.email ? config.invoiceParty.companyAddress.email : ''
            }
        ];

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
                anchor: '100%',
                flex: 1,
                forceSelection: true,
                selectOnFocus: true
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.username'),
                name: 'gatewayConfig.config.username',
                anchor: '100%',
                flex: 1,
                length: 255,
                value: config.username ? config.username : ''
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.transaction_token'),
                name: 'gatewayConfig.config.transactionToken',
                anchor: '100%',
                flex: 1,
                length: 255,
                value: config.transactionToken ? config.transactionToken : ''
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.response_token'),
                name: 'gatewayConfig.config.responseToken',
                anchor: '100%',
                flex: 1,
                length: 255,
                value: config.responseToken ? config.responseToken : ''
            },
            {
                xtype: 'textfield',
                fieldLabel: t('curabill.config.shop_code'),
                name: 'gatewayConfig.config.shopCode',
                anchor: '100%',
                flex: 1,
                length: 255,
                value: config.shopCode ? config.shopCode : ''
            },
            {
                xtype: 'fieldset',
                title: t('curabill.config.invoice_party_address'),
                collapsible: true,
                collapsed: true,
                autoHeight: true,
                labelWidth: 250,
                anchor: '100%',
                flex: 1,
                defaultType: 'textfield',
                items: invoicePartyFields
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
