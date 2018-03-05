<?php
namespace Concrete\Package\PageDisabler;

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Http\Request;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\View\View;
use Concrete\Package\PageDisabler\Controller\Backend\PageDisabler;
use Symfony\Component\EventDispatcher\GenericEvent;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{

    /**
     * The package handle.
     *
     * @var string
     */
    protected $pkgHandle = 'page_disabler';

    /**
     * The package version.
     *
     * @var string
     */
    protected $pkgVersion = '0.0.4';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '5.7.5.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderRegistries
     */
    protected $pkgAutoloaderRegistries = array(
        'src' => 'Concrete\Package\PageDisabler',
    );

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderMapCoreExtensions
     */
    protected $pkgAutoloaderMapCoreExtensions = true;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Page Disabler');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Disable or enable pages with the site map context menu.');
    }

    public function on_start()
    {
        $app = isset($this->app) ? $this->app : Application::getFacadeApplication();
        $this->registerServiceProvider($app);
        $this->registerRoutes($app);
        $this->registerAssets($app);
        $dispatcher = $app->make('director');
        $dispatcher->addListener('on_before_render', function (GenericEvent $e) use ($app) {
            $request = Request::getInstance();
            switch ($request->getPathInfo()) {
                case '/dashboard/sitemap/full':
                    $resolver = $app->make(ResolverManagerInterface::class);
                    $token = $app->make('token');
                    $dynamicData = json_encode(array(
                        'i18n' => array(
                            'Enable' => t('Enable'),
                            'Disable' => t('Disable')
                        ),
                        'actions' => array(
                            'setEnabled' => array(
                                'url' => (string) $resolver->resolve(array(
                                    '/page-disabler/set-enabled'
                                )),
                                'token' => $token->generate('page_disabler.setEnabled')
                            )
                        )
                    ));
                    $view = View::getRequestInstance();
                    $view->addFooterAsset("<script>window.PageDisablerSitemapData = {$dynamicData};</script>");
                    $view->requireAsset('page_disabler/dashboard/sitemap');
                    break;
            }
        });
    }

    protected function registerServiceProvider(\Concrete\Core\Application\Application $app)
    {
        id(new ServiceProvider($app))->register();
    }

    protected function registerRoutes(\Concrete\Core\Application\Application $app)
    {
        $router = $app->make(RouterInterface::class);
        $router->registerMultiple(array(
            '/page-disabler/set-enabled' => array(
                PageDisabler::class . '::setEnabled'
            )
        ));
    }

    protected function registerAssets(\Concrete\Core\Application\Application $app)
    {
        $al = AssetList::getInstance();
        $al->registerMultiple([
            'page_disabler/dashboard/sitemap' => [
                ['javascript', 'js/dashboard/sitemap.js', ['minify' => true, 'combine' => true, 'position' => Asset::ASSET_POSITION_FOOTER], $this],
            ],
        ]);
        $al->registerGroupMultiple([
            'page_disabler/dashboard/sitemap' => [
                [
                    ['javascript', 'page_disabler/dashboard/sitemap'],
                ],
            ],
        ]);
    }
}
