<?php
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

    class CrypterCrypt extends CrypterAbstract {
        public function crypt ($user, &$actual, $crypted, &$new, $configurator, $options) {
            $actual = crypt ($actual);
            $new = crypt ($new);
        }
    }
