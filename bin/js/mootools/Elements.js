
/**
 * Extend the DOMNode Elements
 */

Element.implement({

    /**
     * if the node is an input field,
     * you can set the focus at the begining of the fiels
     */
    focusToBegin : function()
    {
        if ( typeof this === 'undefined' ) {
            return;
        }

        if ( this.nodeName != 'INPUT' ) {
            return;
        }

        if ( this.createTextRange )
        {
            var part = this.createTextRange();

            this.moveat( "character", 0 );
            this.moveEnd( "character", 0 );
            this.select();

        } else if ( this.setSelectionRange )
        {
            this.setSelectionRange( 0, 0 );
        }

        this.focus();
    },

    /**
     * Return if the Element is realy viewable
     *
     * @return {Bool}
     */
    isViewable : function()
    {
        if ( typeof document.elementFromPoint === 'undefined' )
        {
            // fallback for browsers with no document.elementFromPoint
            // from mootools Element.Shortcuts
            var w = this.offsetWidth,
                h = this.offsetHeight;
            return (w == 0 && h == 0) ? false : (w > 0 && h > 0) ? true : this.style.display != 'none';
        }

        var p = this.getPosition(),
            e = document.elementFromPoint( p.x, p.y );

        return e == this ? true : false;
    }
});

if ( Browser.ie8 )
{
    var floatName = (document.html.style.cssFloat == null) ? 'styleFloat' : 'cssFloat';

    Element.implement({

        setStyle: function(property, value){
            if (property == 'opacity'){
                if (value != null) value = parseFloat(value);
                this.setOpacity(this, value);
                return this;
            }
            property = (property == 'float' ? floatName : property).camelCase();
            if (typeOf(value) != 'string'){
                var map = (Element.Styles[property] || '@').split(' ');
                value = Array.from(value).map(function(val, i){
                    if (!map[i]) return '';
                    return (typeOf(val) == 'number') ? map[i].replace('@', Math.round(val)) : val;
                }).join(' ');
            } else if (value == String(Number(value))){
                value = Math.round(value);
            }

            try
            {
                this.style[property] = value;
            } catch (e) {

            }

            //<ltIE9>
            if ((value == '' || value == null) && this.doesNotRemoveStyles && this.style.removeAttribute){
                this.style.removeAttribute(property);
            }
            //</ltIE9>
            return this;
        }

    });
}
