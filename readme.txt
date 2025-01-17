=== mnw ===
Contributors: adrianlang
Tags: microblogging, omb, identica, laconica
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: trunk

Creates a fullblown OpenMicroBlogging-compatible microblogging service.

== Description ==

mnw gives users of an OMB service the possibility to subscribe to your blog directly.
Your blog will automatically send OMB notices to subscribers when you publish a new
post, page or attachment (configurable). You may as well post arbitrary OMB notices
from the wordpress admin interface.

Moreover, mnw allows your blog to subscribe to a remote user and later on receive
his messages. To subscribe to a user, enter the your blog‘s URL as your profile URL
on the remote user‘s remote subscribe form.

== Installation ==
1. Copy the plugin to your wordpress plugin directory.
1. Copy mnw-themepage.php as mnw.php to the directory of your
  current theme.
1. Adjust this mnw.php to fit to your theme (archives.php is a good start to see
  how your theme looks like on custom pages).
1. Create a new page in wordpress. This page is the main frontend of your OMB
  instance. Give it a sensible name, keep the content empty and select 'mnw' as
  template. Publish this page.
1. Activate the plugin.
1. Configure the plugin in the corresponding admin menu.
1. (Optional) Copy omb.png to the directory of your current theme. Enable the sidebar
  widget displaying a count of your omb subscribers.
1. (Optional) Add a sidebar widget showing received messages.

== Changelog ==
= 0.1 =
First public release. Complete profile settings,
                           profile updating through omb, consistent frontend,
                           notice sending on publish.

= 0.1b =
Service release, fixes a serious fault in the
                           description of the microblog notice template.

= 0.1c =
Adds an optional sidebar widget which displays the
                           count of OMB subscribers and a link to the subscribe
                           page.

= 0.2 =
Adds receiving & displaying of notices. mnw now uses
                           libomb for the OMB handling.

= 0.3 =
Adds the possibility to send arbitrary notices, an
                           admin page listing all notices and a dashboard widget
                           showing stats.

= 0.4 =
Reduces the number of included files, completely
                           restructures themepage handling, adds atom feeds and
                           public html notice lists, uses DiSo‘s Simple-XRDS

