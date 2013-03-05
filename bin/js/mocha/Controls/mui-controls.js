/*
 ---
 name: MUI-Controls

 script: mui-controls.js

 description: Root MochaUI Controls - Loads/Configures all MochaUI controls.

 copyright: (c) 2011 Contributors in (/AUTHORS.txt).

 license: MIT-style license in (/MIT-LICENSE.txt).

 note:
 This documentation is taken directly from the javascript source files. It is built using Natural Docs.
 ...
 */

/*
Object.append(MUI.controls, {

    'textarea':{'samples':['index','demo'],data:['json','html'],'description':'Text Area','isFormControl':true,'css':['{theme}css/textbox.css']},
    'textbox':{'samples':['index','demo'],data:['json'],'description':'Text Box','isFormControl':true,'css':['{theme}css/textbox.css']},
    'calendar':{'samples':['demo','index'],data:['json'],'description':'Calendar','isFormControl':true,'css':['{theme}css/calendar.css']},
    'checkboxgrid':{'samples':['demo','index'],data:['json'],'description':'Check Box Grid','isFormControl':true,'css':['{theme}css/checkboxgrid.css']},
    'imagebutton':{'samples':['demo','index'],data:['json'],'description':'Image Button','isFormControl':true,'css':['{theme}css/imagebutton.css']},
    'grid':{'samples':['index'],data:['json'],'description':'Grid','isFormControl':true,'css':['{theme}css/grid.css']},
    'selectlist':{'samples':['demo','index'],data:['json','html'],'description':'Grid View','isFormControl':true,'css':['{theme}css/selectlist.css']},
    'stepper':{'samples':['demo','index'],data:['json'],'description':'Stepper','isFormControl':true,'css':['{theme}css/stepper.css']},
    'accordion':{'samples':['demo','index'],data:['json','html'],'description':'Accordion','isFormControl':false,'css':['{theme}css/accordion.css']},
    'column':{'samples':['demo'],data:['json'],'description':'Column','isFormControl':false,'childNode':'panels','childType':'MUI.Panel','css':['{theme}css/desktop.css']},
    'list':{'samples':['demo','index'],data:['json'],'description':'Basic List','isFormControl':false,'css':['{theme}css/list.css']},
    'panel':{'samples':['demo'],data:['json'],'description':'Panel','isFormControl':false,'childNode':'content','childType':'MUI.Panel','css':['{theme}css/desktop.css']},
    'tabs':{'samples':['index'],data:['json','html'],'description':'Tabs','isFormControl':false,'css':['{theme}css/tab.css']},
    'taskbar':{'samples':['demo'],data:['json'],'description':'Taskbar','isFormControl':false,'css':['{theme}css/taskbar.css']},
    'tree':{'samples':['index','demo'],data:['json'],'description':'Tree','isFormControl':false,'css':['{theme}css/tree.css']},
    'toolbar':{'samples':['index'],data:['json'],'description':'Toolbar','isFormControl':false,'css':['{theme}css/toolbar.css']},
    'dock':{'samples':['index'],data:['json'],'description':'Toolbar Dock','isFormControl':false,'childNode':'docked','dependsOn':['MUI.DockHtml'],'css':['{theme}css/toolbar.css']},
    'dockhtml':{'samples':['index'],data:['json'],'description':'Toolbar HTML Block','isFormControl':false,'css':['{theme}css/toolbar.css']},
    'menu':{'samples':['index'],data:['json'],'description':'Toolbar Menu','isFormControl':false,'css':['{theme}css/menu.css']},
    'spinner':{'samples':['index'],data:['json'],'description':'Toolbar Spinner','isFormControl':false,'css':['{theme}css/toolbar.css']},
    'themechange':{'samples':['index'],data:['json'],'description':'Toolbar Menu','isFormControl':false,'css':['{theme}css/toolbar.css']},

    'window' : {
        'dependsOn':['MUI.Toolbar'],
        'samples':['demo'],
        data:['json'],
        'description':'Window',
        'isFormControl':false,
        'childNode':'content',
        'css':['{theme}css/window.css']
    },

    'modal': {
        'dependsOn':['MUI.Window'],
        'samples':['demo'],
        data:['json'],
        'description':'Modal',
        'isFormControl':false,
        'css':['{theme}css/desktop.css'],
        location:'window'
    },

    desktop: {
        samples       : ['demo'],
        data          : ['json'],
        description   : 'Desktop',
        isFormControl : false,
        childNode     : 'content',
        childType     : 'MUI.DesktopColumns',
        dependsOn     : ['MUI.Dock', 'MUI.Taskbar'],
        css           : ['{theme}css/desktop.css']
    },

    'desktopcolumns':{'hide':true,'loadOnly':true,'description':'used to map columns in MUI.Desktop content section','childNode':'columns','childType':'MUI.Column','js':[],'css':[]}

});
*/
