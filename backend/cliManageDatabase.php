#!/usr/bin/php
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

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    require_once "Updater.php";

    $sql = "CREATE TABLE 'users' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'username' TEXT NOT NULL, 'password' TEXT NOT NULL)";
    $lang = 'fr';
    $messages = array ();

    function translate ($message, $lang) {
        global $messages;

        if (empty ($messages)) {
            $filename = __DIR__ . DS . 'locale' . DS . $lang;
            if (is_readable ($filename)) {
                $messages = parse_ini_file ($filename, false, INI_SCANNER_RAW);
            }
        }
        if (isset ($messages [$message])) {
            $message = $messages [$message];
        }
        return $message;
    }

    function showLicense ($lang) {
        echo "Passwords  Copyright (C) 2019-2020  François Lecluse" . PHP_EOL;
        echo translate ('cli_license1', $lang) . PHP_EOL;
        echo translate ('cli_license2', $lang) . PHP_EOL;
        echo translate ('cli_license3', $lang) . PHP_EOL;
        echo translate ('cli_license4', $lang) . PHP_EOL;
        echo PHP_EOL;
    }

    function checkArgs ($argc, $argv, &$action, &$user, &$pass, $lang) {
        $user = 'user.name';
        $pass = 'P@ssword' . date ('Y');
        $ok = true;
        if (($argc == 3) && ($argv [1] == "del")) {
            $action = 'del';
            $user = $argv [2];
        } elseif (($argc == 3) && ($argv [1] == "add")) {
            $action = 'add';
            $user = $argv [2];
            $pass = password_hash ($pass, PASSWORD_DEFAULT);
        } elseif (($argc == 4) && ($argv [1] == "add")) {
            $action = 'add';
            $user = $argv [2];
            $pass = password_hash ($argv [3], PASSWORD_DEFAULT);
        } elseif (($argc == 2) && ($argv [1] == 'list')) {
            $action = 'list';
        } elseif (($argc == 3) && ($argv [1] == 'forcepwd')) {
            $action = 'force';
            $user = $argv [2];
        } elseif (($argc == 4) && ($argv [1] == 'forcepwd')) {
            $action = 'force';
            $user = $argv [2];
            $pass = $argv [3];
        } else {
            $ok = translate ('cli_usage', $lang) . " : " . $argv [0] . " " .translate ('cli_args', $lang);
        }
        if ($ok === true) {
            if (! preg_match (USERNAME_REGEX, $user)) {
                $ok = "cli_invalid_login";
            }
            if (($action == 'add') && (! preg_match (PASSWORD_REGEX, $pass))) {
                $ok = "cli_invalid_pass";
            }
        }
        return $ok;
    }


    try {
        showLicense ($lang);
        $create = (! is_readable (REFERENCE_DATABASE));
        $pdo = new PDO ('sqlite:' . REFERENCE_DATABASE);
        if ($create) $pdo->query ($sql);
        $action = $user = $pass = '';
        $ok = checkArgs ($argc, $argv, $action, $user, $pass, $lang);
        if ($ok !== true) {
            throw new Exception ($ok);
        }
        switch ($action) {
            case 'add':
                $sth = $pdo->prepare ('insert into `users` (`username`, `password`) values (:user, :pass)');
                $sth->execute (array ('user' => $user, 'pass' => $pass));
                break;
            case 'del':
                $sth = $pdo->prepare ('delete from `users` where `username` = :user');
                $sth->execute (array ('user' => $user));
                break;
            case 'list':
                $sth = $pdo->prepare ('select `username` from `users` order by `username` ASC');
                $sth->execute (array ());
                $list = $sth->fetchAll (PDO::FETCH_ASSOC);
                foreach ($list as $el) {
                    echo "\t- " . $el ['username'] . PHP_EOL;
                }
                break;
            case 'force':
                $passtmp = $pass . '-tmp';
                if ($user == 'ALL') {
                    $sth = $pdo->prepare ('update `users` set `password`=:pass');
                    $sth->execute (array ('user' => $user, 'pass' => password_hash ($passtmp, PASSWORD_DEFAULT)));
                    $sth = $pdo->prepare ('select `username` from `users` order by `username` ASC');
                    $sth->execute (array ());
                    $list = $sth->fetchAll (PDO::FETCH_ASSOC);
                    foreach ($list as $el) {
                        echo "\t- " . $el ['username'] . PHP_EOL;
                        $updater = new Updater ('fr', false, $el ['username'], $passtmp, $pass, $pass);
                        $updater->run ($message);
                    }
                } else {
                    $sth = $pdo->prepare ('update `users` set `password`=:pass where `username`=:user');
                    $sth->execute (array ('user' => $user, 'pass' => password_hash ($passtmp, PASSWORD_DEFAULT)));
                    echo "\t- " . $user . PHP_EOL;
                    $updater = new Updater ('fr', false, $user, $passtmp, $pass, $pass);
                    $updater->run ($message);
                }
                break;
        }
    } catch (Exception $e) {
        echo translate ($e->getMessage (), $lang) . PHP_EOL;
    }
    echo PHP_EOL;
