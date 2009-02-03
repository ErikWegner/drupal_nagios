$Id$

Copyright 2009 Khalid Baheyeldin http://2bits.com

Description
-----------
The Nagios monitoring module intergrates your Drupal site with with the Nagios.

Nagios is a network and host monitoring application. For more information about
Nagios, see http://www.nagios.org

The module reports to Nagios that the site is up and running normally, including:
- PHP is parsing scripts and modules correctly
- The database is accessible from Drupal
- Whether there are configuration issues with the site, such as:
  * pending Drupal version update
  * pending Drupal module updates
  * unwritable 'files' directory
  * Pending updates to the database schema

If you already use Nagios in your organization to monitor your infrastructure, then
this module will be useful for you. If you only run one or two Drupal sites, Nagios
may be overkill for this task.

Security Note
-------------

This module exposes the following information from your web site:
- The number of published nodes.
- The number of active users.
- Whether an action requiring the administrator's attention (e.g pending module updates,
  unreadable 'files' directory, ...etc.)

To mitigate the security risks involve, make sure you use a unique user agent string.
However, this is not a fool proof solution. If you are concerned about this information
being publicly accessible, then don't use this module.

Installation
------------
To install this module, do the following:

1. Extract the tarball that you downloaded from Drupal.org

2. Upload the nagios directory that you extracted to your sites/all/modules
   directory.

Configuration for Drupal
------------------------

To enable this module do the following:

1. Go to Admin -> Build -> Modules
   Enable the module.

2. Go to Admin -> Settings -> Nagios monitoring.
   Enter a unique user agent string.

   Don't forget to configure Nagios accordingly. See below.

Configuration for Nagios
------------------------

The exact way to configure Nagios depends on several factors, e.g. how many Drupal
sites you want to monitor, the way Nagios is setup, ...etc.

The following way is just one of many ways to configure Nagios for Drupal. There are
certainly other ways to do it, but it all centers on using the check_drupal command
being run for each site.

1. Copy the check_drupal script in the nagios-plugin directory to your Nagios plugins
   directory (e.g. /usr/lib/nagions/plugins).

2. Change the commands.cfg file for Nagios to include the following:

   define command{
     command_name  check_drupal
     command_line  /usr/lib/nagios/plugins/check_drupal -H $HOSTNAME$ -u $ARG1$ -T $ARG2$
   }

3. Create a hostgroup for the hosts that run Drupal and need to be monitored.
   This is normally in a hostgroups.cfg file.
   
   define hostgroup {
     hostgroup_name  drupal-servers
     alias           Drupal servers
     members         yoursite.example.com, mysite.example.com 
   }

4. Defined a service that will run for this host group

   define service{
     hostgroup_name         drupal-servers
     service_description    DRUPAL
     check_command          check_drupal!-u "unique_id" -T 2 
     use                    generic-service
     notification_interval  0 ; set > 0 if you want to be renotified
   }

Here is an explanation of some of the options:

-u "unique_id"
  This parameter is required.
  It is a unique identifier that is send as the user agent from the Nagios check_drupal script,
  and has to match what the Drupal Nagios module has configured.  Both sides have to match,
  otherwise, you will get "unauthorized" errors.

-T 2
  This parameter is optional.
  This means that if the Drupal site does not respond in 2 seconds, an error will be reported
  by Nagios. Increase this value if you site is really slow.

-p nagios
  This parameter is optional.
  For a normal site where Drupal is installed in the web server's DocumentRoot, leave this unchanged.
  If you installed Drupal in a subdirectory, then change nagios to sub_directory/nagios

To Do / Wishlist
----------------

The following features are nice to have. If you can provide working and tested patches, please
submit them in the issue queue on drupal.org.

* The nagios_get_data() function can provide a hook so modules can provide their own data into Nagios.
* Would be nice if modules can override the 'DRUPAL' element in the array as well.
* Instead of using Nagios built in check_http, it would be more beneficial if we have our custom Drupal
  plugin for Nagios that returns OK, WARNING or CRITICAL, and not just check for a string, or absence thereof.
* Implement a full SNMP MIB for Drupal
* Integrate average page execution time and average page memory from devel/performance into Nagios

Bugs/Features/Patches:
----------------------
If you want to report bugs, feature requests, or submit a patch, please do so
at the project page on the Drupal web site.

Author
------
Khalid Baheyeldin (http://baheyeldin.com/khalid and http://2bits.com)

If you use this module, find it useful, and want to send the author
a thank you note, then use the Feedback/Contact page at the URL above.

The author can also be contacted for paid customizations of this
and other modules.

