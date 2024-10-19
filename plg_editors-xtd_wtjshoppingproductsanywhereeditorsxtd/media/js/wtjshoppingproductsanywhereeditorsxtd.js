/**
 * @package    WT JShopping products anywhere
 * @version       2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright  Copyright (c) 2021 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */
(() => {
    document.addEventListener('DOMContentLoaded', () => {
        // Get the elements
        const elements = document.querySelectorAll('.select-link');

        for (let i = 0, l = elements.length; l > i; i += 1) {
            // Listen for click event
            elements[i].addEventListener('click', event => {
                event.preventDefault();
                const {
                    target
                } = event;

                const productd_id = target.getAttribute('data-product-id');
                const tmpl = document.getElementById('wtjshoppingproductsanywhere_layout').value;

                if (!Joomla.getOptions('xtd-wtjshoppingproductsanywhereeditorsxtd')) {
                    // Something went wrong!
                    // @TODO Close the modal
                    return false;
                }

                const {
                    editor
                } = Joomla.getOptions('xtd-wtjshoppingproductsanywhereeditorsxtd');

                window.parent.Joomla.editors.instances[editor].replaceSelection("{wt_jshop_products product_id=" + productd_id + " tmpl=" + tmpl + "}");

                if (window.parent.Joomla.Modal) {
                    window.parent.Joomla.Modal.getCurrent().close();
                }
            });
        }
    });
})();
