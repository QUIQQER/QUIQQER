/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
 */

//slick search defines
Slick.definePseudo('display', function(value) {
    return Element.getStyle(this, 'display') == value;
});
