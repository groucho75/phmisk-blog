phmisk-blog
===========

A simple blog/ news section for a [Phmisk](https://github.com/groucho75/phmisk) site.

After adding this package to your Phmisk site you have:

* an admin section (protected by a http auth) to make simple CRUDL operations: create/read/update/delete/list news;
* a public news section, with list and single views (list using an infinite scroll jquery plugin);
* a public rss feed with latest news.

***

Installation
------------

1. Of course, having an installed Phmisk site is a prerequisite to install this package: please follow the [Phmisk](https://github.com/groucho75/phmisk) installation instrucions.

2. prepare the database table and popupate it with sample data, using `install.sql`

3. set the database connection configuration in `app/config.php`

4. copy the files from `app` and `ui` of this repo into your phmisk site

5. add the code from `composer-addon.json` of this repo into `composer.json` of your phmisk site

6. 5. add the code from `index-addon.json` of this repo into `index.php` of your phmisk site



