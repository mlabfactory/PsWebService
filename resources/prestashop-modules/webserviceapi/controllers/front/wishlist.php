<?php
require_once dirname(__FILE__) . '/../../classes/MlabFactoryApiBaseModuleFrontController.php';

class webserviceapiwishlistModuleFrontController extends MlabFactoryApiBaseModuleFrontController
{
    protected function handleRequest()
    {
        $method = strtoupper((string) $_SERVER['REQUEST_METHOD']);
        $this->assertRequestMethod(array('GET', 'POST', 'PUT', 'DELETE'));
        $this->assertWishlistTablesAvailable();

        if ($method === 'GET') {
            return $this->handleGetRequest();
        }

        if ($method === 'POST') {
            return $this->handlePostRequest();
        }

        if ($method === 'PUT') {
            return $this->handlePutRequest();
        }

        return $this->handleDeleteRequest();
    }

    protected function handleGetRequest()
    {
        $idCustomer = (int) Tools::getValue('id_customer');
        $idWishlist = (int) Tools::getValue('id_wishlist');
        $includeProducts = MlabFactoryApiHelper::toBool(Tools::getValue('include_products', true), true);

        $this->assertCustomerIdentifier($idCustomer);

        if ($idWishlist > 0) {
            $wishlist = $this->getOwnedWishlist($idWishlist, $idCustomer, $includeProducts);

            return array(
                'message' => 'Wishlist retrieved successfully.',
                'wishlist' => $wishlist,
            );
        }

        return array(
            'message' => 'Wishlists retrieved successfully.',
            'wishlists' => $this->getCustomerWishlists($idCustomer, $includeProducts),
        );
    }

    protected function handlePostRequest()
    {
        $payload = $this->getJsonPayload();
        $idCustomer = (int) $this->getPayloadValue($payload, 'id_customer', 0);

        $this->assertCustomerIdentifier($idCustomer);
        MlabFactoryApiHelper::ensureCustomerExists($idCustomer);

        $idProduct = (int) $this->getPayloadValue($payload, 'id_product', 0);
        if ($idProduct > 0) {
            return $this->addProductToWishlist($payload, $idCustomer, $idProduct);
        }

        $name = trim((string) $this->getPayloadValue($payload, 'name', ''));
        if ($name === '') {
            $name = 'Wishlist';
        }

        $makeDefault = MlabFactoryApiHelper::toBool($this->getPayloadValue($payload, 'default', false));
        $wishlistId = $this->createWishlist($idCustomer, $name, $makeDefault);

        return array(
            'message' => 'Wishlist created successfully.',
            'wishlist' => $this->getOwnedWishlist($wishlistId, $idCustomer, true),
        );
    }

    protected function handlePutRequest()
    {
        $payload = $this->getJsonPayload();
        $idCustomer = (int) $this->getPayloadValue($payload, 'id_customer', 0);
        $idWishlist = (int) $this->getPayloadValue($payload, 'id_wishlist', 0);

        $this->assertCustomerIdentifier($idCustomer);
        $wishlist = $this->getOwnedWishlistRow($idWishlist, $idCustomer);

        $idProduct = (int) $this->getPayloadValue($payload, 'id_product', 0);
        if ($idProduct > 0) {
            $quantity = (int) $this->getPayloadValue($payload, 'quantity', 1);
            if ($quantity <= 0) {
                throw new MlabFactoryApiException('quantity must be greater than zero.', 422);
            }

            $idProductAttribute = (int) $this->getPayloadValue($payload, 'id_product_attribute', 0);
            $priority = $this->sanitizePriority((string) $this->getPayloadValue($payload, 'priority', '0'));
            $this->upsertWishlistProduct((int) $wishlist['id_wishlist'], $idProduct, $idProductAttribute, $quantity, $priority, true);

            return array(
                'message' => 'Wishlist product updated successfully.',
                'wishlist' => $this->getOwnedWishlist((int) $wishlist['id_wishlist'], $idCustomer, true),
            );
        }

        $updates = array();
        $name = trim((string) $this->getPayloadValue($payload, 'name', (string) $wishlist['name']));
        if ($name === '') {
            throw new MlabFactoryApiException('Wishlist name cannot be empty.', 422);
        }
        $updates['name'] = pSQL($name);

        if (array_key_exists('default', $payload)) {
            $updates['default'] = MlabFactoryApiHelper::toBool($payload['default']) ? 1 : 0;
        }

        $this->updateWishlist((int) $wishlist['id_wishlist'], $idCustomer, $updates);

        return array(
            'message' => 'Wishlist updated successfully.',
            'wishlist' => $this->getOwnedWishlist((int) $wishlist['id_wishlist'], $idCustomer, true),
        );
    }

