<?php
require_once dirname(__FILE__) . '/../../classes/MlabFactoryApiBaseModuleFrontController.php';

class webserviceapicontactModuleFrontController extends MlabFactoryApiBaseModuleFrontController
{
    protected function handleRequest()
    {
        $this->assertRequestMethod(array('POST'));

        $payload = $this->getJsonPayload();
        MlabFactoryApiHelper::requireFields($payload, array('email', 'message'));

        $email = trim((string) $payload['email']);
        if (!Validate::isEmail($email)) {
            throw new MlabFactoryApiException('Invalid email address.', 422);
        }

        $message = trim((string) $payload['message']);
        if ($message === '') {
            throw new MlabFactoryApiException('Message cannot be empty.', 422);
        }

        if (Tools::strlen($message) > 2000) {
            throw new MlabFactoryApiException('Message is too long.', 422, array('max_length' => 2000));
        }

        $contact = $this->resolveContact($payload);
        $languageId = $this->getContextLanguageId();
        $shopId = $this->getContextShopId();
        $customer = $this->resolveCustomer($payload, $email);
        $order = $this->resolveOrder($payload, $customer);
        $subject = trim((string) $this->getPayloadValue($payload, 'subject', 'API contact request'));
        $firstname = trim((string) $this->getPayloadValue($payload, 'firstname', $customer ? (string) $customer->firstname : ''));
        $lastname = trim((string) $this->getPayloadValue($payload, 'lastname', $customer ? (string) $customer->lastname : ''));

        $thread = $this->createCustomerThread($contact, $customer, $order, $email, $subject, $languageId, $shopId);
        $this->createCustomerMessage($thread, $message);
        $this->notifyContact($contact, $thread, $customer, $order, $email, $firstname, $lastname, $subject, $message, $languageId, $shopId);

        return array(
            'message' => 'Contact request sent successfully.',
            'contact' => array(
                'id_contact' => (int) $contact['id_contact'],
                'name' => (string) $contact['name'],
                'email' => (string) $contact['email'],
            ),
            'thread' => array(
                'id_customer_thread' => (int) $thread->id,
                'token' => (string) $thread->token,
            ),
        );
    }

    protected function resolveContact(array $payload)
    {
        $idContact = (int) $this->getPayloadValue($payload, 'id_contact', 0);

        if ($idContact > 0) {
            $row = Db::getInstance()->getRow(
                'SELECT c.`id_contact`, cl.`name`, c.`email`
                FROM `' . _DB_PREFIX_ . 'contact` c
                LEFT JOIN `' . _DB_PREFIX_ . 'contact_lang` cl ON (
                    cl.`id_contact` = c.`id_contact`
                    AND cl.`id_lang` = ' . (int) $this->getContextLanguageId() . '
                )
                WHERE c.`id_contact` = ' . (int) $idContact
            );

            if (!empty($row)) {
                return $row;
            }

            throw new MlabFactoryApiException('Contact not found.', 404, array('id_contact' => $idContact));
        }

        $contacts = Contact::getContacts($this->getContextLanguageId());
        if (empty($contacts)) {
            throw new MlabFactoryApiException('No contact recipients are configured.', 500);
        }

        $contact = reset($contacts);

        return array(
            'id_contact' => isset($contact['id_contact']) ? (int) $contact['id_contact'] : 0,
            'name' => isset($contact['name']) ? (string) $contact['name'] : '',
            'email' => isset($contact['email']) ? (string) $contact['email'] : (string) Configuration::get('PS_SHOP_EMAIL'),
        );
    }

    protected function resolveCustomer(array $payload, $email)
    {
        $idCustomer = (int) $this->getPayloadValue($payload, 'id_customer', 0);
        if ($idCustomer > 0) {
            return MlabFactoryApiHelper::ensureCustomerExists($idCustomer);
        }

        $customer = MlabFactoryApiHelper::getCustomerByEmail($email);
        if ($customer && Validate::isLoadedObject($customer)) {
            return $customer;
        }

        return null;
    }

