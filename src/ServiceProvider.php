<?php

namespace Concrete\Package\PageDisabler;

use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Application\Service\Dashboard\Sitemap as CoreSitemap;
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
        $this->app->singleton(PageDisabler::class);

        $this->app->offsetUnset('helper/concrete/dashboard/sitemap');
    	$this->app->offsetUnset(CoreSitemap::class);
    	$this->app->alias(Sitemap::class, 'helper/concrete/dashboard/sitemap');
    	$this->app->alias(Sitemap::class, CoreSitemap::class);
    	$this->app->singleton(Sitemap::class);
    }
}
