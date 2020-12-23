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

    defined ('DS') || define ('DS', DIRECTORY_SEPARATOR);
    define ('ROOT', __DIR__ . DS . 'data' . DS);
    define ('CONFIGURATION_FILE', ROOT . 'updater.ini');
    define ('REFERENCE_DATABASE', ROOT . 'reference.sqlite');
    define ('LOCKFILE', ROOT . 'lock.lock');
    define ('MAXTIME', 20);
    define ('USERNAME_REGEX', '/^[a-z]+([_-]?[a-z])*\.[a-z]+([_-]?[a-z])*[0-9]?$/');
    define ('PASSWORD_REGEX', '/(?=^.{12,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/');

    require_once (__DIR__ . DS . 'HandlerAbstract.php');
    require_once (__DIR__ . DS . 'CrypterAbstract.php');

    class Updater {
        protected $_user, $_actual, $_new, $_newbis;
        protected $_configuration;
        protected $_handlers = array ();
        protected $_crypters = array ();
        protected $_debug;
        protected $_lang;

        protected function _translate ($message) {
            $table = explode ('.', $message);
            $message = array_pop ($table);
            $dirname = __DIR__ . DS . implode (DS, $table);
            $filename = $dirname . DS . 'locale' . DS . $this->_lang;
            if (is_readable ($filename)) {
                $messages = parse_ini_file ($filename, false, INI_SCANNER_RAW);
                if (isset ($messages [$message])) {
                    $message = $messages [$message];
                }
            }
            return $message;
        }

        protected function _checkActual () {
            if (! preg_match (USERNAME_REGEX, $this->_user)) {
                throw new Exception ('invalid_identifier');
            }
            if (! preg_match (PASSWORD_REGEX, $this->_actual)) {
                throw new Exception ('invalid_password');
            }
        }

        protected function _checkNew () {
            if ($this->_actual == $this->_new) {
                throw new Exception ('identical_passwords');
            }
            if ($this->_new != trim ($this->_new)) {
                throw new Exception ('newpass_space');
            }
            if ($this->_new != $this->_newbis) {
                throw new Exception ('newpass_not_equals');
            }
            if (! preg_match (PASSWORD_REGEX, $this->_new)) {
                throw new Exception ('newpass_invalid');
            }
        }

        protected function _loadConfiguration () {
            if (! is_readable (CONFIGURATION_FILE)) {
                throw new Exception ('config_not_readable');
            }
            $this->_configuration = parse_ini_file (CONFIGURATION_FILE, true, INI_SCANNER_RAW);
            foreach ($this->_configuration as $configurator => $entry) {
                if ( ! is_array ($entry) || ! isset ($entry ['handler']) || ! isset ($entry ['crypter'])) {
                    throw new Exception ('invalid_configurator');
                }

                $className = "Handler" . ucfirst ($entry ['handler']);
                if (! is_readable (__DIR__ . DS . "handler" . DS . $entry ['handler'] . DS . $className . ".php")) {
                    throw new Exception ('invalid_handler' . $entry ['handler'] . '.');
                }
                if (! isset ($this->_handlers [$entry ['handler']])) {
                    require_once (__DIR__ . DS . "handler" . DS . $entry ['handler'] . DS . $className . ".php");
                    $h = new $className ();
                    if (! $h instanceof HandlerAbstract) {
                        throw new Exception ('handler_error');
                    }
                    $this->_handlers [$entry ['handler']] = $h;
                }
                $this->_handlers [$entry ['handler']]->testOptions ($configurator, $entry);

                $className = "Crypter" . ucfirst ($entry ['crypter']);
                if (! is_readable (__DIR__ . DS . "crypter" . DS . $entry ['crypter'] . DS . $className . ".php")) {
                    throw new Exception ('innexistant_crypter');
                }
                if (! isset ($this->_crypters [$entry ['crypter']])) {
                    require_once (__DIR__ . DS . "crypter" . DS . $entry ['crypter'] . DS . $className . ".php");
                    $c = new $className ();
                    if (! $c instanceof CrypterAbstract) {
                        throw new Exception ('invalid_crypter');
                    }
                    $this->_crypters [$entry ['crypter']] = $c;
                }
                $this->_crypters [$entry ['crypter']]->testOptions ($configurator, $entry);
            }
        }

        protected function _checkActualUserAndPassValidity () {
            $result = false;
            if (! is_readable (REFERENCE_DATABASE)) {
                throw new Exception ('ref_not_found');
            }
            try {
                $pdo = new PDO ('sqlite:' . REFERENCE_DATABASE);
                $sth = $pdo->prepare ('select `password` from `users` where `username`=:user');
                $sth->execute (array (':user' => $this->_user));
                $res = $sth->fetchall (PDO::FETCH_ASSOC);
                $sth->closeCursor ();
                if (count ($res) == 1) {
                    if (password_verify ($this->_actual, $res [0]['password'])) {
                        $result = true;
                    }
                }
            } catch (Exception $e) {
                throw new Exception ('ref_invalid');
            }
            if (! $result) {
                throw new Exception ('invalid_login_pass');
            }
        }

        protected function _lockAccess () {
            $mtime = @filemtime (LOCKFILE);
            if (! $mtime || (time () - $mtime > MAXTIME)) {
                if (! touch (LOCKFILE)) {
                    throw new Exception ('create_lock');
                }
            } else {
                throw new Exception ('lock_exists');
            }
        }

        protected function _updatePasswordEverywhere () {
            foreach ($this->_configuration as $configurator => $entry) {
                $actual = $this->_actual;
                $new = $this->_new;
                $crypted = $this->_handlers [$entry ['handler']]->getOld ($this->_user, $actual, $configurator, $entry);
                $this->_crypters [$entry ['crypter']]->crypt ($this->_user, $actual, $crypted, $new, $configurator, $entry);
                $this->_handlers [$entry ['handler']]->handle ($this->_user, $crypted, $new, $configurator, $entry);
            }
        }

        protected function _updateActualUserAndPass () {
            try {
                $pdo = new PDO ('sqlite:' . REFERENCE_DATABASE);
                $sth = $pdo->prepare ('update `users` set `password`=:pass where `username`=:user');
                $pass = password_hash ($this->_new, PASSWORD_DEFAULT);
                $sth->execute (array (':user' => $this->_user, ':pass' => $pass));
                if (! $sth->rowCount ()) {
                    throw new Exception ('pwet');
                }
            } catch (Exception $e) {
                throw new Exception ('update_ref');
            }
        }

        protected function _unlockAccess () {
            @unlink (LOCKFILE);
        }

        public function __construct ($lang, $debug, $user, $actual, $new1, $new2) {
            $this->_debug = $debug;
            $this->_lang = $lang;
            $this->_user = strtolower (trim ($user));
            $this->_actual = trim ($actual);
            $this->_new = $new1;
            $this->_newbis = $new2;
        }

        public function run (&$message) {
            $ok = false;
            $locked = false;
            try {
                $this->_checkActual ();
                $this->_checkNew ();
                $this->_loadConfiguration ();
                $this->_checkActualUserAndPassValidity ();
                $this->_lockAccess ();
                $locked = true;
                $this->_updatePasswordEverywhere ();
                $this->_updateActualUserAndPass ();
                $ok = true;
                $message = "end_ok";
            } catch (Exception $e) {
                $message = $e->getMessage ();
            }
            if ($locked) {
                $this->_unlockAccess ();
            }
            $message = $this->_translate ($message);
            return $ok;
        }
    }
