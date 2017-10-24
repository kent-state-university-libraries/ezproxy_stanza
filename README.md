# EZProxy Stanza

## Install module

## Optional - auto-deploy changes

** Caution ** try these steps on a test server before configuring in your production environment

### Clone your repository and setup a git hook to restart EZProxy on changes
git clone git@example.com:/ezproxy/config config
cd config
echo '#!/bin/sh
/usr/local/ezproxy/ezproxy restart' > .git/hooks/post-merge
chmod +x .git/hooks/post-merge

### Move your repository to your EZProxy directory
cd ../
mv config/* /usr/local/ezproxy/
mv config/.git /usr/local/ezproxy/


### Configure cron to check for EZProxy updates every minute
crontab -e

# Check for updates to EZProxy config.txt
* * * * * cd /usr/local/ezproxy && git pull origin master > /dev/null 2>&1
