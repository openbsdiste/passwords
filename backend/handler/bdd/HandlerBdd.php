<?php
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

    class HandlerBdd extends HandlerAbstract {
        protected $_fields = array ('bddtype', 'bddhost', 'bddname', 'bdduser', 'bddpass', 'bddtable', 'bdduserfield', 'bddpassfield');

        public function getOld ($user, $actual, $configurator, $options) {
            $crypted = $actual;

            try {
                $dsn = $options ['bddtype'] . ":host=" . $options ['bddhost'] . ";dbname=" . $options ['bddname'];
                $pdo = new PDO ($dsn, $options ['bdduser'], $options ['bddpass']);
                $sth = $pdo->prepare (
                    'select `' . $options ['bddpassfield'] . '`' .
                    ' from `' . $options ['bddtable'] . '`' .
                    ' where `' . $options ['bdduserfield'] . '` = :user'
                );
                $sth->execute (array (':user' => $user));
                $res = $sth->fetchall (PDO::FETCH_ASSOC);
                if (count ($res) == 1) {
                    $crypted = $res [0]['password'];
                } else {
                    throw new Exception ('');
                }
            } catch (Exception $e) {
                throw new Exception ('handler.bdd.oldpass');
            }
            return $crypted;
        }

        public function handle ($user, $crypted, $new, $configurator, $options) {
            try {
                $dsn = $options ['bddtype'] . ":host=" . $options ['bddhost'] . ";dbname=" . $options ['bddname'];
                $pdo = new PDO ($dsn, $options ['bdduser'], $options ['bddpass']);
                $sth = $pdo->prepare (
                    'update `' . $options ['bddtable'] . '`' .
                    ' set `' . $options ['bddpassfield'] . '` = :pass' .
                    ' where `' . $options ['bdduserfield'] . '` = :user'
                );
                if (! $sth->execute (array (':user' => $user, ':pass' => $new))) {
                    throw new Exception (implode (' - ', $sth->errorInfo()));
                }
            } catch (Exception $e) {
                throw new Exception ('handler.bdd.newpass');
            }
        }
    }
