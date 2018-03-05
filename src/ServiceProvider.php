<?php

namespace Concrete\Package\PageDisabler;

use Concrete\Core\Application\Application;
use Concrete\Core\Application\Service\Dashboard\Sitemap as CoreSitemap;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Package\Package;
use Concrete\Package\PageDisabler\Application\Service\Dashboard\Sitemap;

/**
 * Register some commonly used service classes.
 *
 * @property \Concrete\Core\Application\Application $app
 */
class ServiceProvider extends Provider
{
    public function register()
    {
        $this->app->singleton('page_disabler/config', function (Application $app) {
            $pkg = Package::getByHandle('page_disabler');

            return $pkg->getFileConfig();
        });

        $this->app->singleton(PageDisabler::class);

        $this->app->offsetUnset('helper/concrete/dashboard/sitemap');
    	$this->app->offsetUnset(CoreSitemap::class);
    	$this->app->alias(Sitemap::class, 'helper/concrete/dashboard/sitemap');
    	$this->app->alias(Sitemap::class, CoreSitemap::class);
    	$this->app->singleton(Sitemap::class);
    }
}
