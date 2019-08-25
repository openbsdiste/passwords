/*
    Passwords handler over subdomains
    Copyright (C) 2019  François Lecluse

    This file is part of Passwords

    Passwords is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Passwords is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
var Base = (function () {
    function Base (isdebug) {
        this.pending = false;
        this.debug = false;
        if (isdebug !== undefined) {
            this.debug = (isdebug) ? true : false;
        }
    };

    Base.prototype.DisplayMessage = function (messagetodisplay, displaytime) {
        var that = this;
        var mtd;

        if (displaytime === undefined) {
            displaytime = 10000;
        }
        if (that.pending !== false) {
            clearTimeout (that.pending);
            that.pending = false;
            jQuery ('#messages').empty ();
        }
        mtd = i18n.__ (messagetodisplay);
        if (mtd === undefined) {
            mtd = messagetodisplay;
        }
        jQuery ('#messages').append (mtd);
        that.pending = setTimeout (function () { jQuery ('#messages').empty (); }, displaytime);
        return that;
    };

    Base.prototype.ChangePass = function () {
        var that = this;

        that.DisplayMessage ('updatingpass', 60000);
        jQuery.ajax ({
            url: 'update.php',
            data: {
                format: 'json',
                user:   jQuery ('#username').val (),
                actual: jQuery ('#actualpass').val (),
                new1:   jQuery ('#newpass1').val (),
                new2:   jQuery ('#newpass2').val (),
                lang:   jQuery ("html").attr ('lang'),
                dbg:    (that.debug) ? 1 : 0
            },
            type: 'POST',
            cache: false,
            error: function () {
                that.DisplayMessage ("unknownerror");
            },
            success: function (data) {
                try {
                    var msgData = jQuery.parseJSON (data);
                    if (msgData.ok == 1) {
                        jQuery ('#actualpass').val ('');
                        jQuery ('#newpass1').val ('');
                        jQuery ('#newpass2').val ('');
                    }
                    that.DisplayMessage (msgData.message);
                } catch (e) {
                    that.DisplayMessage ('updateerror');
                    if (that.debug) { jQuery ('body').empty ().append (data); }
                }
            }
        });
    };

    Base.prototype.Initialize = function () {
        var that = this;

        jQuery ('#changepass').on ('click', function () { that.ChangePass (); });
        return that;
	};

    return Base;
}) ();

jQuery (document).ready (function () {
    var tags = ['title', 'lusername', 'lactualpass', 'lnewpass1', 'lnewpass2', 'changepass'];

    jQuery (document).attr ("title", i18n.__('headtitle'));
    jQuery ('meta[name=description]').attr ('content', i18n.__('description'));
    jQuery ('#title').append (i18n.__("title"));
    jQuery ('#copyright a').last ().attr('href', i18n.__('licenseurl'));
    for (var i in tags) {
        jQuery ('#' + tags [i]).empty ().append (i18n.__(tags [i]));
    }
    new Base (true).Initialize ().DisplayMessage ('welcome', 4000);
});
