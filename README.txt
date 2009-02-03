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

For each Drupal site that you want to monitor, you should add one entry in Nagios.

An entry for a site will look like so:

    define service{
      use                 generic-service  ; Inherit default values from a template
      host_name           drupal-host-name
      service_description Drupal
      check_command       check_http!-u /nagios -A "Drupal" -t 2 -M 5 -s "nagios=status:ok"
    }

Here is an explanation of some of the options:

-A "Drupal"
  This is the user agent that Nagios will use when accessing your site. You should change this
  to the unique string that is unique for your installation, and set Drupal's settings accordingly.

-t 2
  This means that if the Drupal site does not respond in 2 seconds, an error will be reported
  by Nagios. Increase this if you site is really slow.

-M 5
  This means that the response from Drupal should be fresh and no more than 5 seconds ago. This
  will help determine if the site has stopped generating the status page.

-u /nagios
  For a normal site, leave this unchanged. If you installed Drupal in a subdirectory, then change
  /nagios to /sub_directory/nagios

-s "nagios=status:ok"
  This is the reponse to look for on success. Do not change this part.

To Do / Wishlist
----------------

The following features are nice to have. If you can provide working and tested patches, please
submit them in the issue queue on drupal.org.

* The nagios_get_data() function can provide a hook so modules can provide their own data into Nagios.
* Would be nice if modules can override the 'status' element in the array as well.
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

