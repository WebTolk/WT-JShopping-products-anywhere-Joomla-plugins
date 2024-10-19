<?php
/**
 * @package    WT JShopping products anywhere
 * @version       2.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright  Copyright (c) 2021 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Content\Wtjshoppingproductsanywhere\Extension\Wtjshoppingproductsanywhere;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $subject = $container->get(DispatcherInterface::class);
                $config  = (array) PluginHelper::getPlugin('content', 'wtjshoppingproductsanywhere');
                $plugin = new Wtjshoppingproductsanywhere($subject, $config);
                return $plugin;
            }
        );
    }
};