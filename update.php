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

    if (
        isset ($_POST ['format'])
        && ($_POST ['format'] == 'json')
        && isset ($_POST ['user'])
        && isset ($_POST ['actual'])
        && isset ($_POST ['new1'])
        && isset ($_POST ['new2'])
        && isset ($_POST ['lang'])
        && isset ($_POST ['dbg'])
    ) {
        ob_start (null);
        $data = array ('ok' => 0, 'message' => '????');
        try {
            require_once (__DIR__ . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'Updater.php');
            $updater = new Updater ($_POST ['lang'], ($_POST ['dbg'] == 1), $_POST ['user'], $_POST ['actual'], $_POST ['new1'], $_POST ['new2']);
            if ($updater->run ($data ['message'])) {
                $data ['ok'] = 1;
            }
        } catch (Exception $e) {
            $data ['message'] = $e->getMessage ();
        }
        ob_end_clean ();
        echo json_encode ($data);
    } else {
        header ($_SERVER ["SERVER_PROTOCOL"]." 404 Not Found");
    }
