<?php
require_once dirname(__FILE__) . '/../../classes/MlabFactoryApiBaseModuleFrontController.php';

class MlabFactoryApiCartModuleFrontController extends MlabFactoryApiBaseModuleFrontController
{
    protected function handleRequest()
    {
        $method = strtoupper((string) $_SERVER['REQUEST_METHOD']);
        $this->assertRequestMethod(array('GET', 'POST', 'PUT'));

        if ($method === 'GET') {
            return $this->handleGetRequest();
        }

        return $this->handleWriteRequest();
    }

    protected function handleWriteRequest()
    {
        $payload = MlabFactoryApiHelper::getCartPayload($this->getJsonPayload());
        MlabFactoryApiHelper::requireFields($payload, array('id_customer', 'products'));
        if (!is_array($payload['products'])) {
            throw new MlabFactoryApiException('Products must be an array.', 422);
        }

        $customer = MlabFactoryApiHelper::ensureCustomerExists((int) $payload['id_customer']);
        $cart = !empty($payload['id_cart']) ? new Cart((int) $payload['id_cart']) : new Cart();
        if (!empty($payload['id_cart']) && !Validate::isLoadedObject($cart)) {
            throw new MlabFactoryApiException('Cart not found.', 404, array('id_cart' => (int) $payload['id_cart']));
        }
        if ((int) $cart->id > 0 && (int) $cart->id_customer !== (int) $customer->id) {
            throw new MlabFactoryApiException('Cart does not belong to the customer.', 422, array('id_cart' => (int) $cart->id));
        }

        $deliveryAddress = null;
        if (!empty($payload['delivery_address']) && is_array($payload['delivery_address'])) {
            $deliveryAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, $payload['delivery_address'], 'API delivery');
        } elseif (!empty($payload['id_address_delivery'])) {
            $deliveryAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, array('id_address' => (int) $payload['id_address_delivery']), 'API delivery');
        }

        $invoiceAddress = null;
        if (!empty($payload['invoice_address']) && is_array($payload['invoice_address'])) {
            $invoiceAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, $payload['invoice_address'], 'API invoice');
        } elseif (!empty($payload['id_address_invoice'])) {
            $invoiceAddress = MlabFactoryApiHelper::ensureAddressForCustomer($customer, array('id_address' => (int) $payload['id_address_invoice']), 'API invoice');
        }

        $cart->id_customer = (int) $customer->id;
        $cart->id_guest = 0;
        $cart->id_currency = (int) MlabFactoryApiHelper::getValue($payload, 'id_currency', Configuration::get('PS_CURRENCY_DEFAULT'));
        $cart->id_lang = (int) MlabFactoryApiHelper::getValue(
            $payload,
            'id_lang',
            $customer->id_lang ? $customer->id_lang : Configuration::get('PS_LANG_DEFAULT')
        );
        $cart->id_shop_group = (int) $this->context->shop->id_shop_group;
        $cart->id_shop = (int) $this->context->shop->id;
        $cart->secure_key = (string) $customer->secure_key;
        $cart->id_address_delivery = $deliveryAddress ? (int) $deliveryAddress->id : (int) $cart->id_address_delivery;
        $cart->id_address_invoice = $invoiceAddress ? (int) $invoiceAddress->id : (int) $cart->id_address_invoice;

        if (!$cart->id) {
            if (!$cart->add()) {
                throw new MlabFactoryApiException('Unable to create cart.', 500);
            }
        } elseif (!$cart->update()) {
            throw new MlabFactoryApiException('Unable to update cart.', 500);
        }

        if (MlabFactoryApiHelper::toBool(MlabFactoryApiHelper::getValue($payload, 'replace_products', true), true)) {
            foreach ($cart->getProducts() as $existingProduct) {
                $cart->deleteProduct(
                    (int) $existingProduct['id_product'],
                    (int) $existingProduct['id_product_attribute'],
                    (int) $existingProduct['id_customization'],
                    (int) $existingProduct['id_address_delivery']
                );
            }
        }

        foreach ($payload['products'] as $productLine) {
            if (!is_array($productLine)) {
                throw new MlabFactoryApiException('Each product line must be an object.', 422);
            }

            MlabFactoryApiHelper::requireFields($productLine, array('id_product', 'quantity'));
            $quantity = (int) $productLine['quantity'];
            if ($quantity <= 0) {
                throw new MlabFactoryApiException('Quantity must be greater than zero.', 422, array('product' => $productLine));
            }

            $productId = (int) $productLine['id_product'];
            $combinationId = (int) MlabFactoryApiHelper::getValue($productLine, 'id_product_attribute', 0);
            $customizationId = (int) MlabFactoryApiHelper::getValue($productLine, 'id_customization', 0);
            $deliveryAddressId = $cart->id_address_delivery ? (int) $cart->id_address_delivery : 0;

            $updated = $cart->updateQty($quantity, $productId, $combinationId, $customizationId, 'up', $deliveryAddressId, null, true, true);
            if ($updated <= 0) {
                throw new MlabFactoryApiException('Unable to add product to cart.', 422, array('product' => $productLine));
            }
        }

        $carrierId = (int) MlabFactoryApiHelper::getValue($payload, 'id_carrier', 0);
        if ($carrierId > 0) {
            $cart->id_carrier = $carrierId;
            $cart->setDeliveryOption(array((int) $cart->id_address_delivery => $carrierId . ','));
        }

        if (!$cart->update()) {
            throw new MlabFactoryApiException('Unable to persist cart.', 500);
        }

        return array(
            'message' => !empty($payload['id_cart']) ? 'Cart updated successfully.' : 'Cart created successfully.',
            'cart' => MlabFactoryApiHelper::serializeCart($cart),
        );
    }

    protected function handleGetRequest()
    {
        $idCustomer = (int) Tools::getValue('id_customer');
        $idGuest = (int) Tools::getValue('id_guest');
        $idCart = (int) Tools::getValue('id_cart');

        if ($idCustomer <= 0 && $idGuest <= 0) {
            throw new MlabFactoryApiException('You must provide id_customer or id_guest.', 422);
        }

        if ($idCustomer > 0 && $idGuest > 0) {
            throw new MlabFactoryApiException('Provide only one owner identifier: id_customer or id_guest.', 422);
        }

        $cart = $idCart > 0
            ? $this->getCartById($idCart, $idCustomer, $idGuest)
            : $this->getLatestOpenCart($idCustomer, $idGuest);

        return array(
            'message' => 'Cart retrieved successfully.',
            'cart' => MlabFactoryApiHelper::serializeCart($cart),
        );
    }

    protected function getCartById($idCart, $idCustomer, $idGuest)
    {
        $cart = new Cart((int) $idCart);
        if (!Validate::isLoadedObject($cart)) {
            throw new MlabFactoryApiException('Cart not found.', 404, array('id_cart' => (int) $idCart));
        }

        if ($idCustomer > 0 && (int) $cart->id_customer !== (int) $idCustomer) {
            throw new MlabFactoryApiException('Cart does not belong to the customer.', 422, array('id_cart' => (int) $idCart));
        }

        if ($idGuest > 0 && (int) $cart->id_guest !== (int) $idGuest) {
            throw new MlabFactoryApiException('Cart does not belong to the guest.', 422, array('id_cart' => (int) $idCart));
        }

        return $cart;
    }

    protected function getLatestOpenCart($idCustomer, $idGuest)
    {
        $where = $idCustomer > 0
            ? 'c.`id_customer` = ' . (int) $idCustomer
            : 'c.`id_guest` = ' . (int) $idGuest;

        $shopFilter = '';
        if (isset($this->context->shop) && Validate::isLoadedObject($this->context->shop)) {
            $shopFilter = ' AND c.`id_shop` = ' . (int) $this->context->shop->id;
        }

        $cartId = (int) Db::getInstance()->getValue(
            'SELECT c.`id_cart`
            FROM `' . _DB_PREFIX_ . 'cart` c
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_cart` = c.`id_cart`)
            WHERE ' . $where . $shopFilter . ' AND o.`id_order` IS NULL
            ORDER BY c.`date_upd` DESC, c.`id_cart` DESC
            LIMIT 1'
        );

        if ($cartId <= 0) {
            throw new MlabFactoryApiException('No open cart found for the requested owner.', 404, array(
                'id_customer' => (int) $idCustomer,
                'id_guest' => (int) $idGuest,
            ));
        }

        return new Cart($cartId);
    }
}
