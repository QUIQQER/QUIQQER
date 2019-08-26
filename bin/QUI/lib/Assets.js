/*
---
MooTools: the javascript framework

web build:
 - http://mootools.net/more/417ab68a672f38ea1c04efab47af2ff7

packager build:
 - packager build More/Assets

copyrights:
  - [MooTools](http://mootools.net)

licenses:
  - [MIT License](http://mootools.net/license.txt)
...
*/

MooTools.More = {version: "1.5.1",build: "2dd695ba957196ae4b0275a690765d6636a61ccd"};var Asset = {javascript: function (d,b) {
    if (!b) {
        b = {};
    }var a = new Element("script",{src: d,type: "text/javascript"}),e = b.document || document,c = b.onload || b.onLoad;
    delete b.onload;delete b.onLoad;delete b.document;if (c) {
        if (!a.addEventListener) {
            a.addEvent("readystatechange", function () {
                if (["loaded","complete"].contains(this.readyState)) {
                    c.call(this);
                }
            });
        }else {
            a.addEvent("load", c);
        }
    }return a.set(b).inject(e.head);
},css: function (a,d) {
    if (!d) {
        d = {};
    }var i = d.onload || d.onLoad,h = d.document || document,f = d.timeout || 3000;
    ["onload","onLoad","document"].each(function (j) {
        delete d[j];
    });var g = new Element("link",{type: "text/css",rel: "stylesheet",media: "screen",href: a}).setProperties(d).inject(h.head);
    if (i) {
        var c = false,e = 0;var b = function () {
            var m = document.styleSheets;for (var l = 0; l < m.length; l++) {
                var k = m[l];var j = k.ownerNode ? k.ownerNode : k.owningElement;
                if (j && j == g) {
                    c = true;return i.call(g);
                }
            }e++;if (!c && e < f / 50) {
                return setTimeout(b, 50);
            }
        };setTimeout(b, 0);
    }return g;
},image: function (c,b) {
    if (!b) {
        b = {};
    }var d = new Image(),a = document.id(d) || new Element("img");
    ["load","abort","error"].each(function (e) {
        var g = "on" + e,f = "on" + e.capitalize(),h = b[g] || b[f] || function () {};delete b[f];delete b[g];d[g] = function () {
            if (!d) {
                return;
            }if (!a.parentNode) {
                a.width = d.width;a.height = d.height;
            }d = d.onload = d.onabort = d.onerror = null;h.delay(1, a, a);a.fireEvent(e, a, 1);
        };
    });d.src = a.src = c;if (d && d.complete) {
        d.onload.delay(1);
    }return a.set(b);
},images: function (c,b) {
    c = Array.from(c);var d = function () {},a = 0;b = Object.merge({onComplete: d,onProgress: d,onError: d,properties: {}}, b);return new Elements(c.map(function (f,e) {
        return Asset.image(f, Object.append(b.properties, {onload: function () {
            a++;
            b.onProgress.call(this, a, e, f);if (a == c.length) {
                b.onComplete();
            }
        },onerror: function () {
            a++;b.onError.call(this, a, e, f);if (a == c.length) {
                b.onComplete();
            }
        }}));
    }));
}};
