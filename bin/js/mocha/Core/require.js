
Object.append(Asset,
{
    getCSSRule: function(selector)
    {
        console.info('@todo mochaui require.js Asset.getCSSRule');

        for (var ii = 0, len = document.styleSheets.length; ii < len; ii++) {
            try {
                var mySheet = document.styleSheets[ii];
                var myRules = mySheet.cssRules ? mySheet.cssRules : mySheet.rules;
                selector=selector.toLowerCase();
                for (var i = 0; i < myRules.length; i++){
                    if (myRules[i].selectorText.toLowerCase() == selector){
                        return myRules[i];
                    }
                }
            } catch (e) {}
        }
        return false;
    }
});