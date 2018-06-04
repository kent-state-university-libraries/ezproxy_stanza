CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintenance
 * Maintainers


INTRODUCTION
------------

The EZProxy Stanza module helps to manage your EZProxy configuration files, and
keeps the stanzas up to date with OCLC's stanzas.

 * For a full description of the module visit:
   https://www.drupal.org/project/ezproxy_stanza

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/ezproxy_stanza


REQUIREMENTS
------------

This module requires the following outside of Drupal core.
 * Drupal's private filesystem configured
 * Git installed on your web server
 * A private, hosted git repository in a management system such as GitLab or
   GitHub to store your EZProxy configuration files.
 * cpliakas/git-wrapper (will automatically be installed if using composer)
 * Weight module (will automatically be installed if using composer)


INSTALLATION
------------

 * Install the EZProxy Stanza module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > People > Permissions and assign the "EZProxy
       Admin" role to users you want to be able to make changes to the EZProxy
       configuration files.
    3. Navigate to Administration > Content > EZProxy > Settings and provide the
       URL and access information for the private git repository.
    4. Choose whether or not to have the local repository automatically updated
       from the remote. Not configuring this has a performance impact. The
       webhook will keep your local repository up to date with changes made
       outside of this system. If you don't configure this, every time you
       perform an action your system will need to check in with your remote
       repository to ensure it is up to date.
    5. Authenticate the repository by entering the private SSH key. Save.



If using SSH to authenticate with your local repo, there is a known issue when
initially setting this up. visit issue #2922545: Adding to known_hosts on
initial install.

 * https://www.drupal.org/node/2922545


MAINTENANCE
-----------

However often your Drupal cron is configured, your system will check if OCLC has
made any updates to its stanzas. Any updates will be imported into your Drupal
system. The respective stanza's node will be updated (and a revision made). If
the stanza is in the config.txt all EZProxy administrators will be sent an
email alert.


MAINTAINERS
-----------

 * Joe Corall (joe.corall) - https://www.drupal.org/u/joecorall
