seatping
--------
A tool to find eachother in lecture halls (or on arbitrary images, for that matter).

Sources
-------
Available via https://github.com/cbdevnet/seatping/
Demo available on http://seatping.kitinfo.de/
Comments/Bugs/Feature requests may be directed to cb@cbcdn.com

Prerequisites
-------------
Working httpd with PHP5 (eg. lighttpd with php5-cgi)
PHP5 sqlite drivers and graphics libraries (eg. php5-sqlite and php5-gd)

Setup
-----
Clone the repo into a folder served by the httpd.
Ensure read/write access by the user running the httpd on the
folder containing the database file as well as the database
file itself.
Configure according to the next section.

Configuration
-------------
After installation, edit settings.php to your liking.
If you do not plan on using the SYSTEM (https://github.com/kitinfo/system/)
for authentication, you may safely disable it (all that will be left is
some lines in the code and a table in the database) and ignore all
related parameters.

To add new halls/images, copy the images into the img/ subfolder
and add entries to the "halls" table.

Usage
-----
Open the page.
Follow on-screen instructions.
