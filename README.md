# passwords
Password handler over differents subodmains

To be able to use CPanel webmail passwords handler, you must create subfolder backend/handler/cpanel/CPanel
and use composer (for example) to get the gufy/cpanel-whm plugin :

        composer require gufy/cpanel-whm:dev-master

This app relies on jQuery-3.4.1.min and browser-i18n which are included in the repository

Note : you can now select your language (en|fr) by :
- updating html tag in index.html
- updating $lang variable value in cli interface file (backend/cliManageDatabase.php)

Have a nice day !

F.
