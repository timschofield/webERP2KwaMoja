# Convert webERP to KwaMoja

************************************************************************

This script is designed to convert a webERP database into a KwaMoja
database.
It performs other housework on your new KwaMoja instance to enable it to
run with your webERP data.
To run this script you will need to have both the webERP and the KwaMoja
code installed on the same machine that you are running this script.
This script assumes that the database user and password in the config.php
file in your webERP instance has permissions to CREATE and DROP databases.
If this is not so, you should temporarily make it so. Please see the mysql
documentation if you are unsure how to do this.
This script takes 3 parameters. The first is the name of the webERP
to be converted. This database will not be altered, and the new database
will have this name but will have a suffix of _1 attached to it. eg If
your webERP database is called weberp then the converted database will be
called weberp_1.
WARNING - If a database already exists with this name then it will be deleted.
The second parameter is the path to the webERP code. This can be an
absolute path, or a path relative to the directory that the script is
being run from.
The third parameter is the path to the KwaMoja code. This can be an
absolute path, or a path relative to the directory that the script is
being run from.
So running the script in the web root, with a webERP database named weberp
the command would look something like:

ConvertwebERP-4.15.1ToKwaMoja.php weberp webERP/ KwaMoja/

WARNING - Please note. Every effort has been made to ensure this script runs safely.
However the KwaMoja team accepts no responsibility for any data loss that might occur.
It is your responsibility to ensure you have backed up all your data correctly.

************************************************************************
