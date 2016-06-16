=== Plugin Name ===
Contributors: jeryj
Donate link: http://engagingnewsproject.org
Tags: quiz, create, front-end, user, content, engaging
Requires at least: 4.4
Tested up to: 4.4
Stable tag: 4.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allow you and your users to create modern, engaging quizzes to share and embed.

== Description ==

**This Plugin is in very active development and is not usable right now.**

Create quizzes to engage your users. Easily embed the quiz on any website.

Our research on quizzes shows that quizzes help your users:

- Increase time on site
- Learn more
- Enjoy your site more

Using the Engaging Quiz Creator, you can easily create quizzes that you can embed on your website, and even allow your users to create quizzes for sharing/embedding too.

== Installation ==

1. Upload the 'engaging-quiz-creator' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'http://your-site.com/enp-quiz/dashboard' to get to your dashboard and start creating quizzes.

**Default URLs**

*Dashboard*
http://your-site.com/enp-quiz/dashboard

*Create/Edit Quiz*
http://your-site.com/enp-quiz/quiz-create

*Preview Quiz/Quiz Settings*
http://your-site.com/enp-quiz/quiz-preview

*Publish Quiz/Quiz Embed Code*
http://your-site.com/enp-quiz/quiz-publish

*Quiz Results*
http://your-site.com/enp-quiz/quiz-results

*Create/Edit AB Test*
http://your-site.com/enp-quiz/ab-test

*AB Test Results*
http://your-site.com/enp-quiz/ab-results


**Advanced Installation**
The plugin will create two configuration files on plugin activation:
- enp-quiz-config.php
- enp-quiz-database-config.php

*enp-quiz-config.php*
This file is found in the wp-content folder. This is the main config file. You can change the path to your Template folders so you can change the views of the plugin. Templates default to the default plugin templates.

*enp-quiz-database-config.php*
This file is located in your DOCUMENT_ROOT. It's outside of your public_html or domain-name.com folder (depending on your host). This is to protect it from prying eyes. It is included by the enp-quiz-config.php file. Feel free to move it, but be sure to change the path in the enp-quiz-config.php file too, otherwise the plugin won't know how to connect to your database.

This odd set-up will allow you to move your quiz database to an entirely different server if you ever get hit with a lot of usage. Also, the plugin is written with PDO as the database connection layer, so you could even run your quiz database as something other than MySQL if you want.


== Frequently Asked Questions ==



== Screenshots ==



== Changelog ==



== Upgrade Notice ==


== Arbitrary section ==
