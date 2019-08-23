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

    require_once implode (DIRECTORY_SEPARATOR, array (__DIR__, "CPanel", "vendor", "autoload.php"));

    class HandlerCpanel extends HandlerAbstract {
        protected $_fields = array ('authtype', 'adminurl', 'adminuser', 'adminpass', 'domainname');

        public function getOld ($user, $actual, $configurator, $options) {
            return $actual;
        }

        public function handle ($user, $crypted, $new, $configurator, $options) {
            $cpanel = new \Gufy\CpanelPhp\Cpanel ([
                'username' => $options ['adminuser'],
                'host'    => $options ['adminurl'],
                'auth_type' => $options ['authtype'],
                'password' => $options ['adminpass']
            ]);

            $new_email = $cpanel->execute_action (
                3, 'Email', 'passwd_pop', 'cpanel_username',
                array (
                    'email' => $user,
                    'password' => $new,
                    'domain' => $options ['domainname']
                )
            );
        }
    }
