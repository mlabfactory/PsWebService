<?php
require_once dirname(__FILE__) . '/../../classes/MlabFactoryApiBaseModuleFrontController.php';

class webserviceapicustomerModuleFrontController extends MlabFactoryApiBaseModuleFrontController
{
    protected function handleRequest()
    {
        $this->assertRequestMethod(array('POST'));

        $payload = MlabFactoryApiHelper::getCustomerPayload($this->getJsonPayload());
        MlabFactoryApiHelper::requireFields($payload, array('email', 'password', 'firstname', 'lastname'));

        $customer = MlabFactoryApiHelper::createCustomerFromPayload($payload, true);

        $addresses = array();
        if (!empty($payload['delivery_address']) && is_array($payload['delivery_address'])) {
            $deliveryAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, $payload['delivery_address']);
            $addresses['delivery'] = MlabFactoryApiHelper::serializeAddress($deliveryAddress);
        }

        if (!empty($payload['invoice_address']) && is_array($payload['invoice_address'])) {
            $invoiceAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, $payload['invoice_address']);
            $addresses['invoice'] = MlabFactoryApiHelper::serializeAddress($invoiceAddress);
        }

        return array(
            'message' => 'Customer created successfully.',
            'customer' => MlabFactoryApiHelper::serializeCustomer($customer),
            'addresses' => $addresses,
        );
    }
}