    protected function handleDeleteRequest()
    {
        $payload = $this->getJsonPayload();
        $idCustomer = (int) $this->getPayloadValue($payload, 'id_customer', (int) Tools::getValue('id_customer'));
        $idWishlist = (int) $this->getPayloadValue($payload, 'id_wishlist', (int) Tools::getValue('id_wishlist'));

        $this->assertCustomerIdentifier($idCustomer);
        $wishlist = $this->getOwnedWishlistRow($idWishlist, $idCustomer);

        $idProduct = (int) $this->getPayloadValue($payload, 'id_product', (int) Tools::getValue('id_product'));
        if ($idProduct > 0) {
            $idProductAttribute = (int) $this->getPayloadValue($payload, 'id_product_attribute', (int) Tools::getValue('id_product_attribute'));

            $deleted = Db::getInstance()->delete(
                'wishlist_product',
                'id_wishlist = ' . (int) $wishlist['id_wishlist'] . '
                AND id_product = ' . (int) $idProduct . '
                AND id_product_attribute = ' . (int) $idProductAttribute
            );

            if (!$deleted) {
                throw new MlabFactoryApiException('Unable to remove product from wishlist.', 500, array(
                    'id_wishlist' => (int) $wishlist['id_wishlist'],
                    'id_product' => $idProduct,
                    'id_product_attribute' => $idProductAttribute,
                ));
            }

            return array(
                'message' => 'Wishlist product removed successfully.',
                'wishlist' => $this->getOwnedWishlist((int) $wishlist['id_wishlist'], $idCustomer, true),
            );
        }

        Db::getInstance()->delete('wishlist_product', 'id_wishlist = ' . (int) $wishlist['id_wishlist']);
        $deleted = Db::getInstance()->delete('wishlist', 'id_wishlist = ' . (int) $wishlist['id_wishlist']);

        if (!$deleted) {
            throw new MlabFactoryApiException('Unable to delete wishlist.', 500, array('id_wishlist' => (int) $wishlist['id_wishlist']));
        }

        return array(
            'message' => 'Wishlist deleted successfully.',
            'id_wishlist' => (int) $wishlist['id_wishlist'],
        );
    }

    protected function addProductToWishlist(array $payload, $idCustomer, $idProduct)
    {
        $this->assertProductExists($idProduct);

        $idWishlist = (int) $this->getPayloadValue($payload, 'id_wishlist', 0);
        if ($idWishlist > 0) {
            $wishlist = $this->getOwnedWishlistRow($idWishlist, $idCustomer);
        } else {
            $wishlist = $this->getOrCreateDefaultWishlist($idCustomer);
        }

        $quantity = (int) $this->getPayloadValue($payload, 'quantity', 1);
        if ($quantity <= 0) {
            throw new MlabFactoryApiException('quantity must be greater than zero.', 422);
        }

        $idProductAttribute = (int) $this->getPayloadValue($payload, 'id_product_attribute', 0);
        $priority = $this->sanitizePriority((string) $this->getPayloadValue($payload, 'priority', '0'));
        $replaceQuantity = MlabFactoryApiHelper::toBool($this->getPayloadValue($payload, 'replace_quantity', false));

        $this->upsertWishlistProduct((int) $wishlist['id_wishlist'], $idProduct, $idProductAttribute, $quantity, $priority, $replaceQuantity);

        return array(
            'message' => 'Product added to wishlist successfully.',
            'wishlist' => $this->getOwnedWishlist((int) $wishlist['id_wishlist'], $idCustomer, true),
        );
    }

