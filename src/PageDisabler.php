<?php
namespace Concrete\Package\PageDisabler;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Access\Access;
use Concrete\Core\Permission\Access\Entity\GroupEntity;
use Concrete\Core\Permission\Key\PageKey;
use Exception;

class PageDisabler
{

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var Repository
     */
    protected $packageConfig;

    /**
     * @var PageKey|null
     */
    protected $viewPageKey;

    /**
     * @param Repository $config
     */
    public function __construct(Application $app, Repository $config)
    {
        $this->packageConfig = $app->make('page_disabler/config');
        $this->config = $config;
    }

    /**
     * @param Page $page
     *
     * @return bool|null
     */
    public function isAccessibleByGuest(Page $page)
    {
        return $this->isAdvancedPermissionsEnabled() ? $this->isAccessibleByGuest_Advanced($page) : $this->isAccessibleByGuest_Simple($page);
    }

    /**
     * @param Page $page
     * @param bool $accessible
     * @throws Exception
     */
    public function setAccessibleByGuest(Page $page, $accessible)
    {
        return $this->isAdvancedPermissionsEnabled() ? $this->setAccessibleByGuest_Advanced($page, $accessible) : $this->setAccessibleByGuest_Simple($page, $accessible);
    }

    /**
     * @return bool
     */
    protected function isAdvancedPermissionsEnabled()
    {
        return $this->config->get('concrete.permissions.model') !== 'simple';
    }

    /**
     * @return PageKey
     */
    protected function getViewPageKey()
    {
        if ($this->viewPageKey === null) {
            $this->viewPageKey = PageKey::getByHandle('view_page');
        }

        return $this->viewPageKey;
    }

    /**
     * @param Page $page
     *
     * @return bool
     */
    protected function isAccessibleByGuest_Simple(Page $page)
    {
        $result = false;
        $pk = $this->getViewPageKey();
        $pk->setPermissionObject($page);
        $pao = $pk->getPermissionAccessObject();
        $assignments = $pao ? $pao->getAccessListItems() : array();
        /* @var \Concrete\Core\Permission\Access\ListItem\PageListItem[] $assignments */
        foreach ($assignments as $assignment) {
            $ae = $assignment->getAccessEntityObject();
            if ($ae->getAccessEntityTypeHandle() == 'group') {
                /* \Concrete\Core\Permission\Access\Entity\GroupEntity $ae */
                $group = $ae->getGroupObject();
                if (is_object($group) && $group->getGroupID() == GUEST_GROUP_ID) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param Page $page
     *
     * @return bool|null
     */
    protected function isAccessibleByGuest_Advanced(Page $page)
    {
        return null;
    }

    /**
     * @param Page $page
     * @param bool $accessible
     */
    protected function setAccessibleByGuest_Simple(Page $page, $accessible)
    {
        if ($this->isAccessibleByGuest($page) !== $accessible) {
            $page->setPermissionsToManualOverride();
            $pk = $this->getViewPageKey();
            $pk->setPermissionObject($page);
            $accessibleGroups = array();
            $pao = $pk->getPermissionAccessObject();
            $assignments = $pao ? $pao->getAccessListItems() : array();
            /* @var \Concrete\Core\Permission\Access\ListItem\PageListItem[] $assignments */
            foreach ($assignments as $assignment) {
                $ae = $assignment->getAccessEntityObject();
                if ($ae->getAccessEntityTypeHandle() == 'group') {
                    /* \Concrete\Core\Permission\Access\Entity\GroupEntity $ae */
                    $group = $ae->getGroupObject();
                    if (is_object($group) && $group->getGroupID() == GUEST_GROUP_ID) {
                        break;
                    }
                }
            }
            if ($accessible) {
                $accessibleGroups[GUEST_GROUP_ID] = \Group::getByID(GUEST_GROUP_ID);
            } else {
                unset($accessibleGroups[GUEST_GROUP_ID]);
                if (empty($accessibleGroups)) {
                    $gID = (int) $this->packageConfig->get('access.ensure_user_group');
                    if ($gID === 0) {
                        $gID = ADMIN_GROUP_ID;
                    }
                    $accessibleGroups[$gID] = \Group::getByID($gID);
                }
            }
            $pa = Access::create($pk);
            foreach ($accessibleGroups as $group) {
                $pa->addListItem(GroupEntity::getOrCreate($group));
            }
            $pao = $pk->getPermissionAssignmentObject();
            $pao->clearPermissionAssignment();
            $pao->assignPermissionAccess($pa);
        }
    }

    /**
     * @param Page $page
     * @param bool $accessible
     * @throws Exception
     */
    protected function setAccessibleByGuest_Advanced(Page $page, $accessible)
    {
        throw new Exception('Advanced Mode not implemented');
    }
}
