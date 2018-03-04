<?php
namespace Concrete\Package\PageDisabler\Controller\Backend;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Page\Page;
use Concrete\Package\PageDisabler\PageDisabler as PageDisablerService;

class PageDisabler extends Controller
{
    public function setEnabled()
    {
        $ajax = $this->app->make('helper/ajax');
        $h = $this->app->make('helper/concrete/dashboard/sitemap');
        if (!$h->canRead()) {
            $ajax->sendError(t('Access Denied'));
        } else {
            $post = $this->request->request;
            $t = $this->app->make('token');
            if (!$t->validate('page_disabler.setEnabled', $post->get('token'))) {
                $ajax->sendError($t->getErrorMessage());
            } else {
                $pageID = $post->get('pageID');
                $page = Page::getByID($pageID, 'RECENT');
                if (!$page || $page->isError() || $page->isSystemPage() || $page->isAdminArea()) {
                    $ajax->sendError('Bad page received');
                } else {
                    if ($post->get('action') === 'enable') {
                        $enable = true;
                    } elseif ($post->get('action') === 'disable') {
                        $enable = false;
                    } else {
                        $enable = null;
                    }
                    if ($enable === null) {
                        $ajax->sendError('Bad enabled parameter received');
                    } else {
                        $pd = $this->app->make(PageDisablerService::class);
                        $pd->setAccessibleByGuest($page, $enable);
                        $ajax->sendResult(array(
                            'success' => true,
                            'pageID' => $pageID,
                            'enabled' => $enable
                        ));
                    }
                }
            }
        }
    }
}
