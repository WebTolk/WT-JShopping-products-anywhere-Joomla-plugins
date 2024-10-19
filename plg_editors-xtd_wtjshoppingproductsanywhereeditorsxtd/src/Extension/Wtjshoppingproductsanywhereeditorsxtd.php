<?php
/**
 * @package    WT JShopping products anywhere
 * @version       2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright  Copyright (c) 2021 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\EditorsXtd\Wtjshoppingproductsanywhereeditorsxtd\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jshopping\Site\Helper\SelectOptions;

/**
 * Editor Article button
 *
 * @since  1.5
 */
final class Wtjshoppingproductsanywhereeditorsxtd extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;

    /**
     * Display the button
     *
     * @param string $name The name of the button to add
     *
     * @return  CMSObject  The button options as CMSObject
     *
     * @since   1.5
     */
    public function onDisplay($name)
    {

        $app = $this->getApplication();
        if (!$app->isClient('administrator')) {
            return;
        }

        $user = $app->getIdentity();

        // Can create in any category (component permission) or at least in one category
        $canCreateRecords = $user->authorise('core.create', 'com_content')
            || count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;

        // Instead of checking edit on all records, we can use **same** check as the form editing view
        $values = (array)Factory::getApplication()->getUserState('com_content.edit.article.id');
        $isEditingRecords = count($values);

        // This ACL check is probably a double-check (form view already performed checks)
        $hasAccess = $canCreateRecords || $isEditingRecords;
        if (!$hasAccess) {
            return;
        }

        if (!class_exists('JSHelper') && file_exists(JPATH_SITE . '/components/com_jshopping/bootstrap.php')) {
            require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
        }
        $current_lang = \JSFactory::getLang()->lang;
        \JSFactory::loadAdminLanguageFile($current_lang);

        $app->getDocument()->getWebAssetManager()->addInlineStyle('
            .btn-web-tolk {
                    background-color: #0FA2E6;
                    color:#fff;
                    border: 1px solid rgba(0,0,0,0.2);
                    transition: all linear .3s;
                } 
                .btn-web-tolk:hover, .btn-web-tolk:focus, .btn-web-tolk:active {
                    background-color: #384148;
                    color:#fff;
                    border: 1px solid rgba(0,0,0,0.2);
                }
        ');
        $link = 'index.php?option=com_ajax&amp;plugin=wtjshoppingproductsanywhereeditorsxtd&amp;group=editors-xtd&amp;format=html&amp;tmpl=component&amp;' . Session::getFormToken() . '=1&amp;editor=' . $name;

        $button = new CMSObject();
        $button->modal = true;
        $button->class = 'btn btn-web-tolk';
        $button->link = $link;
        $button->text = Text::_('JSHOP_PRODUCTS') . ' JoomShopping';
        $button->name = 'cart';
        $button->options = [
            'height' => '400px',
            'width' => '700px',
            'modalWidth' => '80',
        ];

        return $button;

    }

    /**
     * Method working with Joomla com_ajax. Return a HTML form for product selection
     * @return string product selection HTML form
     * @throws Exception
     */
    public function onAjaxWtjshoppingproductsanywhereeditorsxtd()
    {
        $app = Factory::getApplication();

        if ($app->isClient('site')) {
            Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
        }

        if (!class_exists('JSHelper') && file_exists(JPATH_SITE . '/components/com_jshopping/bootstrap.php')) {
            require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
        } else {
            echo 'JoomShopping has not installed!';
        }
        $current_lang = \JSFactory::getLang()->lang;
        \JSFactory::loadAdminLanguageFile($current_lang);

        $jshopConfig = \JSFactory::getConfig();
        $products = \JSFactory::getModel("products");

        $context = "jshoping.list.admin.product";
        $limit = $app->getUserStateFromRequest($context . 'limit', 'limit', $app->get('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($context . 'limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($context . 'filter_order', 'filter_order', $jshopConfig->adm_prod_list_default_sorting, 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($context . 'filter_order_Dir', 'filter_order_Dir', $jshopConfig->adm_prod_list_default_sorting_dir, 'cmd');

        if ($app->getInput()->get('category_id', 0) == "0") {
            $app->setUserState($context . 'category_id', 0);
            $app->setUserState($context . 'manufacturer_id', 0);
            $app->setUserState($context . 'vendor_id', -1);
            $app->setUserState($context . 'label_id', 0);
            $app->setUserState($context . 'publish', 0);
            $app->setUserState($context . 'text_search', '');
        }

        $category_id = $app->getUserStateFromRequest($context . 'category_id', 'category_id', 0, 'int');
        $manufacturer_id = $app->getUserStateFromRequest($context . 'manufacturer_id', 'manufacturer_id', 0, 'int');
        $vendor_id = $app->getUserStateFromRequest($context . 'vendor_id', 'vendor_id', -1, 'int');
        $label_id = $app->getUserStateFromRequest($context . 'label_id', 'label_id', 0, 'int');
        $publish = $app->getUserStateFromRequest($context . 'publish', 'publish', 0, 'int');
        $text_search = $app->getUserStateFromRequest($context . 'text_search', 'text_search', '');
        if ($category_id && $filter_order == 'category') {
            $filter_order = 'product_id';
        }

        $filter = array("category_id" => $category_id, "manufacturer_id" => $manufacturer_id, "vendor_id" => $vendor_id, "label_id" => $label_id, "publish" => $publish, "text_search" => $text_search);
        $total = $products->getCountAllProducts($filter);
        $pageNav = new Pagination($total, $limitstart, $limit);
        $rows = $products->getAllProducts(
            $filter,
            $pageNav->limitstart,
            $pageNav->limit,
            $filter_order,
            $filter_order_Dir,
            array(
                'label_image' => 1,

            )
        );
        $lists = array(
            'treecategories' => HTMLHelper::_('select.genericlist', SelectOptions::getCategories(), 'category_id', 'class="form-select" onchange="document.adminForm.submit();"', 'category_id', 'name', $category_id),
            'manufacturers' => HTMLHelper::_('select.genericlist', SelectOptions::getManufacturers(), 'manufacturer_id', 'class="form-select" onchange="document.adminForm.submit();"', 'manufacturer_id', 'name', $manufacturer_id),
            'publish' => HTMLHelper::_('select.genericlist', SelectOptions::getPublish(), 'publish', 'class="form-select" onchange="document.adminForm.submit();"', 'id', 'name', $publish)
        );

        if ($jshopConfig->admin_show_product_labels) {
            $lists['labels'] = HTMLHelper::_('select.genericlist', SelectOptions::getLabels(), 'label_id', 'class="form-select" onchange="document.adminForm.submit();"', 'id', 'name', $label_id);
        }

        $app->triggerEvent('onBeforeDisplayListProducts', array(&$rows));
        $doc = $app->getDocument();
        $doc->getWebAssetManager()
            ->useScript('core')
            ->registerAndUseScript(
                'wtjshoppingproductsanywhereeditorsxtd', 'plg_editors-xtd_wtjshoppingproductsanywhereeditorsxtd/wtjshoppingproductsanywhereeditorsxtd.js'
        );

        $editor = $app->getInput()->get('editor', '');
        $wt_wtjshoppingproductsanywhereeditorsxtd = Folder::files(JPATH_SITE . "/plugins/content/wtjshoppingproductsanywhere/tmpl");
        $options = array();
        foreach ($wt_wtjshoppingproductsanywhereeditorsxtd as $file) {
            if (File::getExt($file) == "php") {
                $wt_layout = File::stripExt($file);
                $options[] = HTMLHelper::_('select.option', $wt_layout, $wt_layout);
            }
        }

        if (!empty($editor)) {

            $doc->addScriptOptions('xtd-wtjshoppingproductsanywhereeditorsxtd', array('editor' => $editor));
        }

        $i = 0;

        $editor = $app->getInput()->get('editor');
        ?>
        <form
                action="index.php?option=com_ajax&plugin=wtjshoppingproductsanywhereeditorsxtd&group=editors-xtd&format=html&tmpl=component&<?php echo Session::getFormToken(); ?>=1&editor=<?php echo $editor; ?>"
                id="adminForm"
                name="adminForm" class="container">
            <input type="hidden" name="option" value="com_ajax"/>
            <input type="hidden" name="plugin" value="wtjshoppingproductsanywhereeditorsxtd"/>
            <input type="hidden" name="group" value="editors-xtd"/>
            <input type="hidden" name="format" value="html"/>
            <input type="hidden" name="tmpl" value="component"/>
            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1"/>
            <input type="hidden" name="editor" value="<?php echo $editor; ?>"/>

            <div class="row mb-3">
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="input-group mb-3">
                        <label for="wtjshoppingproductsanywhere_layout" class="input-group-text">
                            <strong>tmpl</strong>
                        </label>
                        <?php
                        $attribs = [
                            'class' => 'form-select',
                            'aria-label' => 'Choose layout'
                        ];

                        echo HTMLHelper::_("select.genericlist", $options, $name = "wtjshoppingproductsanywhere_layout", $attribs, $key = 'value', $text = 'text', $selected = "default"); ?>
                    </div>

                </div>
                <div class="col-6 col-md-4 col-lg-9">
                    <div class="input-group mb-3">
                        <input class="form-control" id="text_search" type="text" name="text_search"
                               placeholder="<?php echo Text::_('JSHOP_SEARCH'); ?>"
                            <?php
                                if (!empty($text_search)) {
                                    echo 'value="' . $text_search . '"';
                                }
                            ?>
                        />
                        <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                        <button class="btn btn-danger" type="button"
                                onclick="document.getElementById('text_search').value='';this.form.submit();"><i
                                    class="icon-remove"></i></button>
                    </div>
                </div>
                <div class="d-flex justify-content-start">
                    <?php foreach ($lists as $key => $value): ?>
                        <div class="me-3">
                            <?php echo $value; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo Text::_('JSHOP_IMAGE'); ?></th>
                    <th><?php echo Text::_('JSHOP_PRODUCT'); ?></th>
                    <th><?php echo Text::_('JSHOP_CATEGORY'); ?></th>
                    <th><?php echo Text::_('JSHOP_MANUFACTURER'); ?></th>
                    <th><?php echo Text::_('JSHOP_EAN'); ?></th>
                    <th><?php echo Text::_('JSHOP_PRICE'); ?></th>
                    <th><?php echo Text::_('JSHOP_ID'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="row<?php echo $i % 2; ?>">
                        <td>
                            <?php echo $pageNav->getRowOffset($i); ?>
                        </td>
                        <td>
                            <?php if ($row->label_id) : ?>
                                <div class="product_label">
                                    <?php if (isset($row->_label_image) && $row->_label_image) : ?>
                                        <img src="<?php print $row->_label_image ?>" width="25" alt=""/>
                                    <?php else : ?>
                                        <span class="label_name"><?php print $row->_label_name; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($row->image) : ?>
                                <a href="#" class="select-link" data-product-id="<?php echo $row->product_id; ?>">
                                    <img src="<?php print \JSHelper::getPatchProductImage($row->image, 'thumb', 1) ?>"
                                         width="120"/>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <b><a href="#" class="select-link"
                                  data-product-id="<?php print $row->product_id ?>"><?php echo $row->name; ?></a></b>
                            <div><?php echo $row->short_description; ?></div>
                        </td>
                        <?php print $row->tmp_html_col_after_title ?>

                        <td>
                            <?php echo $row->namescats; ?>
                        </td>
                        <td>
                            <?php echo $row->man_name; ?>
                        </td>
                        <td>
                            <?php echo $row->ean ?>
                            <?php if ($jshopConfig->admin_product_list_manufacture_code && $row->manufacturer_code != '') { ?>
                                (<?php print $row->manufacturer_code ?>)
                            <?php } ?>
                        </td>

                        <td>
                            <?php echo \JSHelper::formatprice($row->product_price, \JSHelper::sprintCurrency($row->currency_id)); ?>
                        </td>
                        <td class="center">
                            <?php echo $row->product_id; ?>
                        </td>
                    </tr>
                    <?php
                    $i++;
                endforeach;
                ?>


                </tbody>
                <tfoot>
                <tr>
                    <?php echo(!empty($this->tmp_html_col_before_td_foot) ? $this->tmp_html_col_before_td_foot : ''); ?>
                    <td colspan="6">
                        <div class="jshop_list_footer"><?php echo $pageNav->getListFooter(); ?></div>
                    </td>
                    <td colspan="2">
                        <div class="jshop_limit_box"><?php echo $pageNav->getLimitBox(); ?></div>
                    </td>
                    <?php echo(!empty($this->tmp_html_col_after_td_foot) ? $this->tmp_html_col_after_td_foot : ''); ?>
                </tr>
                </tfoot>
            </table>
        </form>
        <div class="fixed-bottom bg-white shadow-sm border-top">
            <div class="container d-flex justify-content-between align-items-center">


                <a href="https://www.webdesigner-profi.de/joomla-webdesign/shop.html" target="_blank">
                        <img src="<?php echo Uri::root(); ?>/administrator/components/com_jshopping/images/joomshopping.png"
                             class="my-2" style="height:28px"/>
                </a>

                    <span class="">
                        <a href="https://web-tolk.ru" target="_blank"
                           style="display: inline-flex; align-items: center;">
                                <svg width="85" height="18" xmlns="http://www.w3.org/2000/svg">
                                     <g>
                                      <title>Go to https://web-tolk.ru</title>
                                      <text font-weight="bold" xml:space="preserve" text-anchor="start"
                                            font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_3" y="18"
                                            x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000"
                                            fill="#0fa2e6">Web</text>
                                      <text font-weight="bold" xml:space="preserve" text-anchor="start"
                                            font-family="Helvetica, Arial, sans-serif" font-size="18" id="svg_4" y="18"
                                            x="45" stroke-opacity="null" stroke-width="0" stroke="#000"
                                            fill="#384148">Tolk</text>
                                     </g>
                                </svg>
                        </a>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
}
