<?php
/**
 * @package    WT JShopping products anywhere
 * @version       2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright  Copyright (c) 2021 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */
namespace Joomla\Plugin\Content\Wtjshoppingproductsanywhere\Extension;

\defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;

final class Wtjshoppingproductsanywhere extends CMSPlugin
{

    protected $autoloadLanguage = true;

    public function onContentPrepare($context, $article, $params, $limitstart = 0)
    {

        if ($context == 'com_finder.indexer') {
            return true;
        }

        //Проверка есть ли строка замены в контенте
        if (strpos($article->text, 'wt_jshop_products') === false) {
            return;
        }


        $regex = '/{wt_jshop_products\s(.*?)}/i';
        preg_match_all($regex, $article->text, $short_codes);

        if (!file_exists(JPATH_SITE . '/components/com_jshopping/bootstrap.php')) {
            return;
        }
        if (!class_exists('JSHelper')) {
            require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
        }

        \JSFactory::loadLanguageFile();
        $jshopConfig = \JSFactory::getConfig();
        $jshop_product = \JSFactory::getTable('product', 'jshop');

        $i = 0;
        $short_code_params = [];
        foreach ($short_codes[1] as $short_code) {

            $settings = explode(" ", $short_code);

            foreach ($settings as $param) {
                $param = explode("=", $param);
                $short_code_params[$param[0]] = $param[1];

            }
            if (!empty($short_code_params["product_id"])) {

                $tmpl = (!empty($short_code_params["tmpl"]) ? $short_code_params["tmpl"] : 'default');
                $jshop_product->load($short_code_params["product_id"]);
                if (!empty($jshop_product->product_id)) {
                    $product = new \stdClass();
                    $product->product_id = $jshop_product->product_id;
                    $product->product_ean = $jshop_product->product_ean;
                    $product->manufacturer_code = $jshop_product->manufacturer_code;
                    $product->product_quantity = $jshop_product->product_quantity;
                    $product->unlimited = $jshop_product->unlimited;
                    $product->product_availability = $jshop_product->product_availability;
                    $product->product_date_added = $jshop_product->product_date_added;
                    $product->date_modify = $jshop_product->date_modify;
                    $product->product_old_price = \JSHelper::formatprice($jshop_product->product_old_price);
                    $product->product_buy_price = \JSHelper::formatprice($jshop_product->product_buy_price);
                    $product->product_price = \JSHelper::formatprice($jshop_product->product_price);
                    $product->min_price = \JSHelper::formatprice($jshop_product->min_price);
                    $product->product_weight = $jshop_product->product_weight;
                    $product->image = $jshopConfig->image_product_live_path . '/' . $jshop_product->image;
                    $product->average_rating = $jshop_product->average_rating;
                    $product->reviews_count = $jshop_product->reviews_count;
                    $product->hits = $jshop_product->hits;

                    $lang = \JSFactory::getLang();
                    $product_name = $lang->get('name');
                    $product->name = $jshop_product->$product_name;
                    $product_short_description = $lang->get('short_description');
                    $product->short_description = $jshop_product->$product_short_description;

                    $category_id = $jshop_product->getCategory();
                    $defaultItemid = \JSHelper::getDefaultItemid('index.php?option=com_jshopping&controller=product&task=view&category_id=' . $category_id . '&product_id=' . $product->product_id);
                    $product->sef_link = Route::_('index.php?option=com_jshopping&&controller=product&task=view&category_id=' . $category_id . '&product_id=' . $product->product_id . '&Itemid=' . $defaultItemid);
                    ob_start();
                    if (file_exists(JPATH_SITE . '/plugins/content/wtjshoppingproductsanywhere/tmpl/' . $tmpl . '.php')) {
                        require JPATH_SITE . '/plugins/content/wtjshoppingproductsanywhere/tmpl/' . $tmpl . '.php';
                    } else {
                        require JPATH_SITE . '/plugins/content/wtjshoppingproductsanywhere/tmpl/default.php';
                    }
                    $html = ob_get_clean();

                } else {
                    //Товара с таким id не существует. Вставляем пустоту
                    $html = '';
                }

                $article->text = str_replace($short_codes[0][$i], $html, $article->text);

            } else {
                return;
            }
            $i++;
        }
    }
}