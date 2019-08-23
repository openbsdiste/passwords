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

    abstract class CrypterAbstract {
        protected $_fields = array ();

        public function testOptions ($configurator, $options) {
            foreach ($this->_fields as $field) {
                if (! isset ($options [$field])) {
                    throw new Exception ($configurator . ' : configuration de ' . $field . 'n&eacute;c&eacute;ssaire.');
                }
            }
        }

        abstract public function crypt ($user, &$actual, $crypted, &$new, $configurator, $options);
    }