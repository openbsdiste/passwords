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

    class HandlerYourls extends HandlerAbstract {
        protected $_fields = array ('file');

        public function getOld ($user, $actual, $configurator, $options) {
            $crypted = $actual;
            $configfile = file_get_contents ($options ['file']);
            if ($configfile == false) {
                throw new Exception ('handler.yourls.readerror');
            }
            $quotes = "'" . '"';
			$pattern = "/[$quotes]${user}[$quotes]\s*=>\s*[$quotes](.*)[$quotes]/";
            $matches = array ();
            preg_match_all ($pattern, $configfile, $matches);
            if (count ($matches) == 2) {
                $crypted = $matches [1][0];
            } else {
                throw new Exception ('handler.yourls.usernotfound');
            }
            return $crypted;
        }

        public function handle ($user, $crypted, $new, $configurator, $options) {
            $configfile = file_get_contents ($options ['file']);
            $count = 0;
            $quotes = "'" . '"';
			$pattern = "/[$quotes]${user}[$quotes]\s*=>\s*[$quotes].*[$quotes]/";
			$replace = "'$user' => 'md5:$new'";
			$configdata = preg_replace ($pattern, $replace, $configfile, -1, $count);
            if ($count != 1) {
                throw new Exception ('handler.yourls.updateerror');
            }
            $success = file_put_contents ($options ['file'], $configdata);
        	if ($success === FALSE) {
                throw new Exception ('handler.yourls.writeerror');
        	}
        }
    }
