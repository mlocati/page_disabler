/* jshint unused:vars, undef:true, browser:true, jquery:true, -W014, -W018 */

;(function($) {
'use strict';

var DISABLED_ICON_MAP = {
    'fa fa-folder-o': 'fa fa-folder-open',
    'fa fa-file-o': 'fa fa-file',
    'fa fa-home': 'fa fa-h-square'
};

function HookedMenu($element, $menu, data)
{
    var me = this;
    me.$element = $element;
    me.$menu = $menu;
    me.data = data;
    me.$menu
        .find('ul.dropdown-menu li.divider:first')
        .after($('<li class="divider" />'))
        .after($('<li />')
            .append(me.$link = $('<a href="#" />')
                .on('click', function (e) {
                    e.preventDefault();
                    me.toggleAccessibility();
                })
            )
        )
    ;
    me.accessibilityUpdated();
}
HookedMenu.prototype = {
    getIcon: function() {
        var $icon = this.$element.prev();
        return $icon.length === 1 && $icon.hasClass('fa') ? $icon : null;
    },
    accessibilityUpdated: function(iconToo) {
        var me = this;
        me.$link.text(me.data.accessibleByGuest ? window.PageDisablerSitemapData.i18n.Disable : window.PageDisablerSitemapData.i18n.Enable);
        if (iconToo) {
            var $icon = me.getIcon();
            if ($icon !== null) {
                $.each(DISABLED_ICON_MAP, function (enabledIcon, disabledIcon) {
                    if (me.data.accessibleByGuest) {
                        if ($icon.hasClass(disabledIcon)) {
                            $icon.removeClass(disabledIcon).addClass(enabledIcon);
                            return false;
                        }
                    } else {
                        if ($icon.hasClass(enabledIcon)) {
                            $icon.removeClass(enabledIcon).addClass(disabledIcon);
                            return false;
                        }
                    }                    
                });
            }
        }
    },
    toggleAccessibility: function() {
        var me = this;
        new window.ConcreteAjaxRequest({
            url: window.PageDisablerSitemapData.actions.setEnabled.url,
            data: {
                token: window.PageDisablerSitemapData.actions.setEnabled.token,
                pageID: me.data.cID,
                action: me.data.accessibleByGuest ? 'disable' : 'enable'
            },
            success: function(data) {
                me.data.accessibleByGuest = data.enabled;
                me.accessibilityUpdated(true);
            }
        });
    }
};
HookedMenu.create = function ($element, $menu, data) {
    if (
        !$element instanceof $ || $element.length !== 1
        || !$menu instanceof $ || $menu.length !== 1
        || !data || !data.cID || typeof data.accessibleByGuest !== 'boolean'
    ) {
        return;
    }
    return new HookedMenu($element, $menu, data);
};

function onConcreteMenuShow(evt, evtData)
{
    HookedMenu.create(
        evtData && evtData.menu ? evtData.menu.$element || null : null,
        evtData && evtData.menu ? evtData.menu.$menuPointer || null : null,
        evtData && evtData.menu && evtData.menu.options ? evtData.menu.options.data || null : null
    );
}

$(document).ready(function() {
    window.ConcreteEvent.subscribe('ConcreteMenuShow', onConcreteMenuShow);
});

})(jQuery);
