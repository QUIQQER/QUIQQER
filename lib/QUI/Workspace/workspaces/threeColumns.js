[{
    "attributes": {
        "resizeLimit"   : [],
        "height"        : 775,
        "width"         : 329,
        "setting_toggle": true
    },
    "children"  : [{
        "attributes": {
            "name"       : "projects-panel",
            "icon"       : "fa fa-home",
            "title"      : "Projects",
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false,
            "height"     : 731
        },
        "type"      : "controls/projects/project/Panel",
        "isOpen"    : true
    }, {
        "attributes": {
            "title"      : "Bookmarks",
            "icon"       : "fa fa-bookmark",
            "footer"     : false,
            "name"       : "qui-bookmarks",
            "height"     : 400,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false
        },
        "type"      : "controls/desktop/panels/Bookmarks",
        "bookmarks" : [],
        "isOpen"    : false
    }]
}, {
    "attributes": {
        "resizeLimit"   : [],
        "height"        : 775,
        "width"         : 984,
        "setting_toggle": false
    },
    "children"  : [{
        "attributes": {
            "title": "My Panel 1",
            "icon" : "fa fa-heart",
            "name" : "tasks"
        },
        "type"      : "qui/controls/desktop/Tasks",
        "bar"       : {
            "attributes": {
                "name"  : "qui-taskbar-issogpue",
                "styles": {
                    "bottom"  : 0,
                    "left"    : 0,
                    "position": "absolute"
                }
            },
            "type"      : "qui/controls/taskbar/Bar",
            "tasks"     : [{
                "attributes": {
                    "closeable": true,
                    "dragable" : true
                },
                "type"      : "qui/controls/taskbar/Task",
                "instance"  : {
                    "attributes": {
                        "closeButton": true,
                        "collapsible": false,
                        "height"     : 745,
                        "dragable"   : true
                    },
                    "type"      : "controls/help/Dashboard"
                }
            }]
        },
        "isOpen"    : true
    }]
}, {
    "attributes": {
        "resizeLimit"   : [],
        "height"        : 775,
        "width"         : 283,
        "setting_toggle": true
    },
    "children"  : [{
        "attributes": {
            "height"     : 687,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false
        },
        "type"      : "qui/controls/messages/Panel",
        "isOpen"    : true
    }, {
        "attributes": {
            "height"     : 300,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false,
            "title"      : "Upload"
        },
        "type"      : "controls/upload/Manager",
        "isOpen"    : false
    }, {
        "attributes": {
            "title"      : "QUIQQER-Hilfe",
            "icon"       : "fa fa-h-square",
            "height"     : 400,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false
        },
        "type"      : "controls/desktop/panels/Help",
        "isOpen"    : false
    }]
}]