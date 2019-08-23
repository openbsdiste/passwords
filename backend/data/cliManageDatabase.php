#!/usr/bin/php
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

    define ('REFERENCE_DATABASE', dirname (__FILE__) . DIRECTORY_SEPARATOR . 'reference.sqlite');
    define ('USERNAME_REGEX', '/^[a-z]+([_-]?[a-z])*\.[a-z]+([_-]?[a-z])*[0-9]?$/');
    define ('PASSWORD_REGEX', '/(?=^.{12,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/');
    $sql = "CREATE TABLE 'users' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'username' TEXT NOT NULL, 'password' TEXT NOT NULL)";

    if (($argc == 3) && ($argv [1] == "del")) {
        $action = 'del';
        $user = $argv [2];
    } elseif (($argc == 4) && ($argv [1] == "add")) {
        $action = 'add';
        $user = $argv [2];
        $pass = password_hash ($argv [3], PASSWORD_DEFAULT);
    } else {
        echo "Usage : " . $argv [0] . " [add|del] username (password)" . PHP_EOL;
        die ();
    }
    if (! preg_match (USERNAME_REGEX, $user)) {
        echo "Identifiant invalide." . PHP_EOL;
    }
    if (($action == 'add') && (! preg_match (PASSWORD_REGEX, $pass))) {
        echo "Mot de passe non conforme." . PHP_EOL;
    }

    try {
        $create = (! is_readable (REFERENCE_DATABASE));
        $pdo = new PDO ('sqlite:' . REFERENCE_DATABASE);
        if ($create) $pdo->query ($sql);

        if ($action == 'add') {
            $sth = $pdo->prepare ('insert into `users` (`username`, `password`) values (:user, :pass)');
            $sth->execute (array ('user' => $user, 'pass' => $pass));
        } elseif ($action == 'del') {
            $sth = $pdo->prepare ('delete from `users` where `username` = :user');
            $sth->execute (array ('user' => $user));
        }
    } catch (Exception $e) {
        echo $e->getMessage () . PHP_EOL;
    }
