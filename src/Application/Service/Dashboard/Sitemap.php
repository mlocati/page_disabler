<?php

namespace Concrete\Package\PageDisabler\Application\Service\Dashboard;

use Concrete\Core\Application\Service\Dashboard\Sitemap as CoreSitemap;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Package\PageDisabler\PageDisabler;

class Sitemap extends CoreSitemap
{
	/**
	 * {@inheritDoc}
	 * @see \Concrete\Core\Application\Service\Dashboard\Sitemap::getNode()
	 */
	public function getNode($cItem, $includeChildren = true, $onGetNode = null)
	{
		$node = parent::getNode($cItem, $includeChildren, $onGetNode);
		if ($node) {
			if ($cItem instanceof Page) {
				$page = $cItem->isError() ? null : $cItem;
			} else {
				$page = Page::getByID($node->cID, 'RECENT');
				if (!$page || $page->isError()) {
					$page = null;
				}
			}
			if ($page !== null && !$page->isSystemPage() && !$page->isAdminArea()) {
			    $app = Application::getFacadeApplication();
			    $pd = $app->make(PageDisabler::class);
			    $isAccessibleByGuest = $pd->isAccessibleByGuest($page);
			    if ($isAccessibleByGuest !== null) {
			        $node->accessibleByGuest = $isAccessibleByGuest;
			        $node->inheritPermissionsFromParent = $page->getCollectionInheritance() === 'PARENT';
			        if (isset($node->iconClass) && $isAccessibleByGuest === false) {
        		        $node->iconClass = $this->getDisabledIcon($node->iconClass);
    			    }
			    }
			}
		}
		return $node;
	}

	private function getDisabledIcon($enabledIcon)
	{
	    $map = array(
	        'fa fa-folder-o' => 'fa fa-folder-open',
	        'fa fa-file-o' => 'fa fa-file',
	        'fa fa-home' => 'fa fa-h-square',
	    );
	    return isset($map[$enabledIcon]) ? $map[$enabledIcon] : $enabledIcon;
	}
}
