[{
    "attributes": {
        "resizeLimit"   : [],
        "height"        : 775,
        "width"         : 373,
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
            "height"     : 599
        },
        "type"      : "controls/projects/project/Panel",
        "isOpen"    : true
    }, {
        "attributes": {
            "title"      : "Bookmarks",
            "icon"       : "fa fa-bookmark",
            "footer"     : false,
            "name"       : "qui-bookmarks",
            "height"     : 300,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false
        },
        "type"      : "controls/desktop/panels/Bookmarks",
        "bookmarks" : [],
        "isOpen"    : false
    }, {
        "attributes": {
            "height"     : 100,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false
        },
        "type"      : "qui/controls/messages/Panel",
        "isOpen"    : false
    }, {
        "attributes": {
            "height"     : 100,
            "collapsible": true,
            "dragable"   : false,
            "closeButton": false,
            "title"      : "Upload"
        },
        "type"      : "controls/upload/Manager",
        "isOpen"    : false
    }]
}, {
    "attributes": {
        "resizeLimit": [],
        "height"     : 775,
        "width"      : 1244
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
                "name"  : "qui-taskbar-issogpst",
                "styles": {
                    "bottom"  : 0,
                    "left"    : 0,
                    "position": "absolute"
                }
            },
            "type"      : "qui/controls/taskbar/Bar",
            "tasks"     : []
        },
        "isOpen"    : true
    }]
}]