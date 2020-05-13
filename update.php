<?php

// command line tool used to bulk update tags for products in shopify
// make sure api has access to "write_products" scope
// usage: php update.php

require('vendor/autoload.php');

use PHPShopify\ShopifySDK;

// config
$config = array(
    'ShopUrl' => 'yourshop.myshopify.com',
    'ApiKey' => '***YOUR-PRIVATE-API-KEY***',
    'Password' => '***YOUR-PRIVATE-API-PASSWORD***',
);

// setup $shopify
$shopify = PHPShopify\ShopifySDK::config($config);

// download all the products
$productResource = $shopify->Product();
$products = $productResource->get(['limit' => 250]);
$nextPageProducts = $productResource->getNextPageParams();
$nextPageProductsArray = [];

while ($nextPageProducts) {
    $nextPageProductsArray = $productResource->get($productResource->getNextPageParams());
    $products = array_merge($products, $nextPageProductsArray);
    $nextPageProducts = $productResource->getNextPageParams();
}

// update tags for each product
foreach ($products as $product) {
    if (count($product['variants']) > 0) {
        $tags = explode(",", $product['tags']);
        
        $tags = array_map(function($a) {
            return trim($a);
        }, $tags);
        
        // append tags
        $tags[] = 'Append This Tag';
        
        // delete duplicates
        $tags = array_unique($tags);
        $tags = array_filter($tags);

        // update
        try {
            $shopify->Product($product['id'])->put([
                    'tags' => join(',', $tags)
            ]);
            
            echo sprintf('UPDATE TAG: %d' . PHP_EOL, $product['id']);
        } catch(Exception $e) {
            echo sprintf('UPDATE TAG FAILED: %d' . PHP_EOL, $product['id']);
        }

        // rest
        sleep(0.5);
    }
}

echo PHP_EOL;
echo 'DONE';
echo PHP_EOL;