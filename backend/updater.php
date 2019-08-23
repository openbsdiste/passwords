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

    define ('ROOT', __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
    define ('CONFIGURATION_FILE', ROOT . 'updater.ini');
    define ('REFERENCE_DATABASE', ROOT . 'reference.sqlite');
    define ('LOCKFILE', ROOT . 'lock.lock');
    define ('MAXTIME', 20);
    define ('USERNAME_REGEX', '/^[a-z]+([_-]?[a-z])*\.[a-z]+([_-]?[a-z])*[0-9]?$/');
    define ('PASSWORD_REGEX', '/(?=^.{12,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/');

    require_once (__DIR__ . DIRECTORY_SEPARATOR . 'HandlerAbstract.php');
    require_once (__DIR__ . DIRECTORY_SEPARATOR . 'CrypterAbstract.php');

    class Updater {
        protected $_user, $_actual, $_new, $_newbis;
        protected $_configuration;
        protected $_handlers = array ();
        protected $_crypters = array ();

        protected function _checkActual () {
            if (! preg_match (USERNAME_REGEX, $this->_user)) {
                throw new Exception ('Votre identifiant ne peut &ecirc;tre celui saisi car il est invalide.');
            }
            if (! preg_match (PASSWORD_REGEX, $this->_actual)) {
                throw new Exception ('Le mot de passe actuel ne respecte pas les r&egrave;gles. Il est donc incorrect.');
            }
        }

        protected function _checkNew () {
            if ($this->_actual == $this->_new) {
                throw new Exception ('L\'ancien mot de passe et le nouveau sont identiques. Rien &agrave; changer.');
            }
            if ($this->_new != trim ($this->_new)) {
                throw new Exception ('Le nouveau mot de passe ne peut commencer ni finir par un espace.');
            }
            if ($this->_new != $this->_newbis) {
                throw new Exception ('Le nouveau mot de passe diff&egrave;re du contr&ocirc;le.');
            }
            if (! preg_match (PASSWORD_REGEX, $this->_new)) {
                throw new Exception ('Nouveau mot de passe : 12 carat&egrave;res mini dont 1 maj, 1 min, 1 chiffre ou sp&eacute;cial.');
            }
        }

        protected function _loadConfiguration () {
            if (! is_readable (CONFIGURATION_FILE)) {
                throw new Exception ('Probl&egrave;me technique : Impossible de lire la configuration.');
            }
            $this->_configuration = parse_ini_file (CONFIGURATION_FILE, true, INI_SCANNER_RAW);
            foreach ($this->_configuration as $configurator => $entry) {
                if ( ! is_array ($entry) || ! isset ($entry ['handler']) || ! isset ($entry ['crypter'])) {
                    throw new Exception ('Probl&egrave;me technique : Fichier de configuration invalide. Configurateur : ' . $configurator);
                }

                if (! is_readable (__DIR__ . DIRECTORY_SEPARATOR . "handler" . DIRECTORY_SEPARATOR . "Handler" . ucfirst ($entry ['handler']) . ".php")) {
                    throw new Exception ('Probl&egrave;me technique : Gestionnaire configur&eacute; innexistant : ' . $entry ['handler'] . '.');
                }
                if (! isset ($this->_handlers [$entry ['handler']])) {
                    $className = "Handler" . ucfirst ($entry ['handler']);
                    require_once (__DIR__ . DIRECTORY_SEPARATOR . "handler" . DIRECTORY_SEPARATOR . $className . ".php");
                    $h = new $className ();
                    if (! $h instanceof HandlerAbstract) {
                        throw new Exception ('Probl&egrave;me technique : Gestionnaire ' . $className . ' invalide.');
                    }
                    $this->_handlers [$entry ['handler']] = $h;
                }
                $this->_handlers [$entry ['handler']]->testOptions ($configurator, $entry);

                if (! is_readable (__DIR__ . DIRECTORY_SEPARATOR . "crypter" . DIRECTORY_SEPARATOR . "Crypter" . ucfirst ($entry ['crypter']) . ".php")) {
                    throw new Exception ('Probl&egrave;me technique : Crypteur configur&eacute; innexistant : ' . $entry ['crypter'] . '.');
                }
                if (! isset ($this->_crypters [$entry ['crypter']])) {
                    $className = "Crypter" . ucfirst ($entry ['crypter']);
                    require_once (__DIR__ . DIRECTORY_SEPARATOR . "crypter" . DIRECTORY_SEPARATOR . $className . ".php");
                    $c = new $className ();
                    if (! $c instanceof CrypterAbstract) {
                        throw new Exception ('Probl&egrave;me technique : Crypteur ' . $className . ' invalide.');
                    }
                    $this->_crypters [$entry ['crypter']] = $c;
                }
                $this->_crypters [$entry ['crypter']]->testOptions ($configurator, $entry);
            }
        }

        protected function _checkActualUserAndPassValidity () {
            $resut = false;
            if (! is_readable (REFERENCE_DATABASE)) {
                throw new Exception ('Probl&egrave;me technique : Base de r&eacute;f&eacute;rence introuvable.');
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
                throw new Exception ('Probl&egrave;me technique lors de l\'acces &agrave; la base de r&eacute;f&eacute;rence.');
            }
            if (! $result) {
                throw new Exception ('Identifiant et/ou mot de passe actuel invalide(s).');
            }
        }

        protected function _lockAccess () {
            $mtime = @filemtime (LOCKFILE);
            if (! $mtime || (time () - $mtime > MAXTIME)) {
                if (! touch (LOCKFILE)) {
                    throw new Exception ('Probl&egrave;me technique : Impossible de cr&eacute;er le fichier de v&eacute;rrouillage.');
                }
            } else {
                throw new Exception ('Une autre modification est en cours. Patientez quelques secondes et r&eacute;essayez.');
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
                throw new Exception ('Probl&egrave;me technique lors de la mise &agrave; jour de la base de r&eacute;f&eacute;rence.');
            }
        }

        protected function _unlockAccess () {
            @unlink (LOCKFILE);
        }

        public function __construct ($user, $actual, $new1, $new2) {
            $this->_user = strtolower (trim ($user));
            $this->_actual = trim ($actual);
            $this->_new = $new1;
            $this->_newbis = $new2;
        }

        public function run (&$message) {
            $ok = false;
            try {
                $this->_checkActual ();
                $this->_checkNew ();
                $this->_loadConfiguration ();
                $this->_checkActualUserAndPassValidity ();
                $this->_lockAccess ();
                $this->_updatePasswordEverywhere ();
                $this->_updateActualUserAndPass ();
                $this->_unlockAccess ();
                $ok = true;
                $message = "Mise &agrave; jour du mot de passe termin&eacute;e. Merci.";
            } catch (Exception $e) {
                $message = $e->getMessage ();
            }
            return $ok;
        }
    }