    protected function resolveOrder(array $payload, $customer)
    {
        $idOrder = (int) $this->getPayloadValue($payload, 'id_order', 0);
        if ($idOrder <= 0) {
            return null;
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            throw new MlabFactoryApiException('Order not found.', 404, array('id_order' => $idOrder));
        }

        if ($customer && (int) $customer->id > 0 && (int) $order->id_customer !== (int) $customer->id) {
            throw new MlabFactoryApiException('Order does not belong to the customer.', 422, array('id_order' => $idOrder));
        }

        return $order;
    }

    protected function createCustomerThread(array $contact, $customer, $order, $email, $subject, $languageId, $shopId)
    {
        $thread = new CustomerThread();
        $thread->id_shop = (int) $shopId;
        $thread->id_lang = (int) $languageId;
        $thread->id_contact = (int) $contact['id_contact'];
        $thread->id_customer = $customer && Validate::isLoadedObject($customer) ? (int) $customer->id : 0;
        $thread->id_order = $order && Validate::isLoadedObject($order) ? (int) $order->id : 0;
        $thread->id_product = 0;
        $thread->email = (string) $email;
        $thread->token = Tools::passwdGen(12);
        $thread->status = 'open';
        if (property_exists($thread, 'subject')) {
            $thread->subject = $subject;
        }

        if (!$thread->add()) {
            throw new MlabFactoryApiException('Unable to create contact thread.', 500);
        }

        return $thread;
    }

    protected function createCustomerMessage(CustomerThread $thread, $message)
    {
        $customerMessage = new CustomerMessage();
        $customerMessage->id_customer_thread = (int) $thread->id;
        $customerMessage->message = strip_tags((string) $message, '<br><p><strong><em><ul><ol><li>');
        $customerMessage->private = 0;

        if (!$customerMessage->add()) {
            throw new MlabFactoryApiException('Unable to persist contact message.', 500, array('id_customer_thread' => (int) $thread->id));
        }
    }

    protected function notifyContact(array $contact, CustomerThread $thread, $customer, $order, $email, $firstname, $lastname, $subject, $message, $languageId, $shopId)
    {
        $templateVars = array(
            '{reply}' => nl2br(Tools::safeOutput($message)),
            '{link}' => '',
            '{firstname}' => $firstname,
            '{lastname}' => $lastname,
            '{email}' => $email,
            '{id_order}' => $order && Validate::isLoadedObject($order) ? (int) $order->id : '',
            '{id_customer_thread}' => (int) $thread->id,
        );

        $sent = Mail::Send(
            (int) $languageId,
            'contact',
            $subject,
            $templateVars,
            (string) $contact['email'],
            (string) $contact['name'],
            $email,
            trim($firstname . ' ' . $lastname),
            null,
            null,
            _PS_MAIL_DIR_,
            false,
            (int) $shopId
        );

        if (!$sent) {
            throw new MlabFactoryApiException('Unable to send contact email.', 500, array(
                'id_customer_thread' => (int) $thread->id,
                'id_contact' => (int) $contact['id_contact'],
            ));
        }
    }

    protected function getPayloadValue(array $payload, $key, $default = null)
    {
        if (array_key_exists($key, $payload)) {
            return $payload[$key];
        }

        return $default;
    }

    protected function getContextShopId()
    {
        if (isset($this->context->shop) && Validate::isLoadedObject($this->context->shop)) {
            return (int) $this->context->shop->id;
        }

        return (int) Configuration::get('PS_SHOP_DEFAULT');
    }

    protected function getContextLanguageId()
    {
        if (isset($this->context->language) && Validate::isLoadedObject($this->context->language)) {
            return (int) $this->context->language->id;
        }

        return (int) Configuration::get('PS_LANG_DEFAULT');
    }
}