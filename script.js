/*
    Passwords handler over subdomains
    Copyright (C) 2019  François Lecluse

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
var Base = (function () {
    function Base () {
        this.pending = false;
        this.debug = true;
    };

    Base.prototype.DisplayMessage = function (messagetodisplay, displaytime) {
        var that = this;
        if (displaytime === undefined) {
            displaytime = 5000;
        }
        if (that.pending !== false) {
            clearTimeout (that.pending);
            that.pending = false;
            jQuery ('#messages').empty ();
        }
        jQuery ('#messages').append (messagetodisplay);
        that.pending = setTimeout (function () { jQuery ('#messages').empty (); }, displaytime);
    };

    Base.prototype.ChangePass = function () {
        var that = this;
        that.DisplayMessage ("Mise &agrave; jour de votre mot de passe...", 60000);
        jQuery.ajax ({
            url: 'update.php',
            data: {
                format: 'json',
                user: jQuery ('#username').val (),
                actual: jQuery ('#actualpass').val (),
                new1: jQuery ('#newpass1').val (),
                new2: jQuery ('#newpass2').val ()
            },
            type: 'POST',
            cache: false,
            error: function () {
                that.DisplayMessage ("Erreur inconnue d'appel...", 10000);
            },
            success: function (data) {
                try {
                    var msgData = jQuery.parseJSON (data);
                    if (msgData.ok == 1) {
                        jQuery ('#actualpass').val ('');
                        jQuery ('#newpass1').val ('');
                        jQuery ('#newpass2').val ('');
                    }
                    that.DisplayMessage (msgData.message, 6000);
                } catch (e) {
                    that.DisplayMessage ('Erreur inconnue lors de la mise &agrave; jour...', 10000);
                    if (that.debug) { jQuery ('body').empty ().append (data); }
                }
            }
        });
    };

    Base.prototype.Initialize = function () {
        var that = this;

        jQuery ('#changepass').on ('click', function () { that.ChangePass (); });
	};

    return Base;
}) ();

jQuery (document).ready (function () {
    var base = new Base ();
    base.Initialize ();
    base.DisplayMessage ("Bienvenue !", 2000);
});
