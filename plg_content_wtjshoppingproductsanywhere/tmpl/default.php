<?php
/**
 * @package    WT JShopping products anywhere
 * @version       2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright  Copyright (c) 2021 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');
/**
 * $product->product_id
 * $product->product_ean
 * $product->manufacturer_code
 * $product->product_quantity
 * $product->unlimited
 * $product->product_availability
 * $product->product_date_added
 * $product->date_modify
 * $product->product_old_price
 * $product->product_buy_price
 * $product->product_price
 * $product->min_price
 * $product->product_weight
 * $product->image
 * $product->average_rating
 * $product->reviews_count
 * $product->hits
 * $product->name
 * $product->short_description
 * $product->sef_link
 */

?><a href="<?php echo $product->sef_link; ?>"><?php echo $product->name; ?></a>