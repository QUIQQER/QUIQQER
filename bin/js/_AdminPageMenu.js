
if ( typeof _pcsg == 'undefined' ) {
    var _pcsg = {};
}

_pcsg.AdminPageMenu =
{
    Menu : null,

    load : function()
    {
        var oMenu = document.createElement('div');
        var oLink = document.createElement('a');
        var style = oMenu.style;

        style.position   = 'fixed';
        style.right      = '50px';
        style.bottom     = '50px';
        style.border     = '2px solid #24405E';
        style.background = '#CAD9E4';
        style.color      = '#24405E';
        style.width      = '250px';
        style.zIndex     = 10000;

        style = oLink.style;
        style.cssFloat   = 'left';
        style.styleFloat = 'left';
        style.clear      = 'both';
        style.padding    = '10px';
        style.fontSize   = '12px';
        style.color      = '#24405E';

        // Menü aufbauen
        oMenu.innerHTML = '<span style="background-color: #24405E; color: #fff; float: left; width: 250px; font-size: 10px">' +
            '<span style="margin: 5px; float: left; font-size: 10px; font-weight: bold;">' +
                'PCSG Quick Edit Menü' +
            '</span>' +
        '</span>';

        // Quickedit
        var oEdit = oLink.cloneNode(true);
        oEdit.innerHTML = 'Aktuelle Seite bearbeiten';
        oEdit.target    = '_blank';
        oEdit.href      = _pcsg.admin.link +'?' +
            'move_to_id='+ _pcsg.Site.id +
            '&move_to_project='+ _pcsg.Project.name +
            '&move_to_lang='+ _pcsg.Project.lang;

        oMenu.appendChild( oEdit );

        // Admin
        oEdit = oLink.cloneNode(true);
        oEdit.innerHTML = 'Administration öffnen';
        oEdit.target    = '_blank';
        oEdit.href      = _pcsg.admin.link;

        oMenu.appendChild( oEdit );

        document.body.appendChild( oMenu );
    }
};

_pcsg.AdminPageMenu.load();
