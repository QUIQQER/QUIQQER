
/**
 * OmniGrid Table Object
 *
 * based on Author: Marko Šantić
 * Version: OmniGrid 1.2.6
 *
 * Complete rewrited by www.pcsg.de (Henning Leutz)
 *
 * @module controls/grid/Grid
 * @author www.pcsg.de (Henning Leutz)
 *
 * @fires onClick
 * @fires onDblClick
 * @fires onContextMenu
 * @fires onBlur
 * @fires onFocus
 * @fires onMouseDown
 * @fires onMouseDown
 * @fires onEditComplete
 * @fires onLoadData
 * @fires onStart - on Sort Start
 * @fires onAutoColumModel
 * @fires onRefresh
 *
 * @fires onDragDropStart
 * @fires onDragDropComplete
 * @fires onDragDropEnter
 * @fires onDragDropLeave
 * @fires onDrop
 *
 * @licence: MIT licence
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Seperator
 * @require qui/utils/Controls
 * @require css!controls/grid/Grid.css
 */

define('controls/grid/Grid', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/contextmenu/Menu',
    'qui/controls/contextmenu/Item',
    'qui/utils/Controls',

    'css!controls/grid/Grid.css'

], function(QUIControl, QUIButton, QUISeperator, QUIContextMenu, QUIContextItem, ControlUtils)
{
    "use strict";

    /**
     * @class controls/grid/Grid
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/grid/Grid',

        options : {
            name          : false,
            alternaterows : true,
            showHeader    : true,
            sortHeader    : true,
            resizeColumns : true,
            selectable    : true,
            serverSort    : false,
            sortOn        : null,
            sortBy        : 'ASC',
            filterHide    : true,
            filterHideCls : 'hide',

            filterSelectedCls : 'filter',
            multipleSelection : false,
            editable          : false,   // Grid.addEvent('editcomplete', function(data) // selectable muss "true" sein!
            editondblclick    : false,
            editType          : 'input', // textarea | input
            resizeHeaderOnly  : false,

            // accordion
            accordion               : false,
            openAccordionOnClick    : true,
            accordionRenderer       : null,
            accordionLiveRenderer   : null,
            autoSectionToggle       : true, // if true just one section can be open/visible
            showtoggleicon          : true,
            toggleiconTitle         : 'Details',
            openAccordionOnDblClick : false,

            // pagination
            url            : null,
            pagination     : false,
            page           : 1,
            perPageOptions : [5,10, 20, 50, 75, 100, 150, 200, 250, 500, 750, 1000, 2500, 5000],
            perPage        : 20,
            filterInput    : true,
            // dataProvider
            dataProvider   : null,

            //export
            exportData     : false,
            exportTypes    : {
                pdf  : 'PDF',
                csv  : 'CSV',
                json : 'JSON'
            }, // {print : 'Drucken', pdf : 'PDF', csv : 'CSV', json : 'JSON'},
            exportRenderer : null, // function(data){data.type data.data data.Grid}
            exportBinUrl   : URL_BIN_DIR +'js/extern/omnigrid1.2.3/omnigrid/',

            // drag & Drop
            dragdrop          : false,
            droppables        : [],
            dragDropDataIndex : '',
            dragDropClass     : false
        },

        $data           : false,
        $columnModel    : false,
        $refreshDelayID : null,

        initialize : function(container, options)
        {
            // column model
            if ( typeof options.columnModel !== 'undefined' )
            {
                this.$columnModel = options.columnModel;
                delete options.columnModel;
            } else
            {
                this.$columnModel = {};
            }

            this.parent( options );


            this.container  = typeOf(container) === 'string' ? document.id(container) : container;
            this._stopdrag  = false;
            this._dragtimer = false;
            this._mousedown = false;

            this.$data = [];
            this.$Menu = false;

            if ( !this.container ) {
                return;
            }

            //instanz name für element ids
            if ( !this.getAttribute( 'name' ) ) {
                this.setAttribute( 'name', this.getId() );
            }

            this.container.set({
                'tabindex' : '-1',
                styles : {
                    'MozOutline' : 'none',
                    'outline'    : 0
                },
                events : {
                    'focus'     : this.focus.bind( this ),
                    'blur'      : this.blur.bind( this ),
                    'mousedown' : this.mousedown.bind( this ),
                    'mouseup'   : this.mouseup.bind( this )
                },
                'data-quiid' : this.getId()
            });

            this.draw();
            this.resize();
            this.reset();
            this.loadData();
        },

        destroy : function()
        {
            this.removeAll();

            this.container.empty();
            this.container.setStyles({
                width  : '',
                height : ''
            });

            this.container.removeClass( 'omnigrid' );
        },

        // API
        reset : function()
        {
            var t = this;

            t.renderData();

            t.$refreshDelayID = null;
            t.dragging        = false;
            t.selected        = [];

            t.elements = t.ulBody.getElements( 'li' );

            t.filtered    = false;
            t.lastsection = null;

            if ( t.getAttribute( 'alternaterows' ) ) {
                t.altRow();
            }

            // Setup header
            t.container.getElements('.th').each(function(el, i)
            {
                //alert(el.dataType);
                var dataType = el.retrieve( 'dataType' );

                if ( !dataType ) {
                    return;
                }

                el.getdate = function(str)
                {
                    function fixYear(yr)
                    {
                        yr = +yr;

                        if ( yr < 50 )
                        {
                            yr += 2000;
                        } else if (yr<100)
                        {
                            yr += 1900;
                        }

                        return yr;
                    }

                    var ret, strtime;

                    if ( str.length > 12 )
                    {
                        strtime = str.substring( str.lastIndexOf(' ') + 1 );
                        strtime = strtime.substring( 0, 2 ) + strtime.substr( -2 );
                    } else
                    {
                        strtime = '0000';
                    }

                    // YYYY-MM-DD
                    if ( (ret=str.match(/(\d{2,4})-(\d{1,2})-(\d{1,2})/)) ) {
                        return (fixYear(ret[1])*10000) + (ret[2]*100) + (+ret[3]) + strtime;
                    }

                    // DD/MM/YY[YY] or DD-MM-YY[YY]
                    if ( (ret=str.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/)) ) {
                        return ( fixYear(ret[3])*10000 ) + (ret[2]*100) + (+ret[1]) + strtime;
                    }

                    return 999999990000; // So non-parsed dates will be last, not first
                };

                el.findData = function(elem)
                {
                    var child = elem.getFirst();

                    if ( child ) {
                        return el.findData( child );
                    }

                    return elem.innerHTML.trim();
                };

                el.stripHTML = function(str)
                {
                    var tmp = str.replace(/(<.*['"])([^'"]*)(['"]>)/g,
                        function(x, p1, p2, p3) { return  p1 + p3;}
                    );

                    return tmp.replace(/<\/?[^>]+>/gi, '');
                };

                el.compare = function(a, b)
                {
                    // a i b su LI elementi
                    var var1 = a.getChildren()[i].innerHTML.trim(),
                        var2 = b.getChildren()[i].innerHTML.trim();

                    if (dataType == 'number' || dataType == 'integer' || dataType == 'int')
                    {
                        var1 = parseFloat( el.stripHTML( var1 ) );
                        var2 = parseFloat( el.stripHTML( var2 ) );

                        if ( el.sortBy == 'ASC' ) {
                            return var1-var2;
                        }

                        return var2-var1;
                    }

                    if ( dataType == 'string' )
                    {
                        var1 = var1.toUpperCase();
                        var2 = var2.toUpperCase();

                        if ( var1 == var2 ) {
                            return 0;
                        }

                        if ( el.sortBy == 'ASC' && var1 < var2 ) {
                            return -1;
                        }

                        return 1;
                    }

                    if ( dataType == 'date' )
                    {
                        var1 = parseFloat( el.getdate( var1 ) );
                        var2 = parseFloat( el.getdate( var2 ) );

                        if ( el.sortBy == 'ASC' ) {
                            return var1-var2;
                        }

                        return var2-var1;
                    }

                    if ( dataType == 'currency' )
                    {
                        var1 = parseFloat( var1.substr( 1 ).replace( ',', '' ) );
                        var2 = parseFloat( var2.substr( 1 ).replace( ',', '' ) );

                        if ( el.sortBy == 'ASC' ) {
                            return var1-var2;
                        }

                        return var2-var1;
                    }
                };

            }, t);

            t.altRow();
        },

        /**
         * Resize the grid
         */
        resize : function()
        {
            var Container = this.container,
                width     = Container.getSize().x,
                buttons   = Container.getElements( '.tDiv button'),
                menuWidth = 0;

            buttons.setStyle( 'display', null );

            if ( this.$Menu )
            {
                this.$Menu.hide();

                menuWidth = this.$Menu.getElm().getSize().x;
            }

            var sumWidth = buttons.map(function(Button) {
                    return Button.getComputedSize().totalWidth;
                }).sum() - menuWidth + ( buttons.length * 10 );

            if ( sumWidth > width )
            {
                // hide buttons
                buttons.setStyle( 'display', 'none' );

                if ( this.$Menu ) {
                    this.$Menu.show();
                }

            } else
            {
                // show buttons
                buttons.setStyle( 'display', null );

                if ( this.$Menu ) {
                    this.$Menu.hide();
                }
            }
        },

        /**
         * Return pagination data for ajax request
         *
         * @return {Object}
         */
        getPaginationData : function()
        {
            return  {
                perPage : this.getAttribute( 'perPage' ),
                page    : this.getAttribute( 'page' )
            };
        },

        // API
        // pretvara zadanu columnu u inline edit mode
        // options = {
        //        dataIndex:Number - column name || columnIndex:Number - column index
        //}
        edit: function(options)
        {
            var li;
            var t    = this,
                sels = t.getSelectedIndices();

            if ( !sels || sels.length === 0 || !t.getAttribute('editable') ) {
                return;
            }

            if ( options.li )
            {
                li = options.li;
            } else
            {
                li = t.elements[ sels[0] ];
            }

            t.finishEditing();

            // nadi index u columnModel
            var c = options.columnIndex || 0;
            var colmod, len;

            if ( options.dataIndex )
            {
                for ( len = this.$columnModel.length; c < len; c++ )
                {
                    colmod = this.$columnModel[c];

                    if ( colmod.hidden ) {
                        continue;
                    }

                    if ( colmod.dataIndex == options.dataIndex ) {
                        break;
                    }
                }
            }

            if ( c == this.$columnModel.length ) {
                return;
            }

            colmod = this.$columnModel[c];

            if ( !colmod.editable ) {
                return;
            }

            var td       = li.getElements('div.td')[c],
                data     = this.$data[ sels[0] ],
                width    = td.getStyle('width').toInt()-5,
                html     = data[ colmod.dataIndex ],
                editType = colmod.editType ? colmod.editType : this.getAttribute('editType');

            td.innerHTML = '';

            var input = new Element(editType, {
                'class' : 'inline',
                style   : "width: " + width + "px; height: auto;",
                value   : html,
                title   : 'Doppelklick oder Enter um die änderungen zu übernehmen',
                events : {
                    keyup    : t.finishEditing.bind( this ),
                    blur     : t.finishEditing.bind( this ),
                    dblclick : t.finishEditing.bind( this )
                }
            });

            if ( this.getAttribute('editType') == 'textarea' ) {
                input.setAttribute('title', 'Doppelklick mit der linken Maustaste um die Änderungen zu übernehmen');
            }

            input.inject( td );
            input.focus();

            t.inlineEditSafe = {
                row         : sels[0],
                columnModel : colmod,
                td          : td,
                input       : input,
                oldvalue    : html
            };

            t.inlineeditmode = true;

            return t.inlineEditSafe;
        },

        finishEditing: function(evt)
        {
            var t = this;

            if ( !t.inlineeditmode ) {
                return;
            }

            if ( evt &&
                evt.type == "keyup" &&
                evt.key != 'enter' &&
                evt.key != 'esc' &&
                evt.type != 'dblclick' )
            {
                return;
            }

            var row      = t.inlineEditSafe.row,
                data     = this.$data[ row ],
                colmod   = t.inlineEditSafe.columnModel,
                td       = t.inlineEditSafe.td,
                editType = colmod.editType ? colmod.editType : this.getAttribute('editType');

            if ( editType == 'textarea' &&
                evt &&
                evt.key != 'esc' &&
                evt.type != 'dblclick' )
            {
                return;
            }

            t.inlineeditmode = false;

            if ( editType == 'input' )
            {
                if ( ( evt && ( (evt.type == 'keyup' && evt.key == 'enter' ) || (evt.type == 'dblclick')) ) )
                {
                    data[ colmod.dataIndex ] = t.inlineEditSafe.input.value;
                } else
                {
                    data[ colmod.dataIndex ] = t.inlineEditSafe.oldvalue;
                }
            }

            if ( editType == 'textarea' )
            {
                if ( evt && evt.type == 'dblclick' )
                {
                    data[ colmod.dataIndex ] = t.inlineEditSafe.input.value;
                } else
                {
                    data[ colmod.dataIndex ] = t.inlineEditSafe.oldvalue;
                }
            }

            if ( typeof data[ colmod.dataIndex ] === 'undefined' || !data[ colmod.dataIndex ] ) {
                data[ colmod.dataIndex ] = '';
            }


            td.innerHTML = colmod.labelFunction ? colmod.labelFunction(data, row, colmod) : data[colmod.dataIndex];

            if (td.innerHTML.length === 0) {
                td.innerHTML = '&nbsp;';
            }

            // Key Events
            if ( evt && evt.type == 'keyup' &&
                evt.key == 'enter' && t.inlineEditSafe.oldvalue != td.innerHTML )
            {
                t.inlineEditSafe.target = t;
                t.fireEvent("editComplete", t.inlineEditSafe);
            }

            // bei dbl click auch speichern ausführen
            if ( evt && evt.type == 'dblclick' &&
                t.inlineEditSafe.oldvalue != td.innerHTML )
            {
                t.inlineEditSafe.target = t;
                t.fireEvent("editComplete", t.inlineEditSafe);
            }

            t.inlineEditSafe = null;
        },

        toggle : function(el)
        {
            if ( typeof el.style === 'undefined' ) {
                return;
            }

            if ( el.style.display == 'block' )
            {
                el.style.display = 'none';
                return;
            }

            el.style.display = 'block';
        },

        // API
        getSection : function(row)
        {
            return this.ulBody.getElement( '.section-'+ row );
        },

        // API
        removeSections : function()
        {
            var i, len;
            var sections = this.ulBody.getElements('.section');

            if ( this.getAttribute('showtoggleicon') ) {
                this.ulBody.getElements('.toggleicon').setStyle('background-position', '0 0');
            }

            for ( i = 0, len = sections.length; i < len; i++ ) {
                this.ulBody.removeChild( sections[i] );
            }
        },

        getLiParent: function(target)
        {
            if ( !target ) {
                return false;
            }

            if ( target && !target.hasClass('td') ) {
                target = this.getTdParent(target);
            }

            if ( target ) {
                return target.getParent();
            }
        },

        getTdParent: function(target)
        {
            if ( !target ) {
                return;
            }

            if ( target && !target.hasClass('td') ) {
                target = target.getParent('.td');
            }

            if ( target ) {
                return target;
            }
        },

        focus : function()
        {
            this.fireEvent( 'focus' );
        },

        blur : function()
        {
            this.fireEvent( 'blur', [ this ] );
        },

        mousedown : function()
        {
            this.fireEvent( 'mouseDown', [ this ] );
        },

        mouseup : function()
        {
            this.fireEvent( 'mouseUp', [ this ] );
        },

        onRowMouseOver : function(evt)
        {
            var li = this.getLiParent( evt.target );

            if ( !li ) {
                return;
            }

            if ( !this.dragging ) {
                li.addClass('over');
            }

            if ( !evt.target || typeof evt.target.getParent !== 'function' ) {
                return;
            }

            this.fireEvent("mouseOver", {
                target  : this,
                row     : li.retrieve('row'),
                element : li
            });
        },

        onRowMouseOut : function(evt)
        {
            var li = this.getLiParent( evt.target );

            if ( !li ) {
                return;
            }

            if ( !this.dragging ) {
                li.removeClass('over');
            }

            if ( !evt.target || typeof evt.target.getParent !== 'function' ) {
                return;
            }

            this.fireEvent("mouseOut", {
                target  : this,
                row     : li.retrieve('row'),
                element : li
            });
        },

        onRowMouseDown : function(event)
        {
            if ( this._mousedown ) {
                return;
            }

            this._stopdrag  = false;
            this._dragtimer = this.startDrag.delay(200, this, event);
        },

        onRowMouseUp : function(event)
        {
            // stop drag an drop
            if ( !this.getAttribute('dragdrop') ) {
                return false;
            }

            // if dragdrop is start
            if ( this.Drag )
            {
                this.Drag.fireEvent( 'mouseUp', event );
                return;
            }

            if ( this._dragtimer ) {
                clearTimeout( this._dragtimer );
            }

            this._dragtimer = false;
            this._stopdrag  = true;
        },

        onRowClick : function(evt)
        {
            var i, len, row;

            var t  = this,
                li = this.getLiParent( evt.target ),
                onclick = false;

            if ( !li ) {
                return;
            }

            if ( typeof li.focus !== 'undefined' ) {
                li.focus();
            }

            row = li.retrieve('row');

            if ( t.getAttribute('selectable') )
            {
                var selectedNum  = t.selected.length,
                    dontselect   = false;

                if ( (!evt.control && !evt.shift && !evt.meta) ||
                    !t.getAttribute('multipleSelection') )
                {
                    for ( i = 0, len = t.elements.length; i < len; i++ ) {
                        t.elements[i].removeClass('selected');
                    }

                    t.selected = [];
                }

                if ( evt.control || evt.meta )
                {
                    for ( i = 0; i < selectedNum; i++ )
                    {
                        if ( row == t.selected[i] )
                        {
                            t.elements[ row ].removeClass('selected');
                            t.selected.splice( i, 1 );

                            dontselect = true;
                        }
                    }
                }

                if ( evt.shift && t.getAttribute('multipleSelection') )
                {
                    var si = 0;

                    if ( t.selected.length > 0 ) {
                        si = t.selected[selectedNum-1];
                    }

                    var endindex   = row;
                    var startindex = Math.min(si, endindex);

                    endindex = Math.max(si, endindex);

                    for ( i = startindex; i <= endindex; i++ )
                    {
                        t.elements[i].addClass('selected');
                        t.selected.push( Number(i) );
                    }
                }

                if ( !dontselect )
                {
                    li.addClass('selected');
                    t.selected.push( Number( row ) );
                }

                t.selected = t.unique( t.selected, true );
            }

            if ( t.getAttribute('accordion') &&
                t.getAttribute('openAccordionOnClick') &&
                !t.getAttribute('openAccordionOnDblClick') )
            {
                t.accordianOpen( li );
            }

            if ( (onclick = t.$data[ row ].onclick) )
            {
                if ( typeof onclick === 'string' )
                {
                    if ( !eval(onclick +'(li, data, evt);') ) {
                        return;
                    }
                } else
                {
                    onclick( li, t.$data[ row ], evt );
                }
            }

            t.fireEvent("click", [{
                                      indices : t.selected,
                                      target  : t,
                                      row     : row,
                                      element : li,
                                      cell    : t.getTdParent(evt.target),
                                      evt     : evt
                                  }, this]);
        },

        onRowDblClick : function(evt)
        {
            var li = this.getLiParent(evt.target);

            if ( !li ) {
                return;
            }

            var ondblclick;
            var target = evt.target,
                row    = li.retrieve('row');

            if ( !target.hasClass( 'td' ) && target.getParent( '.td' ) ) {
                target = target.getParent( '.td' );
            }

            if ( this.getAttribute('editable') &&
                this.getAttribute('editondblclick') && target.hasClass('td') )
            {
                var i, len;
                var childs = li.getChildren();

                for ( i = 0, len = childs.length; i < len; i++ )
                {
                    if ( childs[i] == target ) {
                        break;
                    }
                }

                var obj = this.edit({
                    columnIndex : i,
                    li : li
                });

                if ( obj )
                {
                    if ( typeof obj.input.selectRange === 'function' ) {
                        obj.input.selectRange( 0, obj.input.value.length );
                    }
                }
            }

            if ( this.getAttribute('accordion') &&
                this.getAttribute('openAccordionOnDblClick') )
            {
                this.accordianOpen( li );
            }

            if ( (ondblclick = this.$data[ row ].ondblclick) )
            {
                if (typeof ondblclick === 'string')
                {
                    if ( !eval(ondblclick +'(li, t.$data[ row ]);') ) {
                        return;
                    }
                } else
                {
                    ondblclick(li, this.$data[ row ]);
                }
            }

            var eventparams = {
                row     : row,
                target  : this,
                element : li,
                cell    : this.getTdParent( evt.target )
            };

            this.fireEvent( "dblClick", eventparams );
        },

        onRowContext : function(event)
        {
            var li = this.getLiParent( event.target );

            if ( !li ) {
                return;
            }

            event.stop();

            this.fireEvent('contextMenu', {
                row     : li.retrieve('row'),
                target  : this,
                event   : event,
                element : li,
                cell    : this.getTdParent(event.target)
            });
        },

        toggleIconClick: function(evt)
        {
            evt.stop();

            this.accordianOpen(
                this.getLiParent( evt.target )
            );
        },

        accordianOpen: function(li, event)
        {
            if (typeof li === 'undefined') {
                return;
            }

            var row     = li.retrieve('row'),
                section = this.getSection( row );

            if ( this.getAttribute('accordion') &&
                (typeof section === 'undefined' || !section) )
            {
                var li2 = new Element('li.section', {
                    styles : {
                        width : this.sumWidth + 2 * this.visibleColumns
                    }
                });

                li2.addClass('section-'+ li.retrieve('row'));

                var oSibling = li.nextSibling;

                if ( !oSibling )
                {
                    this.ulBody.appendChild( li2 );
                } else
                {
                    oSibling.parentNode.insertBefore( li2, oSibling );
                }

                section = li2;
            }

            if ( this.getAttribute('autoSectionToggle') )
            {
                if ( this.lastsection )
                {
                    if ( this.lastsection != section )
                    {
                        this.lastsection.setStyle('display', 'none');

                        if ( this.lastsection.getPrevious() )
                        {
                            var ToggleIcon = this.lastsection.getPrevious().getElement('.toggleicon');

                            if ( ToggleIcon ) {
                                ToggleIcon.setStyle('background-position', '0 0');
                            }
                        }
                    }
                }

                if ( !this.getAttribute('accordionRenderer') &&
                    !this.getAttribute('accordionLiveRenderer') )
                {
                    section.setStyle('display', 'block');
                }
            }


            if ( this.getAttribute('accordionRenderer') ||
                this.getAttribute('accordionLiveRenderer') )
            {
                this.toggle( section );
            }

            if ( this.getAttribute('accordionLiveRenderer') )
            {
                this.showLoader();

                this.getAttribute('accordionLiveRenderer')({
                    parent : section,
                    row    : li.retrieve('row'),
                    grid   : this,
                    event  : event
                });

                this.hideLoader();
            }

            if ( this.getAttribute('showtoggleicon') && li.getElement('.toggleicon') )
            {
                li.getElement('.toggleicon')
                    .setStyle(
                    'background-position',
                    section.getStyle('display') == 'block' ? '-16px 0' : '0 0'
                );
            }

            this.lastsection = section;
        },

        onLoadData : function(data)
        {
            this.setData( data );

            // API
            this.fireEvent("loadData", {
                target : this
            });
        },

        unique: function(a, asNumber)
        {
            function om_sort_number(a, b) {
                return a - b;
            }

            var sf =  asNumber ? om_sort_number : function(){};

            a.sort( sf );
            a = a.unique();

            return a;
        },
        // API
        loadData : function(url)
        {
            var options   = this.getAttributes(),
                container = this.container;

            if ( !this.getAttribute('url') && !this.getAttribute('dataProvider') ) {
                return;
            }

            var data = {};

            // pagination
            if ( this.getAttribute('pagination') )
            {
                data = {
                    page    : this.getAttribute('page'),
                    perpage : this.getAttribute('perPage')
                };
            }

            // server sorting
            if ( this.getAttribute('serverSort') )
            {
                data.sorton = this.getAttribute('sortOn');
                data.sortby = this.getAttribute('sortBy');
            }

            if ( this.getAttribute('filterInput') )
            {
                var cfilter = container.getElement('input.cfilter');

                if ( cfilter ) {
                    data.filter = cfilter.value;
                }
            }

            this.showLoader();

            if ( this.getAttribute('dataProvider') )
            {
                this.getAttribute('dataProvider').loadData( data );
                return;
            }

            var request = new Request.JSON({
                url  : (url !== null) ? url : options.url,
                data : data
            });

            request.addEvent("complete", this.onLoadData.bind( this ));
            request.get();
        },

        // API
        refresh : function()
        {
            this.resetButtons();

            if ( this.getAttribute( 'onrefresh' ) ) {
                this.getAttribute( 'onrefresh' )( this );
            }

            this.fireEvent( 'refresh', [ this ] );

            this.loadData();
        },

        resetButtons : function()
        {
            var btns = this.getAttribute('buttons');

            if ( !btns || !btns.length ) {
                return;
            }

            var i, len, Btn;

            for ( i = 0, len = btns.length; i < len; i++ )
            {
                if ( !btns[ btns[i].name ] ) {
                    continue;
                }

                Btn = btns[ btns[i].name ];

                if ( btns[i].disabled )
                {
                    Btn.setDisable();
                    continue;
                }

                Btn.setNormal();
            }
        },

        /**
         * Return the grid buttons
         *
         * @return {Array}
         */
        getButtons : function()
        {
            var buttons = [];

            var btns = this.getAttribute('buttons');

            if ( !btns || !btns.length ) {
                return buttons;
            }

            var i, len;

            for ( i = 0, len = btns.length; i < len; i++ )
            {
                if ( !btns[ btns[i].name ] ) {
                    continue;
                }

                buttons.push( btns[ btns[i].name ] );
            }

            return buttons;
        },

        dataLoader : function()
        {
            this.setAttribute('page', 1);
            this.onLoadData({
                data    : {},
                total   : 0,
                page    : 1,
                perPage : 0
            });

            this.loadData();
        },

        // API
        setData : function(data, cm)
        {
            var options   = this.getAttributes(),
                container = this.container;

            if ( !data ) {
                return;
            }

            this.$data = data.data;

            if ( !this.$columnModel ) {
                this.setAutoColumnModel();
            }

            if ( this.getAttribute('pagination') )
            {
                if ( typeof data.total === 'undefined' ) {
                    data.total = this.$data.length;
                }

                if ( typeof data.page === 'undefined' ) {
                    data.page = 1;
                }

                options.page    = data.page * 1;
                options.total   = data.total;
                options.maxpage = Math.ceil(options.total/options.perPage);

                var cPage = container.getElements('div.pDiv input.cpage');

                cPage.set('value', data.page);
                cPage.setStyle( 'width', 32 );

                var to   = (data.page * options.perPage) > data.total ? data.total : (data.page*options.perPage),
                    page = ((data.page-1)*options.perPage+1);

                var stats = '<span>'+ page +'</span>' +
                    '<span>..</span>' +
                    '<span>'+ to +'</span>' +
                    '<span> / </span>' +
                    '<span>'+ data.total +'</span>';

                container.getElements('div.pDiv .pPageStat').set('html', stats);

                cPage.getNext( 'span.cpageMax' ).set( 'html', options.maxpage );
            }

            if ( cm && this.$columnModel != cm )
            {
                this.$columnModel = cm;
                this.draw();
            }

            this.reset();
            this.hideLoader();
        },

        // API
        getData : function()
        {
            if ( !this.$data.length ) {
                this.$data = [];
            }

            return this.$data;
        },

        // API
        getDataByRow : function(row)
        {
            if ( row < 0 ) {
                return false;
            }

            if ( typeof this.$data[row] !== 'undefined' ) {
                return this.$data[row];
            }
        },

        // API
        getRowElement : function(row)
        {
            if ( typeof this.elements[ row ] !== 'undefined' ) {
                return this.elements[ row ];
            }

            return false;
        },

        // API
        setDataByRow : function(row, data)
        {
            if ( row < 0 ) {
                return false;
            }

            if ( typeof this.$data[row] === 'undefined' ) {
                return;
            }

            this.$data[row] = data;

            var Row = this.container.getElement('[data-row="'+ row +'"]');
            var newRow = this.renderRow( row, this.$data[row] );

            newRow.inject( Row, 'after' );
            Row.destroy();

            this.elements[ row ] = newRow;
        },

        setScroll: function(x, y)
        {
            new Fx.Scroll(
                this.container.getElement('.bDiv')
            ).set( x, y );
        },

        // API
        addRow: function(data, row)
        {
            if ( typeof row === 'undefined' )
            {
                row = 0;

                if ( this.$data.length ) {
                    row = this.$data.length;
                }
            }

            if ( row >= 0 )
            {
                this.$data.splice( row, 0, data );
                this.reset();
            }
        },

        // API
        deleteRow : function(row)
        {
            if ( row >=0 && row < this.$data.length )
            {
                this.$data.splice( row, 1 );
                this.reset();
            }
        },

        /**
         * Delete multible rows
         *
         * @param {Array} rowIds - list of the row ids
         */
        deleteRows : function(rowIds)
        {
            for ( var i = 0, len = rowIds.length; i < len; i++ ) {
                delete this.$data[ rowIds[ i ] ];
            }

            this.$data = this.$data.clean();
            this.reset();
        },

        isHidden : function(i)
        {
            return this.elements[i].hasClass(
                this.getAttribute('filterHideCls')
            );
        },

        hideWhiteOverflow: function()
        {
            var gBlock, pReload;

            if ( (gBlock = this.container.getElement('.gBlock')) ) {
                gBlock.dispose();
            }

            if ( (pReload = this.container.getElement('div.pDiv .pReload')) ) {
                pReload.removeClass('loading');
            }
        },

        showWhiteOverflow: function()
        {
            var pReload, gBlock;
            var container = this.container;

            // white overflow & loader
            if ( (gBlock = container.getElement('.gBlock')) ) {
                gBlock.dispose();
            }

            gBlock = new Element('div.gBlock', {
                styles : {
                    position : 'absolute',
                    top      : 0,
                    left     : 0,
                    zIndex   : 999,
                    opacity  : 0.5,
                    filter   : 'alpha(opacity=50)',
                    background: 'white none repeat scroll 0% 0%;',
                    '-moz-background-clip'         : '-moz-initial',
                    '-moz-background-origin'       : '-moz-initial',
                    '-moz-background-inline-policy': '-moz-initial'
                }
            });

            gBlock.setStyles({
                width  : this.getAttribute('width'),
                height : this.getAttribute('height')-1,
                top    : 0
            });

            container.appendChild( gBlock );

            if ( (pReload = container.getElement('div.pDiv .pReload')) ) {
                pReload.addClass('loading');
            }
        },

        showLoader : function()
        {
            if ( this.loader ) {
                return;
            }

            this.showWhiteOverflow();

            this.loader = new Element('div.elementloader', {
                styles : {
                    top  : this.options.height / 2 - 16,
                    left : this.options.width / 2
                }
            });

            this.loader.inject( this.container );
        },

        hideLoader : function()
        {
            if ( !this.loader ) {
                return;
            }

            this.hideWhiteOverflow();
            this.loader.destroy();
            this.loader = null;
        },

        // API
        selectAll : function()
        {
            var i, len, el;

            for ( i = 0, len = this.elements.length; i < len; i++ )
            {
                el = this.elements[i];

                this.selected.push( el.retrieve('row') );
                el.addClass('selected');
            }

            //this.resetButtons();
            this.fireEvent("click", {
                indices : this.selected,
                target  : this
            });
        },

        selectRow : function(Row, event)
        {
            if ( typeof event !== 'undefined' &&
                (event.shift || event.control || event.meta) &&
                this.options.multipleSelection )
            {
                // nothing
            } else
            {
                this.unselectAll();
            }

            var i, len;
            var children = Row.getParent().getElements('li');

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( children[i] === Row ) {
                    break;
                }
            }

            this.selected.push( i );
            Row.addClass('selected');
        },

        unSelectRow : function(Row)
        {
            Row.removeClass('selected');

            var i, len;

            var sel  = this.selected,
                nsel = [];

            for ( i = 0, len = sel.length; i < len; i++ )
            {
                if ( sel[i].hasClass('selected') ) {
                    nsel.push( sel[i] );
                }
            }

            this.selected = nsel;
        },

        // API
        unselectAll : function()
        {
            for ( var i = 0, len = this.elements.length; i < len; i++ ) {
                this.elements[ i ].removeClass( 'selected' );
            }

            this.selected = [];
            this.resetButtons();
        },

        // API
        getSelectedIndices : function()
        {
            return this.selected;
        },

        getSelectedData : function()
        {
            var i, len;
            var data = [];

            for ( i = 0, len = this.selected.length; i < len; i++ ) {
                data.push( this.getDataByRow( this.selected[i] ) );
            }

            return data;
        },

        // API
        setSelectedIndices : function(arr)
        {
            var i, alen, li;

            this.selected = arr;

            for ( i = 0, alen = arr.length; i < alen; i++ )
            {
                li = this.elements[arr[i]];

                if ( li ) {
                    li.addClass('selected');
                }
            }
        },

        // mislim da je visak
        onMouseOver : function(obj)
        {
            obj.columnModel.onMouseOver(obj.element, obj.data);
        },

        removeHeader: function()
        {
            var obj = this.container.getElement('.hDiv');

            if ( obj ) {
                obj.empty();
            }

            this.$columnModel = null;
        },

        // API
        removeAll : function()
        {
            for ( var i = 0, len = this.elements; i < len; i++ ) {
                this.elements[i].destroy();
            }

            if ( this.ulBody ) {
                this.ulBody.empty();
            }

            this.selected = [];
        },

        // API
        setColumnModel : function(cmu)
        {
            if ( !cmu ) {
                return;
            }

            this.$columnModel = cmu;
            this.draw();
        },
        // API
        setColumnProperty: function(columnName, property, value)
        {
            var i, len;
            var cmu = this.$columnModel;

            if ( !cmu || !columnName || !property ) {
                return;
            }

            columnName = columnName.toLowerCase();

            for ( i = 0, len = cmu.length; i < len; i++ )
            {
                if ( cmu[i].dataIndex.toLowerCase() == columnName )
                {
                    cmu[i][property] = value;
                    return;
                }
            }
        },
        // Automatsko odredivanje column modela ako nije zadan
        setAutoColumnModel: function()
        {
            var rowCount = this.$data.length;

            if ( !rowCount ) {
                return;
            }

            this.$columnModel = [];

            // uzmi schemu od prvog podatka
            for ( var cn in this.$data[0] )
            {
                var dataType = typeof(this.$data[0][cn]) == "number" ? "number" : "string";

                this.$columnModel.push({
                    header    : cn,
                    dataIndex : cn,
                    dataType  : dataType,
                    editable  : true
                });
            }

            this.fireEvent("autoColumModel", {
                target      : this,
                columnModel : this.$columnModel
            });

            this.draw();
        },
        // API
        setSize: function(w, h)
        {
            var container = this.container,
                gBlock    = container.getElement( '.gBlock' ),
                hDiv      = container.getElement( '.hDiv' ),
                tDiv      = container.getElement( '.tDiv' ),
                bodyEl    = container.getElement( '.bDiv' );

            this.setAttribute( 'width', w ? w : this.getAttribute('width') );
            this.setAttribute( 'height', h ? h : this.getAttribute('height') );

            container.setStyle( 'width', this.getAttribute('width') );
            container.setStyle( 'height', this.getAttribute('height') );

            var width = this.getAttribute('width');

            if ( this.getAttribute('buttons') ) {
                tDiv.setStyle('width', width);
            }

            if ( this.getAttribute('showHeader') && hDiv ) {
                hDiv.setStyle('width', width);
            }

            bodyEl.setStyle('width', width);
            container.getElement('.pDiv').setStyle('width', width);

            // Height
            bodyEl.setStyle('height', this.getBodyHeight());


            if ( gBlock )
            {
                gBlock.setStyles({
                    width  : this.getAttribute('width'),
                    height : bodyEl.getSize().y
                });
            }
        },

        onBodyScroll : function()
        {
            var hbox = this.container.getElement( '.hDivBox' ),
                bbox = this.container.getElement( '.bDiv' ),
                xs   = bbox.getScroll().x;

            hbox.setStyle( 'left', -xs );
            this.rePosDrag();
        },

        onBodyClick : function()
        {

        },

        onBodyMouseOver : function()
        {

        },

        onBodyMouseOut : function()
        {

        },

        // Drag columns events
        rePosDrag : function()
        {
            var t = this;
            var options = t.getAttributes();

            if ( !options.resizeColumns ) {
                return;
            }

            var c, oclen, columnModel, dragSt;

            var dragTempWidth = 0,
                container     = t.container,

                cDrags  = container.getElements('.cDrag div'),
                scrollX = container.getElement('div.bDiv').getScroll().x,

                cModel  = this.$columnModel,
                browser = false, //Browser.Engine.trident,
                cWidth  = 0;

            if ( typeof browser == 'undefined' ) {
                browser = false;
            }

            for ( c = 0, oclen = cModel.length; c < oclen; c++ )
            {
                columnModel = cModel[c];
                dragSt      = cDrags[c];

                if ( typeof dragSt === 'undefined' ) {
                    continue;
                }

                dragSt.setStyle('left', dragTempWidth + columnModel.width + cWidth + (browser ? 1 : 1 ) - scrollX);
                cWidth++;

                if ( !columnModel.hidden ) {
                    dragTempWidth += columnModel.width;
                }
            }
        },

        onColumnDragComplete : function(target)
        {
            var t = this;
            var c, len, columnModel;

            t.dragging = false;

            var colindex = target.retrieve('column'),
                cDrag    = t.container.getElement('div.cDrag'),
                scrollX  = t.container.getElement('div.bDiv').getScroll().x,
                dragSt   = cDrag.getElements('div')[colindex],
                browser  = false, //Browser.Engine.trident,
                cModel   = this.$columnModel,

                pos = 0,

                visibleColumns = t.visibleColumns,
                elements       = t.ulBody.getElements('li.tr');

            t.sumWidth = 0;

            if (typeof browser == 'undefined') {
                browser = false;
            }

            for ( c = 0, len = cModel.length; c < len; c++ )
            {
                columnModel = cModel[c];

                if (c == colindex)
                {
                    pos = dragSt.getStyle('left').toInt()+scrollX-this.sumWidth-(browser ? -1 : 1 ); // zato sto je u dragSt.left +2
                } else if (!columnModel.hidden)
                {
                    t.sumWidth += columnModel.width;
                }
            }

            if ( pos < 30 ) {
                pos = 30;
            }

            cModel[ colindex ].width = pos-2; // -2 fix by mor
            t.sumWidth += pos;

            t.ulBody.setStyle( 'width', t.sumWidth + visibleColumns * (browser ? 1 : 1) );
            var hDivBox = document.id(t.options.name+'_hDivBox');

            hDivBox.setStyle( 'width', t.sumWidth + visibleColumns * 2 );

            // header
            var columns   = hDivBox.getElements('div.th');
            var columnObj = columns[colindex];

            columnObj.setStyle('width', pos-(browser ? 6 : 6));

            // sve kolone u body
            elements.each(function(el)
            {
                el.setStyle('width', t.sumWidth + 2 * visibleColumns); // inace se Div-ovi wrapaju

                if ( !el.hasClass('section') )
                {
                    var columns   = el.getElements('div.td'),
                        columnObj = columns[colindex];

                    columnObj.setStyle('width', pos-(browser ? 6 : 6 ));
                }
            });

            t.rePosDrag();
        },

        onColumnDragStart : function()
        {
            this.dragging = true;
        },

        onColumnDragging : function(target)
        {
            target.setStyle('top', -1);
        },

        overDragColumn : function(evt)
        {
            evt.target.addClass('dragging');
        },

        outDragColumn : function(evt)
        {
            evt.target.removeClass('dragging');
        },

        // Header events
        clickHeaderColumn : function(evt)
        {
            if ( this.dragging ) {
                return;
            }

            var Target      = evt.target,
                colindex    = Target.getAttribute('column'),
                columnModel = this.$columnModel[ colindex ] || {},
                colSort     = this.getAttribute( 'sortBy' );

            if ( !colSort ) {
                colSort = 'DESC';
            }

            Target.removeClass( 'ASC' );
            Target.removeClass( 'DESC' );

            colSort = ( colSort == 'ASC' ) ? 'DESC' : 'ASC';

            this.setAttribute( 'sortBy', colSort );
            this.setAttribute( 'sortOn', columnModel.dataIndex );

            Target.addClass( colSort );

            this.sort( colindex, colSort );
        },

        overHeaderColumn : function(evt)
        {
            if ( this.dragging ) {
                return;
            }

            var colindex    = evt.target.getAttribute('column'),
                columnModel = this.$columnModel[colindex] || {};

            if ( typeof columnModel.onmouseover == 'function' ) {
                columnModel.onmouseover( evt );
            }

            evt.target.addClass( columnModel.sort );
        },

        outHeaderColumn : function(evt)
        {
            if ( this.dragging ) {
                return;
            }

            var colindex    = evt.target.getAttribute('column'),
                columnModel = this.$columnModel[colindex] || {};

            if ( typeof columnModel.onmouseout == 'function' ) {
                columnModel.onmouseout( evt );
            }

            evt.target.removeClass(columnModel.sort);
        },

        getBodyHeight: function()
        {
            var height = this.getAttribute('height');

            if ( this.getAttribute('showHeader') ) {
                height = height - 26;
            }

            if ( this.getAttribute('buttons') && this.container.getElement('.tDiv') ) {
                height = height - this.container.getElement('.tDiv').getStyle('height').toInt();
            }

            if ( this.getAttribute('pagination') || this.getAttribute('filterInput') ) {
                height = height - 26;
            }

            return (height - 2);
        },

        setHeight: function(height)
        {
            this.setAttribute('height', height);

            this.container.setStyle('height', height);

            if (this.container.getElement('.bDiv')) {
                this.container
                    .getElement('.bDiv')
                    .setStyle('height', this.getBodyHeight());
            }
        },

        setWidth: function(width)
        {
            this.setAttribute('width', width);

            this.container.setStyle('width', width);

            if (this.container.getElement('.bDiv')) {
                this.container
                    .getElement('.bDiv')
                    .setStyle('width', width);
            }
        },

        renderData : function()
        {
            this.ulBody.empty();
            this.inlineEditSafe = null;

            if (!this.$data) {
                return;
            }

            var rowCount = this.$data.length;

            for ( var r = 0; r < rowCount; r++ )
            {
                var rowdata = this.$data[r],
                    li      = this.renderRow(r, rowdata);

                this.ulBody.appendChild( li );

                if (this.getAttribute('tooltip')) {
                    this.getAttribute('tooltip').attach( li );
                }

                if (this.getAttribute('accordion') &&
                    this.getAttribute('accordionRenderer') &&
                    !this.getAttribute('accordionLiveRenderer'))
                {
                    var li2 = new Element('li.section');
                    li2.addClass('section-'+r);
                    li2.setStyle('width', this.sumWidth + 2*this.visibleColumns);

                    this.ulBody.appendChild(li2);

                    if (this.getAttribute('accordionRenderer'))
                    {
                        this.getAttribute('accordionRenderer')({
                            parent : li2,
                            row    : r,
                            grid   : this
                        });
                    }
                }
            }
        },

        /**
         * Render one row
         *
         * @method controls/grid/Grid#renderRow
         *
         * @param {Number} row - row number
         * @param {Object} data - data for the row
         *
         * @return {HTMLElement} li
         */
        renderRow : function(row, data)
        {
            var c;

            var t = this,
                o = t.getAttributes(),
                r = row,

                columnCount = this.$columnModel.length,
                rowdata     = data;

            var li = new Element('li.tr', {
                styles : {
                    width : t.sumWidth + 2 * t.visibleColumns
                }
            });

            li.store('row', r);
            li.set('data-row', r);

            if ( this.$data[r].cssClass ) {
                li.addClass( this.$data[r].cssClass );
            }

            var columnModel, columnDataIndex, columnData, div, val;
            var firstvisible = -1;

            var func_input_click = function(data)
            {
                var index = data.columnModel.dataIndex;

                data.list.$data[ data.row ][ index ] = data.input.checked ? 1 : 0;
            };

            for ( c = 0; c < columnCount; c++ )
            {
                columnModel     = this.$columnModel[c];
                columnDataIndex = columnModel.dataIndex;
                columnData      = this.$data[ r ][ columnDataIndex ] || false;

                div = new Element('div.td', {
                    styles : {
                        width : (columnModel.width-6).abs()
                    }
                });

                li.appendChild( div );

                firstvisible = (!columnModel.hidden && firstvisible == -1) ? c : firstvisible;

                if ( columnModel.hidden ) {
                    div.setStyle('display', 'none');
                }

                if ( columnModel.onMouseOver )
                {
                    div.onmouseover = t.onMouseOver.bind(t, {
                        element     : div,
                        columnModel : columnModel,
                        data        : this.$data[r]
                    });
                }

                if ( columnModel.title ) {
                    div.title = rowdata[columnModel.title];
                }

                if ( columnModel.dataType == 'button' && columnData )
                {
                    var _btn  = this.$data[ r ][ columnDataIndex ];
                    _btn.data = this.$data[ r ];

                    _btn.data.row  = r;
                    _btn.data.List = t;

                    var Btn  = new QUIButton( _btn );
                    var node = Btn.create();

                    //node.removeClass( 'button' );
                    //node.addClass( 'button' );
                    node.addClass( 'btn-silver' );

                    _btn.data.quiid = Btn.getId();

                    node.removeProperty('tabindex');  // focus eigenschaft nehmen
                    node.inject( div );

                    continue;
                }

                if ( columnModel.dataType == 'QUI' && columnData )
                {
                    columnData.inject( div );
                    continue;
                }

                if ( columnModel.dataType == 'code' && columnData )
                {
                    val = rowdata[ columnDataIndex ];

                    div.set( 'text', val );

                    continue;
                }

                if ( columnModel.dataType == "checkbox" )
                {
                    var input = new Element('input', {type:"checkbox"});

                    input.onclick = func_input_click.bind(this, {
                        columnModel : columnModel,
                        row         : r,
                        list        : t,
                        input       : input
                    });

                    div.appendChild( input );

                    val = rowdata[ columnDataIndex ];

                    if (val == 1 || val=='t') {
                        input.set('checked', true);
                    }

                    continue;
                }

                if ( columnModel.dataType == "image" )
                {
                    if ( ControlUtils.isFontAwesomeClass( rowdata[ columnDataIndex ] ) )
                    {
                        new Element('span', {
                            'class' : rowdata[ columnDataIndex ]
                        }).inject( div );

                        continue;
                    }

                    div.appendChild(
                        new Element('img', {
                            src : rowdata[ columnDataIndex ]
                        })
                    );

                    if ( typeof columnModel.style !== 'undefined' ) {
                        div.getElement('img').setStyles( columnModel.style );
                    }

                    continue;
                }

                if ( columnModel.dataType == "node" )
                {
                    if (typeof rowdata[ columnDataIndex ] != 'undefined' &&
                        rowdata[ columnDataIndex ].nodeName)
                    {
                        div.appendChild(
                            rowdata[ columnDataIndex ]
                        );
                    }

                    continue;
                }

                if ( typeOf(columnModel.labelFunction) === 'function' )
                {
                    div.innerHTML = columnModel.labelFunction(rowdata, r, columnModel);
                    continue;
                }

                if ( columnModel.dataType == "style" )
                {
                    if ( rowdata[ columnDataIndex ] ) {
                        div.setStyles( rowdata[ columnDataIndex ] );
                    }

                    div.innerHTML = '&nbsp;';
                    continue;
                }

                var str = rowdata[ columnDataIndex ] || "";

                if ( str === null ||
                    str == 'null' ||
                    str === 'undefined' ||
                    str === '' ||
                    str == '&nbsp;' )
                {
                    str = '';
                }

                if ( str === '' )
                {
                    div.set( 'html', '&nbsp;' );
                } else
                {
                    div.set( 'html', str );
                }

                var Toggle = false;

                if ( firstvisible == c && o.accordion && o.showtoggleicon )
                {
                    Toggle = new Element('div.toggleicon', {
                        title  : o.toggleiconTitle,
                        events :
                        {
                            click : function(event) {
                                t.toggleIconClick( event );
                            }
                        }
                    }).inject( div, 'top' );
                }
            }

            this.setEventsToRow( li );

            return li;
        },

        setEventsToRow : function(el)
        {
            el.removeEvents([
                'mouseover',
                'mouseout',
                'mousedown',
                'mouseup',
                'click',
                'dblclick'
            ]);

            el.addEvents({
                'mouseover'   : this.onRowMouseOver.bind(this),
                'mouseout'    : this.onRowMouseOut.bind(this),
                'mousedown'   : this.onRowMouseDown.bind(this),
                'mouseup'     : this.onRowMouseUp.bind(this),
                'click'       : this.onRowClick.bind(this),
                'dblclick'    : this.onRowDblClick.bind(this),
                'contextmenu' : this.onRowContext.bind(this)
            });
        },

        // Main draw function
        draw : function()
        {
            var i, len, columnModel;
            var t = this;

            var container   = t.container,
                browser     = false, // Browser.Engine.trident,
                options     = t.getAttributes(),
                width       = options.width - (browser ? 2 : 2 ), //-2 radi bordera
                columnCount = this.$columnModel ? this.$columnModel.length : 0,

                tDiv = null;

            t.removeAll();        // reset variables and only empty ulBody
            container.empty(); // empty all

            // Container
            if (options.width) {
                container.setStyle('width', options.width);
            }

            if (options.styles) {
                container.setStyles( options.styles );
            }

            container.addClass('omnigrid');

            // Toolbar
            if ( this.getAttribute('buttons') )
            {
                tDiv = new Element('div.tDiv', {
                    styles : {
                        width  : width,
                        height : 40
                    }
                });

                container.appendChild( tDiv );

                // button drop down
                this.$Menu = new QUIButton({
                    textimage    : 'fa fa-navicon icon-reorder',
                    text         : 'Menü',
                    dropDownIcon : false
                }).inject( tDiv );

                var bt = this.getAttribute('buttons');

                var node, Btn;

                //var cBt, fBt, spanBt;
                //var func_fbOver = function() {
                //    this.addClass('fbOver');
                //};
                //
                //var func_fbOut = function() {
                //    this.removeClass('fbOver');
                //};

                for ( i = 0, len = bt.length; i < len; i++ )
                {
                    if ( bt[i].type == 'seperator' )
                    {
                        new QUISeperator().inject( tDiv );

                        // new Element('div.btnseparator').inject( tDiv );
                        continue;
                    }

                    bt[i].List = this;
                    bt[i].Grid = this;

                    Btn = new QUIButton( bt[i] );

                    bt[ bt[i].name ] = Btn;

                    node = Btn.create();
                    node.removeProperty( 'tabindex' ); // focus eigenschaft nehmen
                    node.addClass( 'btn-silver' );
                    node.inject( tDiv );

                    var Item = new QUIContextItem({
                        text    : Btn.getAttribute( 'text' ),
                        icon    : Btn.getAttribute( 'image' ) || Btn.getAttribute( 'textimage' ),
                        events  :
                        {
                            onClick : function() {
                                this.click();
                            }.bind( Btn )
                        }
                    });

                    Btn.addEvents({
                        onDisable : function() {
                            this.disable();
                        }.bind( Item ),

                        onNormal : function() {
                            this.enable();
                        }.bind( Item ),

                        onEnable : function() {
                            this.enable();
                        }.bind( Item ),

                        onSetAttribute : function(key, value)
                        {
                            if ( key === 'text' )
                            {
                                this.setAttribute( key, value );
                                return;
                            }

                            if ( key === 'image' || key === 'textimage' ) {
                                this.setAttribute( 'icon', value );
                            }

                        }.bind( Item )
                    });

                    // context menu
                    this.$Menu.appendChild( Item );

                    if ( Btn.isDisabled() ) {
                        Item.disable();
                    }
                }
            }

            // Header
            var hDiv = new Element('div.hDiv', {
                styles : {
                    'width' : width
                }
            });

            container.appendChild(hDiv);

            var hDivBox = new Element('div.hDivBox', {
                id : this.getAttribute('name') +'_hDivBox'
            });

            hDiv.appendChild( hDivBox );

            t.sumWidth       = 0;
            t.visibleColumns = 0; // razlikuje se od columnCount jer podaci za neke kolone su ocitani ali se ne prikazuju, npr. bitno kod li width

            var sortBy = this.getAttribute( 'sortBy' );

            for ( i = 0; i < columnCount; i++ )
            {
                columnModel = this.$columnModel[i] || {};

                var div = new Element('div.th', {
                    'column' : i
                });

                // default postavke columnModela
                if ( typeof columnModel.width == 'undefined' ) {
                    columnModel.width = 100;
                }

                if ( sortBy )
                {
                    columnModel.sort = sortBy;
                } else
                {
                    columnModel.sort = 'ASC';
                }

                // Header events
                if ( this.getAttribute('sortHeader') )
                {
                    div.addEvents({
                        click     : t.clickHeaderColumn.bind( this ),
                        mouseout  : t.outHeaderColumn.bind( this ),
                        mouseover : t.overHeaderColumn.bind( this )
                    });
                }

                div.store('dataType', columnModel.dataType);
                div.setStyle('width', (columnModel.width - (browser ? 6 : 6)).abs());

                hDivBox.appendChild( div );

                if ( typeof columnModel.styles !== 'undefined' ) {
                    div.setStyles( columnModel.styles );
                }

                if ( typeof columnModel.hidden !== 'undefined' && columnModel.hidden )
                {
                    div.setStyle('display', 'none');
                } else
                {
                    t.sumWidth += columnModel.width;
                    t.visibleColumns++;
                }

                var header = columnModel.header;

                if ( header ) {
                    div.innerHTML = header;
                }

                if ( columnModel.image ) {
                    div.style.background = 'url("'+ columnModel.image +'") no-repeat center center';
                }
            }

            hDivBox.setStyle('width', t.sumWidth + t.visibleColumns * 2);

            if ( this.getAttribute('showHeader') === false ) {
                hDiv.setStyle('display', 'none');
            }

            if ( this.getAttribute('height') ) {
                container.setStyle('height', options.height +2);
            }

            /* omni grid version + cWidth = -2; by mor*/
            if ( this.getAttribute('resizeColumns') )
            {
                var cDrag = new Element('div.cDrag');
                var toolbarHeight = 0;

                if ( tDiv ) {
                    toolbarHeight = tDiv.getStyle('height').toInt();
                }

                cDrag.setStyle('top', toolbarHeight);
                container.appendChild(cDrag);

                var dragTempWidth = 0;
                var cWidth        = -2;

                for (i = 0; i < columnCount; i++)
                {
                    columnModel      = this.$columnModel[i] || {};
                    var dragSt       = new Element('div');
                    var headerHeight = options.showHeader ? 24 + 2 : 0; // +2 border

                    if ( typeof columnModel.width == 'undefined' ) {
                        columnModel.width = 100;
                    }

                    dragSt.setStyles({
                        top     : 1,
                        left    : dragTempWidth + cWidth + columnModel.width,
                        height  : headerHeight,
                        display : 'block'
                    });

                    dragSt.store('column', i);
                    cDrag.appendChild(dragSt);

                    // Events
                    dragSt.addEvent('mouseout', t.outDragColumn.bind(this));
                    dragSt.addEvent('mouseover', t.overDragColumn.bind(this));

                    var dragMove = new Drag(dragSt, {snap:0}); // , {container: this.container.getElement('.cDrag') }
                    dragMove.addEvent('drag', t.onColumnDragging.bind(this) );
                    dragMove.addEvent('start', t.onColumnDragStart.bind(this) );
                    dragMove.addEvent('complete', t.onColumnDragComplete.bind(this) );


                    if (columnModel.hidden) {
                        dragSt.setStyle('display', 'none');
                    } else {
                        dragTempWidth += columnModel.width;
                    }

                    cWidth++;
                }
            }

            // Body
            var bDiv = new Element('div.bDiv', {
                id     : this.getAttribute('name') + '_bDiv',
                styles : {
                    'height' : this.getBodyHeight() - 3
                }
            });

            if ( this.getAttribute('width') ) {
                bDiv.setStyle( 'width', width );
            }

            container.appendChild( bDiv );

            //  scroll event
            t.onBodyScrollBind = t.onBodyScroll.bind( t );
            bDiv.addEvent('scroll', t.onBodyScrollBind);

            t.ulBody = new Element('ul', {
                styles : {
                    'width' : t.sumWidth + t.visibleColumns * (browser ? 1 : 1 )
                }
            });

            bDiv.appendChild( t.ulBody );

            if ( ( this.getAttribute('pagination') || this.getAttribute('filterInput') ) &&
                !container.getElement('div.pDiv'))
            {
                var pDiv = new Element('div.pDiv', {
                    styles : {
                        width  : width,
                        height : 30
                    }
                });

                container.appendChild( pDiv );

                var pDiv2 = new Element('div.pDiv2');
                pDiv.appendChild(pDiv2);

                var h = '';


                if ( this.getAttribute('pagination') )
                {
                    h = h +'<div class="pGroup"><select class="rp" name="rp">';

                    var optIdx;
                    var setDefaultPerPage = false;

                    for ( optIdx=0, len=options.perPageOptions.length; optIdx < len; optIdx++ )
                    {
                        if ( options.perPageOptions[optIdx] != options.perPage )
                        {
                            h = h +'<option value="' + options.perPageOptions[optIdx] + '">' + options.perPageOptions[optIdx] +'</option>';
                        } else
                        {
                            setDefaultPerPage = true;
                            h = h +'<option selected="selected" value="' + options.perPageOptions[optIdx] + '">' + options.perPageOptions[optIdx] +'</option>' ;
                        }
                    }

                    h = h +'</select></div>';

                    h = h +'<div class="btnseparator"></div><div class="pGroup"><div class="pFirst pButton"></div><div class="pPrev pButton"></div></div>';
                    h = h +'<div class="btnseparator"></div><div class="pGroup">' +
                    '<span class="pcontrol">' +
                    '<input class="cpage" type="text" value="1" size="4" style="text-align:center" /> ' +
                    '<span>/</span> ' +
                    '<span class="cpageMax"></span>' +
                    '</span>' +
                    '</div>';
                    h = h +'<div class="btnseparator"></div><div class="pGroup"><div class="pNext pButton"></div><div class="pLast pButton"></div></div>';
                    h = h +'<div class="btnseparator"></div><div class="pGroup"><div class="pReload pButton"></div></div>';
                    h = h +'<div class="btnseparator"></div><div class="pGroup"><span class="pPageStat"></span></div>';
                }

                if ( options.multipleSelection )
                {
                    h = h +'<div class="btnseparator"></div>' +
                    '<div class="pGroup">' +
                    '<div class="pSelectAll" title="Alle auswählen"></div>' +
                    '<div class="pUnselectAll" title="Auswahl aufheben"></div>' +
                    '</div>';
                }

                if ( options.filterInput )
                {
                    h = h +'<div class="btnseparator"></div>';
                    h = h +'<div class="pGroup">';
                    h = h +'<span class="pcontrol">';
                    h = h +'<input class="cfilter" ';
                    h = h +'title="Anzeige filtern" ';
                    h = h +'type="text" ';
                    h = h +'value="" ';
                    h = h +'style="" ';
                    h = h +'placeholder="Filter..." ';
                    h = h +'/>';
                    h = h +'<span>';
                    h = h +'</div>';
                }

                if ( options.exportData ) {
                    h = h +'<div class="btnseparator"></div><div class="pGroup"><div class="pExport pButton" title="Drucken / Exportieren"></div></div>';
                }

                pDiv2.innerHTML = h;

                var o = null;

                if ( (o = pDiv2.getElement('.pFirst')) ) {
                    o.addEvent('click', this.firstPage.bind(this));
                }

                if ( (o = pDiv2.getElement('.pPrev')) ) {
                    o.addEvent('click', this.prevPage.bind(this));
                }

                if ( (o = pDiv2.getElement('.pNext')) ) {
                    o.addEvent('click', this.nextPage.bind(this));
                }

                if ( (o = pDiv2.getElement('.pLast')) ) {
                    o.addEvent('click', this.lastPage.bind(this));
                }

                if ( (o = pDiv2.getElement('.pReload')) ) {
                    o.addEvent('click', this.refresh.bind(this));
                }

                if ( (o = pDiv2.getElement('.rp')) )
                {
                    o.addEvent('change', this.perPageChange.bind(this));
                    o.value = options.perPage;
                }

                if ( (o = pDiv2.getElement('input.cpage')) )
                {
                    pDiv2.getElement('input').addEvents({
                        keydown   : this.pageChange.bind(this),
                        mousedown : function() {
                            this.focus();
                        }
                    });
                }

                if ( this.getAttribute('filterInput') )
                {
                    if ( (o = pDiv2.getElement('input.cfilter')) )
                    {
                        pDiv2.getElement('input.cfilter').addEvents({
                            keyup     : this.filerData.bind( this ), // goto 1 & refresh
                            mousedown : function() {
                                this.focus();
                            }
                        });
                    }
                }

                if ( this.getAttribute('multipleSelection') )
                {
                    if ( (o = pDiv2.getElement('.pSelectAll')) ) {
                        o.addEvent('click', this.selectAll.bind(this));
                    }

                    if ( (o = pDiv2.getElement('.pUnselectAll')) ) {
                        o.addEvent('click', this.unselectAll.bind(this));
                    }
                }

                if ( (o = pDiv2.getElement('.pExport')) ) {
                    o.addEvent('click', this.getExportSelect.bind(this));
                }
            }
        },

        firstPage : function()
        {
            this.setAttribute( 'page', 1 );
            this.refresh();
        },

        prevPage : function()
        {
            if ( this.getAttribute('page') > 1 )
            {
                this.setAttribute('page', this.getAttribute('page')-1);
                this.refresh();
            }
        },

        nextPage : function()
        {
            if ( (this.getAttribute('page') + 1) > this.getAttribute('maxpage') ) {
                return;
            }

            this.setAttribute('page', this.getAttribute('page')+1);
            this.refresh();
        },

        lastPage : function()
        {
            this.setAttribute('page', this.getAttribute('maxpage'));
            this.refresh();
        },

        perPageChange : function()
        {
            this.setAttribute('page', 1);
            this.setAttribute('perPage', this.container.getElement('.rp').value);
            this.refresh();
        },

        pageChange : function()
        {
            var np = this.container.getElement('div.pDiv2 input').value;

            if ( np > 0 && np <= this.getAttribute('maxpage') )
            {
                if ( this.$refreshDelayID ) {
                    $clear( this.$refreshDelayID );
                }

                this.setAttribute('page', np);
                this.$refreshDelayID = this.refresh.delay( 1000, this );
            }
        },

        // API
        gotoPage : function(p)
        {
            if ( p > 0 && p <= this.getAttribute('maxpage') )
            {
                this.getAttribute('page', p);
                this.refresh();
            }
        },

        setPerPage : function(p)
        {
            if ( p > 0 )
            {
                this.setAttribute('perPage', p);
                this.refresh();
            }
        },

        // API
        sort : function(index, by)
        {
            if ( index < 0 || index >= this.$columnModel.length ) {
                return;
            }

            if ( this.getAttribute('onStart') ) {
                this.fireEvent('start');
            }

            if ( this.getAttribute('accordionLiveRenderer') ) {
                this.removeSections();
            }

            var header = this.container.getElements( '.th' ),
                el     = header[ index ];

            if ( typeof by !== 'undefined' ) {
                el.addClass( by.toLowerCase() );
            }

            if ( el.hasClass('ASC') )
            {
                el.sortBy = 'ASC';
            } else if ( el.hasClass('DESC') )
            {
                el.sortBy = 'DESC';
            }

            if ( this.getAttribute('serverSort') )
            {
                this.setAttribute('sortOn', this.$columnModel[index].dataIndex);
                this.setAttribute('sortBy', el.sortBy);

                this.refresh();

                return;
            }

            this.elements.sort( el.compare );
            this.elements.inject( this.ulBody );

            this.selected = [];

            for ( var i = 0, len = this.elements.length; i < len; i++ )
            {
                if ( this.elements[ i ].hasClass('selected') ) {
                    this.selected.push( this.elements[ i ].retrieve('row') );
                }
            }

            // Filter
            if ( this.filtered )
            {
                this.filteredAltRow();
                return;
            }

            this.altRow();
        },

        moveup : function()
        {
            if ( typeof this.selected[0] === 'undefined' ) {
                return;
            }

            var i, len;

            var _data = [],
                index = this.selected[0],
                data  = this.$data;

            if ( index === 0 ) {
                return;
            }

            for ( i = 0, len = data.length; i < len; i++ )
            {
                if ( i == index ) {
                    continue;
                }

                if ( i == index-1 ) {
                    _data.push( data[index] );
                }

                _data.push( data[i] );
            }

            this.setData({
                data : _data
            });

            this.setSelectedIndices( [index-1] );
        },

        movedown : function()
        {
            if ( typeof this.selected[0] === 'undefined' ) {
                return;
            }

            var i;

            var _data = [],
                index = this.selected[0],
                data  = this.$data,
                len   = data.length;

            if ( index + 1 >= len ) {
                return;
            }

            for ( i = 0; i < len; i++ )
            {
                if ( i == index ) {
                    continue;
                }

                _data.push( data[i] );

                if ( i == index + 1 ) {
                    _data.push( data[ index ] );
                }
            }

            this.setData({
                data : _data
            });

            this.setSelectedIndices( [index + 1] );
        },

        altRow : function()
        {
            var i, len;
            var elements = this.elements;

            for ( i = 0, len = elements.length; i < len; i++)
            {
                if ( i % 2 )
                {
                    elements[ i ].removeClass( 'erow' );
                    continue;
                }

                elements[ i ].addClass( 'erow' );
            }
        },

        filteredAltRow : function()
        {
            var i, len;
            var elements = this.ulBody.getElements( '.'+ this.getAttribute('filterSelectedCls') );

            for ( i = 0, len = elements.length; i < len; i++)
            {
                if ( i % 2 )
                {
                    elements[ i ].removeClass( 'erow' );
                    continue;
                }

                elements[ i ].addClass( 'erow' );
            }
        },

        filerData : function()
        {
            if ( this.getAttribute( 'filterInput' ) )
            {
                var cfilter = this.container.getElement( 'input.cfilter' );

                if ( cfilter ) {
                    this.filter( cfilter.value );
                }
            }
        },

        // API
        filter : function(key)
        {
            var filterHide    = this.getAttribute( 'filterHide' ),
                filterHideCls = this.getAttribute( 'filterHideCls' );

            if ( !key.length || key === '' )
            {
                this.clearFilter();
                return;
            }

            var i, c, len, clen, data, dat, cml,
                el, columnModel;

            clen = this.$columnModel.length;
            len  = this.$data.length;
            data = this.$data;

            columnModel = this.$columnModel;

            for ( i = 0; i < len; i++ )
            {
                el = this.elements[i];

                if ( filterHide ) {
                    el.removeClass('erow');
                }

                el.addClass( filterHideCls );

                dat = data[i];

                for ( c = 0; c < clen; c++ )
                {
                    cml = columnModel[ c ];

                    if ( cml.type == "checkbox" ) {
                        continue;
                    }

                    if ( typeof dat[ cml.dataIndex ] !== 'undefined' &&
                        typeOf( dat[ cml.dataIndex ] ) !== 'function' &&
                        dat[ cml.dataIndex ] !== null &&
                        dat[ cml.dataIndex ].toString().toLowerCase().indexOf( key ) > -1 )
                    {
                        el.removeClass( filterHideCls );
                        break;
                    }
                }
            }

            this.filtered = true;
        },

        // API
        clearFilter : function()
        {
            var el;

            for ( var i = 0, len = this.elements.length; i < len; i++ )
            {
                el = this.elements[i];
                el.removeClass( this.getAttribute( 'filterSelectedCls' ) );

                if ( this.getAttribute( 'filterHide' ) ) {
                    el.removeClass( this.getAttribute( 'filterHideCls' ) );
                }
            }

            if ( this.getAttribute('filterHide') )
            {
                this.altRow();
                this.filtered = false;
            }
        },

        getExportSelect : function()
        {
            var c, len, columnModel, header, dataIndex;

            var t       = this;
            var options = t.getAttributes();

            var selectWindow  = new Element( 'div.exportSelectDiv' ),
                exportBarDiv  = new Element( 'div.exportSelectBtnDiv' ),
                exportDataDiv = new Element( 'div.exportItemsDiv' ),
                exportTextDiv = new Element( 'div.exportTextsDiv', {
                    html : 'Bitte wählen sie die Felder aus die exportiert werden sollen'
                });

            t.container.appendChild( selectWindow );
            selectWindow.appendChild( exportTextDiv );
            selectWindow.appendChild( exportDataDiv );
            selectWindow.appendChild( exportBarDiv );

            for ( c = 0, len = this.$columnModel.length; c < len; c++ )
            {
                columnModel = this.$columnModel[c];
                header      = columnModel.header;
                dataIndex   = columnModel.dataIndex;

                if ( columnModel.hidden ||
                    columnModel.dataType == 'button'  ||
                    columnModel.dataType == 'checkbox' )
                {
                    continue;
                }

                var div   = new Element('div.exportItemDiv'),

                    span  = new Element('span', {
                        html : header
                    }),

                    input = new Element('input', {
                        type    : 'checkbox',
                        checked : 'checked',
                        value   : dataIndex ,
                        id      : 'export_'+dataIndex ,
                        name    : dataIndex
                    });

                div.appendChild( input );
                div.appendChild( span );

                exportDataDiv.appendChild( div );
            }

            var func_export_btn_click = function(Btn)
            {
                Btn.getAttribute('Grid').exportGrid(
                    Btn.getAttribute('exportType')
                );
            };

            for ( var exportType in options.exportTypes )
            {
                new QUIButton({
                    name   : exportType,
                    text   : options.exportTypes[exportType],
                    events : {
                        click : func_export_btn_click
                    },
                    textimage  : options.exportBinUrl + exportType +'.png',
                    Grid       : this,
                    exportType : exportType
                }).inject( exportBarDiv );
            }

            new QUIButton({
                name   : 'cancel',
                text   : 'Abbrechen',
                events :
                {
                    click : function() {
                        document.getElement('.exportSelectDiv').destroy();
                    }
                },
                textimage : 'icon-remove'
            }).create().inject( exportBarDiv );

            return false;
        },

        setExportData : function()
        {
            var c, i, len, columnModel, header, dataIndex;

            var data     = {
                header : {},
                data   : []
            };

            for ( c = 0, len = this.$columnModel.length; c < len; c++ )
            {
                columnModel = this.$columnModel[c];
                header      = columnModel.header;
                dataIndex   = columnModel.dataIndex;

                if ( columnModel.hidden ||
                    columnModel.dataType == 'button' ||
                    columnModel.dataType == 'checkbox' )
                {
                    continue;
                }

                if ( !document.id('export_'+ dataIndex).checked ) {
                    continue;
                }

                data.header[ dataIndex ] = {
                    header    : header,
                    dataIndex : dataIndex
                };
            }


            var gridData = this.getData();

            if ( gridData )
            {
                for ( i = 0, len = gridData.length; i < len; i++ )
                {
                    var dat      = gridData[i];
                    data.data[i] = {};

                    for ( var h in data.header ) {
                        data.data[i][ data.header[h].dataIndex ] = dat[data.header[h].dataIndex];
                    }
                }
            }

            this.setAttribute('exportData', data);
            document.getElement('.exportSelectDiv').destroy();

            return data;
        },

        exportGrid : function(type)
        {
            var data      = this.getAttribute( 'exportData' ),
                exportUrl = this.getAttribute( 'exportBinUrl' ) + 'export.php';

            if ( this.getAttribute('exportRenderer') )
            {
                this.getAttribute('exportRenderer')({
                    Grid : this,
                    data : data,
                    type : type
                });

                return;
            }

            var tempData = {
                data : data,
                type : type
            };

            if ( type != 'print' )
            {
                new Element('input#exportDataField', {
                    name   : 'data',
                    value  : JSON.encode( tempData ),
                    styles : {
                        display:'none'
                    }
                }).inject( this.container );

                new Element('iframe.exportFrame',{
                    src         : exportUrl,
                    id          : 'gridExportFrame',
                    frameborder : '0',
                    scrolling   : 'auto'
                }).inject( this.container );

                setTimeout('document.id(\'exportDataField\').destroy(); document.id(\'gridExportFrame\').destroy();', 10000);
            }

            // @todo print funktion bauen
        },

        /**
         * Starts the Drag & Drop
         *
         * @method controls/grid/Grid#startDrag
         *
         * @param {DOMEvent} event
         */
        startDrag : function(event)
        {
            if ( !this.getAttribute('dragdrop') ) {
                return;
            }

            if ( this._mousedown ) {
                return;
            }

            if ( this._stopdrag ) {
                return;
            }

            this._mousedown = true;

            var mx = event.page.x,
                my = event.page.y,
                li = this.getLiParent( event.target );

            if ( !li || typeof li.retrieve('row') === 'undefined' ) {
                return;
            }

            var row  = li.retrieve('row'),
                data = this.getDataByRow( row ),
                html = '';

            if ( this.getAttribute('dragDropDataIndex') &&
                data[ this.getAttribute('dragDropDataIndex') ] )
            {
                html = '<span>'+ data[ this.getAttribute('dragDropDataIndex') ] +'</span>';
            }

            this.selectRow( li );

            this.Drag = new Element('div.class', {
                'class'    : this.getAttribute('dragDropClass') || 'omni-drag-drop',
                'data-row' : row.toString(),
                html       :  html,
                styles : {
                    position : 'absolute',
                    top      : (my-15),
                    left     : (mx-40),
                    zIndex   : 1000,
                    'MozOutline' : 'none',
                    outline   : 0
                },
                tabindex : '-1'
            }).inject( document.body );

            this.Drag.addEvent('mouseup', function()
            {
                this.stopDrag();
            }.bind(this));

            // Draging
            new Drag.Move(this.Drag, {

                droppables: this.getAttribute('droppables'),

                onBeforeStart : function(element)
                {
                    this.Drag.focus();
                }.bind(this),

                onStart : function(element, droppable)
                {
                    this.fireEvent( 'dragDropStart', [element, droppable] );
                }.bind(this),

                onComplete : function()
                {
                    this.fireEvent( 'dragDropComplete' );
                }.bind(this),

                onEnter: function(element, droppable)
                {
                    this.fireEvent( 'dragDropEnter', [element, droppable] );
                }.bind(this),

                onLeave: function(element, droppable)
                {
                    this.fireEvent( 'dragDropLeave', [element, droppable] );
                }.bind(this),

                onDrop: function(element, droppable, event)
                {
                    if ( !droppable ) {
                        return;
                    }

                    this.fireEvent('drop', [
                        this.getDataByRow( element.getAttribute('data-row') ),
                        element,
                        droppable,
                        event
                    ]);

                }.bind( this )

            }).start({
                    page: {
                        x : mx,
                        y : my
                    }
                });

            return false;
        },

        stopDrag : function()
        {
            if ( !this.getAttribute('dragdrop') ) {
                return;
            }

            if ( !this._mousedown )
            {
                this._stopdrag = true;
                //this.fireEvent('onclick', [this]);
                return;
            }

            this._mousedown = false;

            if ( this.Drag )
            {
                this.Drag.destroy();
                this.Drag = null;
            }
        }
    });
});
