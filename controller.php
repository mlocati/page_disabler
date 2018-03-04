<?php
namespace Concrete\Package\PageDisabler;

use Concrete\Core\Http\Request;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\PageDisabler\Controller\Backend\PageDisabler;
use Symfony\Component\EventDispatcher\GenericEvent;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;

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
    protected $pkgVersion = '0.0.1';

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
    protected $pkgAutoloaderRegistries = [
        'src' => 'Concrete\Package\PageDisabler'
    ];

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
        if (! $app->isRunThroughCommandLineInterface()) {
            $this->registerServiceProvider($app);
            $this->registerRoutes($app);
            $request = Request::getInstance();
            switch ($request->getPathInfo()) {
                case '/dashboard/sitemap/full':
                    $dispatcher = $app->make('director');
                    $resolver = $app->make(ResolverManagerInterface::class);
                    $token = $app->make('token');
                    $assetUrl = $this->getRelativePath() . '/js/dashboard/sitemap.js?' . $this->pkgVersion;
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
                    $inject = <<<EOT
<script>
window.PageDisablerSitemapData = {$dynamicData};
</script>
<script src="{$assetUrl}"></script>

EOT
;
                    $dispatcher->addListener('on_page_output', function (GenericEvent $e) use ($inject) {
                        $contents = $e->getArgument('contents');
                        $contents = preg_replace('/<\/body>/i', $inject . '$0', $contents);
                        $e->setArgument('contents', $contents);
                    });
                    break;
            }
        }
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
}