    protected function assertWishlistTablesAvailable()
    {
        $wishlistTable = pSQL(_DB_PREFIX_ . 'wishlist');
        $wishlistProductTable = pSQL(_DB_PREFIX_ . 'wishlist_product');

        $wishlistExists = (string) Db::getInstance()->getValue("SHOW TABLES LIKE '" . $wishlistTable . "'");
        $wishlistProductExists = (string) Db::getInstance()->getValue("SHOW TABLES LIKE '" . $wishlistProductTable . "'");

        if ($wishlistExists === '' || $wishlistProductExists === '') {
            throw new MlabFactoryApiException('Wishlist tables are not available. Install and configure the PrestaShop wishlist module first.', 501);
        }
    }

    protected function assertCustomerIdentifier($idCustomer)
    {
        if ($idCustomer <= 0) {
            throw new MlabFactoryApiException('id_customer is required.', 422);
        }
    }

    protected function getPayloadValue(array $payload, $key, $default = null)
    {
        if (array_key_exists($key, $payload)) {
            return $payload[$key];
        }

        return $default;
    }

    protected function getCustomerWishlists($idCustomer, $includeProducts)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT w.*
            FROM `' . _DB_PREFIX_ . 'wishlist` w
            WHERE w.`id_customer` = ' . (int) $idCustomer . '
            ORDER BY w.`default` DESC, w.`date_upd` DESC, w.`id_wishlist` DESC'
        );

        $wishlists = array();
        foreach ((array) $rows as $row) {
            $wishlists[] = $this->serializeWishlistRow($row, $includeProducts);
        }

        return $wishlists;
    }

    protected function getOwnedWishlist($idWishlist, $idCustomer, $includeProducts)
    {
        return $this->serializeWishlistRow($this->getOwnedWishlistRow($idWishlist, $idCustomer), $includeProducts);
    }

    protected function getOwnedWishlistRow($idWishlist, $idCustomer)
    {
        if ($idWishlist <= 0) {
            throw new MlabFactoryApiException('id_wishlist is required.', 422);
        }

        $row = Db::getInstance()->getRow(
            'SELECT w.*
            FROM `' . _DB_PREFIX_ . 'wishlist` w
            WHERE w.`id_wishlist` = ' . (int) $idWishlist . '
              AND w.`id_customer` = ' . (int) $idCustomer
        );

        if (empty($row)) {
            throw new MlabFactoryApiException('Wishlist not found.', 404, array(
                'id_wishlist' => (int) $idWishlist,
                'id_customer' => (int) $idCustomer,
            ));
        }

        return $row;
    }

    protected function getOrCreateDefaultWishlist($idCustomer)
    {
        $row = Db::getInstance()->getRow(
            'SELECT w.*
            FROM `' . _DB_PREFIX_ . 'wishlist` w
            WHERE w.`id_customer` = ' . (int) $idCustomer . '
            ORDER BY w.`default` DESC, w.`id_wishlist` ASC'
        );

        if (!empty($row)) {
            return $row;
        }

        $wishlistId = $this->createWishlist($idCustomer, 'Wishlist', true);

        return $this->getOwnedWishlistRow($wishlistId, $idCustomer);
    }

    protected function createWishlist($idCustomer, $name, $makeDefault)
    {
        if ($makeDefault) {
            Db::getInstance()->update('wishlist', array('default' => 0), 'id_customer = ' . (int) $idCustomer);
        }

        $data = array(
            'id_shop' => $this->getContextShopId(),
            'id_shop_group' => $this->getContextShopGroupId(),
            'id_customer' => (int) $idCustomer,
            'id_currency' => $this->getContextCurrencyId(),
            'id_lang' => $this->getContextLanguageId(),
            'name' => pSQL($name),
            'token' => pSQL(Tools::passwdGen(32)),
            'default' => $makeDefault ? 1 : 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        );

        $created = Db::getInstance()->insert('wishlist', $data);
        if (!$created) {
            throw new MlabFactoryApiException('Unable to create wishlist.', 500, array('id_customer' => (int) $idCustomer));
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    protected function updateWishlist($idWishlist, $idCustomer, array $updates)
    {
        if (isset($updates['default']) && (int) $updates['default'] === 1) {
            Db::getInstance()->update('wishlist', array('default' => 0), 'id_customer = ' . (int) $idCustomer);
        }

        $updates['date_upd'] = date('Y-m-d H:i:s');
        $updated = Db::getInstance()->update('wishlist', $updates, 'id_wishlist = ' . (int) $idWishlist);

        if (!$updated) {
            throw new MlabFactoryApiException('Unable to update wishlist.', 500, array('id_wishlist' => (int) $idWishlist));
        }
    }

    protected function upsertWishlistProduct($idWishlist, $idProduct, $idProductAttribute, $quantity, $priority, $replaceQuantity)
    {
        $this->assertProductExists($idProduct, $idProductAttribute);

        $existing = Db::getInstance()->getRow(
            'SELECT wp.*
            FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            WHERE wp.`id_wishlist` = ' . (int) $idWishlist . '
              AND wp.`id_product` = ' . (int) $idProduct . '
              AND wp.`id_product_attribute` = ' . (int) $idProductAttribute
        );

        if (!empty($existing)) {
            $newQuantity = $replaceQuantity ? (int) $quantity : ((int) $existing['quantity'] + (int) $quantity);
            $updated = Db::getInstance()->update(
                'wishlist_product',
                array(
                    'quantity' => $newQuantity,
                    'priority' => pSQL($priority),
                ),
                'id_wishlist = ' . (int) $idWishlist . '
                AND id_product = ' . (int) $idProduct . '
                AND id_product_attribute = ' . (int) $idProductAttribute
            );

            if (!$updated) {
                throw new MlabFactoryApiException('Unable to update wishlist product.', 500, array(
                    'id_wishlist' => (int) $idWishlist,
                    'id_product' => (int) $idProduct,
                    'id_product_attribute' => (int) $idProductAttribute,
                ));
            }

            Db::getInstance()->update('wishlist', array('date_upd' => date('Y-m-d H:i:s')), 'id_wishlist = ' . (int) $idWishlist);

            return;
        }

        $inserted = Db::getInstance()->insert('wishlist_product', array(
            'id_wishlist' => (int) $idWishlist,
            'id_product' => (int) $idProduct,
            'id_product_attribute' => (int) $idProductAttribute,
            'quantity' => (int) $quantity,
            'priority' => pSQL($priority),
        ));

        if (!$inserted) {
            throw new MlabFactoryApiException('Unable to add product to wishlist.', 500, array(
                'id_wishlist' => (int) $idWishlist,
                'id_product' => (int) $idProduct,
                'id_product_attribute' => (int) $idProductAttribute,
            ));
        }

        Db::getInstance()->update('wishlist', array('date_upd' => date('Y-m-d H:i:s')), 'id_wishlist = ' . (int) $idWishlist);
    }

    protected function serializeWishlistRow(array $row, $includeProducts)
    {
        $wishlist = array(
            'id_wishlist' => (int) $row['id_wishlist'],
            'id_customer' => (int) $row['id_customer'],
            'id_shop' => isset($row['id_shop']) ? (int) $row['id_shop'] : 0,
            'id_shop_group' => isset($row['id_shop_group']) ? (int) $row['id_shop_group'] : 0,
            'id_currency' => isset($row['id_currency']) ? (int) $row['id_currency'] : 0,
            'id_lang' => isset($row['id_lang']) ? (int) $row['id_lang'] : 0,
            'name' => isset($row['name']) ? (string) $row['name'] : '',
            'default' => !empty($row['default']),
            'date_add' => isset($row['date_add']) ? (string) $row['date_add'] : '',
            'date_upd' => isset($row['date_upd']) ? (string) $row['date_upd'] : '',
        );

        if ($includeProducts) {
            $wishlist['products'] = $this->getWishlistProducts((int) $row['id_wishlist'], (int) $wishlist['id_lang']);
        }

        return $wishlist;
    }

    protected function getWishlistProducts($idWishlist, $idLang)
    {
        $languageId = $idLang > 0 ? $idLang : $this->getContextLanguageId();
        $shopId = $this->getContextShopId();

        $rows = Db::getInstance()->executeS(
            'SELECT wp.`id_product`, wp.`id_product_attribute`, wp.`quantity`, wp.`priority`,
                    pl.`name`, pl.`link_rewrite`, p.`reference`, p.`active`
            FROM `' . _DB_PREFIX_ . 'wishlist_product` wp
            INNER JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = wp.`id_product`)
            LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                pl.`id_product` = p.`id_product`
                AND pl.`id_lang` = ' . (int) $languageId . '
                AND pl.`id_shop` = ' . (int) $shopId . '
            )
            WHERE wp.`id_wishlist` = ' . (int) $idWishlist . '
            ORDER BY wp.`priority` ASC, pl.`name` ASC'
        );

        $products = array();
        foreach ((array) $rows as $row) {
            $products[] = array(
                'id_product' => (int) $row['id_product'],
                'id_product_attribute' => (int) $row['id_product_attribute'],
                'id_image' => $this->getProductCoverImage((int) $row['id_product']),
                'name' => isset($row['name']) ? (string) $row['name'] : '',
                'slug' => isset($row['link_rewrite']) ? (string) $row['link_rewrite'] : '',
                'reference' => isset($row['reference']) ? (string) $row['reference'] : '',
                'quantity' => (int) $row['quantity'],
                'priority' => isset($row['priority']) ? (string) $row['priority'] : '',
                'active' => !empty($row['active']),
            );
        }

        return $products;
    }

    protected function getProductCoverImage($idProduct)
    {
        $cover = Product::getCover((int) $idProduct);

        return is_array($cover) && isset($cover['id_image']) ? (int) $cover['id_image'] : 0;
    }

    protected function assertProductExists($idProduct, $idProductAttribute = 0)
    {
        $product = new Product((int) $idProduct, false, $this->getContextLanguageId(), $this->getContextShopId());
        if (!Validate::isLoadedObject($product)) {
            throw new MlabFactoryApiException('Product not found.', 404, array('id_product' => (int) $idProduct));
        }

        if ($idProductAttribute > 0) {
            $combinationExists = (int) Db::getInstance()->getValue(
                'SELECT pa.`id_product_attribute`
                FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                WHERE pa.`id_product_attribute` = ' . (int) $idProductAttribute . '
                  AND pa.`id_product` = ' . (int) $idProduct
            );

            if ($combinationExists <= 0) {
                throw new MlabFactoryApiException('Product combination not found.', 404, array(
                    'id_product' => (int) $idProduct,
                    'id_product_attribute' => (int) $idProductAttribute,
                ));
            }
        }
    }

    protected function sanitizePriority($priority)
    {
        $priority = trim((string) $priority);

        if ($priority === '') {
            return '0';
        }

        return pSQL($priority);
    }

    protected function getContextShopId()
    {
        if (isset($this->context->shop) && Validate::isLoadedObject($this->context->shop)) {
            return (int) $this->context->shop->id;
        }

        return (int) Configuration::get('PS_SHOP_DEFAULT');
    }

    protected function getContextShopGroupId()
    {
        if (isset($this->context->shop) && Validate::isLoadedObject($this->context->shop)) {
            return (int) $this->context->shop->id_shop_group;
        }

        return 0;
    }

    protected function getContextCurrencyId()
    {
        if (isset($this->context->currency) && Validate::isLoadedObject($this->context->currency)) {
            return (int) $this->context->currency->id;
        }

        return (int) Configuration::get('PS_CURRENCY_DEFAULT');
    }

    protected function getContextLanguageId()
    {
        if (isset($this->context->language) && Validate::isLoadedObject($this->context->language)) {
            return (int) $this->context->language->id;
        }

        return (int) Configuration::get('PS_LANG_DEFAULT');
    }
}