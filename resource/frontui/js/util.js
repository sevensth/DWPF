(function($){

    $.fn.serializeObject = function()
    {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    $.IE = {
        _version: $('html').attr('ieversion'),

        isIE : function() {
            return this._version ? true : false;
        },

        is678: function() {
            var v = this.version();
            return 6 <= v && v <= 8;
        },

        version: function() {
            return parseInt(this._version);
        }
    };

     /*\
     |*|
     |*|  :: cookies.js ::
     |*|
     |*|  A complete cookies reader/writer framework with full unicode support.
     |*|
     |*|  https://developer.mozilla.org/en-US/docs/DOM/document.cookie
     |*|
     |*|  This framework is released under the GNU Public License, version 3 or later.
     |*|  http://www.gnu.org/licenses/gpl-3.0-standalone.html
     |*|
     |*|  Syntaxes:
     |*|
     |*|  * docCookies.setItem(name, value[, end[, path[, domain[, secure]]]])
     |*|  * docCookies.getItem(name)
     |*|  * docCookies.removeItem(name[, path], domain)
     |*|  * docCookies.hasItem(name)
     |*|  * docCookies.keys()
     |*|
     \*/

    $.docCookies = {
        getItem: function (sKey) {
            return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
        },
        setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
            if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
            var sExpires = "";
            if (vEnd) {
                switch (vEnd.constructor) {
                    case Number:
                        if (vEnd === Infinity)
                        {
                            sExpires = "; expires=Fri, 31 Dec 9999 23:59:59 GMT";
                        }
                        else
                        {
                            if ($.IE.is678())
                            {
                                var d = new Date();
                                d.setTime(d.getTime() + vEnd*1000); // in milliseconds
                                sExpires = '; expires=' + d.toUTCString();
                            }
                            else
                            {
                                sExpires = "; max-age=" + vEnd;
                            }
                        }
                        break;
                    case String:
                        sExpires = "; expires=" + vEnd;
                        break;
                    case Date:
                        sExpires = "; expires=" + vEnd.toUTCString();
                        break;
                }
            }
            document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
            return true;
        },
        removeItem: function (sKey, sPath, sDomain) {
            if (!sKey || !this.hasItem(sKey)) { return false; }
            document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + ( sDomain ? "; domain=" + sDomain : "") + ( sPath ? "; path=" + sPath : "");
            return true;
        },
        hasItem: function (sKey) {
            return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
        },
        keys: /* optional method: you can safely remove it! */ function () {
            var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
            for (var nIdx = 0; nIdx < aKeys.length; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
            return aKeys;
        }
    };
})(jQuery)