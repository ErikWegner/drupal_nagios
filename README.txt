
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
  * Cron not running for a specified period

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

To mitigate the security risks involve, make sure you use a unique ID. However, this is
not a fool proof solution. If you are concerned about this information being publicly
accessible, then don't use this module.

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
   Enter a unique ID. This must match what you configure Nagios for.
   See below for more details.

   Don't forget to configure Nagios accordingly. See below.

Configuration for Nagios
------------------------

The exact way to configure Nagios depends on several factors, e.g. how many Drupal
sites you want to monitor, the way Nagios is setup, ...etc.

The following way is just one of many ways to configure Nagios for Drupal. There are
certainly other ways to do it, but it all centers on using the check_drupal command
being run for each site.

1. Copy the check_drupal script in the nagios-plugin directory to your Nagios plugins
   directory (e.g. /usr/lib/nagios/plugins).

2. Change the commands.cfg file for Nagios to include the following:

   Nagios 2.x:

   define command{
     command_name  check_drupal
     command_line  /usr/lib/nagios/plugins/check_drupal -H $HOSTADDRESS$ -u $ARG1$ -T $ARG2$
   }

   Nagios 3.x:

   define command{
     command_name  check_drupal
     command_line  /usr/lib/nagios/plugins/check_drupal -H $HOSTADDRESS$ -U $ARG1$ -t $ARG2$
   }

   If you are monitoring multiple Drupal instances set up as virtual hosts, you
   may have to use $HOSTNAME$ instead of $HOSTADDRESS$ in the command_line
   parameter.

3. Create a hostgroup for the hosts that run Drupal and need to be monitored.
   This is normally in a hostgroups.cfg file.

   define hostgroup {
     hostgroup_name  drupal-servers
     alias           Drupal servers
     members         yoursite.example.com, mysite.example.com
   }

4. Defined a service that will run for this host group

   Nagios 2.x:

   define service{
     hostgroup_name         drupal-servers
     service_description    DRUPAL
     check_command          check_drupal!-U "unique_id" -t 2
     use                    generic-service
     notification_interval  0 ; set > 0 if you want to be renotified
   }

   Nagios 3.x:

   define service{
     hostgroup_name         drupal-servers
     service_description    DRUPAL
     check_command          check_drupal!unique_id!2
     use                    generic-service
     notification_interval  0 ; set > 0 if you want to be renotified
   }

Here is an explanation of some of the options:

-U "unique_id"
  This parameter is required.
  It is a unique identifier that is send as the user agent from the Nagios check_drupal script,
  and has to match what the Drupal Nagios module has configured.  Both sides have to match,
  otherwise, you will get "unauthorized" errors. The best way is to generate an MD5 or SHA1
  string from a combination of data, such as date, city, company name, ...etc. For example:

  $ echo "2003-Jan-17 Waterloo, Canada Honda" | md5sum

  The result will be something like this:

  645666c39f06514528987278c4071d85  -

  The resulting hash is hard enough to deduce, and gives a first level protection against snooping.

-t 2
  This parameter is optional.
  This means that if the Drupal site does not respond in 2 seconds, an error will be reported
  by Nagios. Increase this value if you site is really slow.
  The default is 2 seconds.

-P nagios
  This parameter is optional.
  For a normal site where Drupal is installed in the web server's DocumentRoot, leave this unchanged.
  If you installed Drupal in a subdirectory, then change nagios to sub_directory/nagios
  The default is the path nagios.

API
---

This module provides an API for other modules to report status back to Nagios.
Your module should implement the following hooks:

hook_nagios_info()
------------------
This hook is used to provide a way to enabled/disable a certain module from being included in Nagios
reports and alerts.

function yourmodule_nagios_info() {
  return array(
    'name'   => 'Your module name',
    'id'     => 'IDENTIFIER',
  );
}

hook_nagios()
-------------
Your module should have a yourmodule_nagios() function that does the actual work of checking something
and reporting back a status and some info.

The data returned is an associative array as follows:

array(
  'key'  => 'IDENTIFIER',
  'data' => array(
    'status' => STATUS_CODE,
    'type    => 'state', // Can be a 'state' for OK, Warning, Critical, Unknown) or can be 'perf', which does
                         // Cause an alert, but can be processed later by custom programs
    'text'   => 'Text description for the problem',
  ),
);

STATUS_CODE must be one of the following, defined in nagios.module:

  NAGIOS_STATUS_OK
  NAGIOS_STATUS_UNKNOWN
  NAGIOS_STATUS_WARNING
  NAGIOS_STATUS_CRITICAL

Here is an example:

function yourmodule_nagios() {
  $data = array();

  // Check something ...
  $count = ...
  if (!$count) {
    $data = array(
      'status' => NAGIOS_STATUS_WARNING,
      'type'   => 'state',
      'text'   => t('A very brief description of the warning'),
    );
  }
  else {
    $data = array(
      'status' => NAGIOS_STATUS_OK,
      'type'   => 'state',
      'text'   => '',
    );
  }

  return array(
    'key' => 'IDENTIFIER', // This identifier will appear on Nagios' monitoring pages and alerts.
    'data' => $data,
  );
}

For a real life example on how to use this API, check the performance.module in the devel project
at http://drupal.org/project/devel

hook_nagios_settings()
----------------------
This hook provides standard form API elements to be included at admin/settings/nagios. You can
set any thresholds you want in this hook.

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

