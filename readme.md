This service utilizes several APIs to display new books in the library (sorted by fund and date of arrival). It is built on the web framework CodeIgniter, a "rapid development" framework which is useful for quickly writing new features without poring through extensive documentation.

The big picture of how this works (like which APIs it draws from) can be found [here](https://www.oclc.org/content/dam/community/WorldShare/global2019/Presentations/cowing-lightning-talk.pdf), and a working version can be found [here](https://library.woodbury.edu/newbooks).

# What you’ll need

To install a version to test with at your own library, you will need the following:

* A web server to which you can upload HTML pages, PHP scripts, etc.

    * This web server should also include a MySQL database that you can use to store the data.

* A WSKey from OCLC that has access to:

    * Acquisitions API (WMS_ACQ)

    * Collection Management API (WMS_COLLECTION_MANAGEMENT)

* A user created in WMS who has these permissions:

    * ACQ_READ_ONLY_USER

    * COLLECTION_MANAGEMENT_USER

    * *(This type of access will be changing per OCLC, but for now it will work, I’ll update notes to reflect new API access method once that time comes)*

* _(Optional)_ A [Google Books API key](https://cloud.google.com/docs/authentication/api-keys?visit_id=637155151722963656-1968642627&rd=1)

* _(Optional)_ A [LibraryThing developer key](http://www.librarything.com/services/keys.php)

* A willingness to experience a setback or two! These things rarely go perfectly and by-the-book, and that’s OK since our libraries and needs are different.

# Installation steps

1. In MySQL, create a new database with a name of your choosing.

2. See the SQL code in the appendix that you can use to create the schema (the structure) of the database. The schema is explained in the presentation slides linked above.

Your MySQL database should now be ready to receive book data! Now it’s time to set up the application itself:

3. Go to the [repository on Github](https://github.com/jaredcowing/wmsNewBooks), and choose clone/download -> download ZIP

4. Rename the wmsNewBooks folder to whatever name you’d like to choose (this will become part of the URL path for your installation, eg mylibrary.edu/nameThatIChose )

5. Upload the renamed folder to your webserver

6. Go to application/config/databaseRENAME.php (this is where CodeIgniter framework will get its core database settings)

    1. Rename file to database.php (this file will contain sensitive information, so if you make this a repo make sure to ignore this file). Set the following variables:

    2. `username` = the username of a user authorized to create/read/update/delete (CRUD) in your database

    3. `password` = that user’s password

    4. `database` = name of the db created in MySQL

7. Go to application/config/config.php (this is where CodeIgniter framework keeps its core configuration settings)

    5. `$config['base_url'] = '[https://yoururl/path](https://yoururl/path/)';` *(no slash at end)*

    6. `$config['sess_driver'] = 'files'`;

    7. You may keep session data set to ‘database’ like I do (sessions are stored in a MySQL database), but if you do then a little [extra setup is required](https://codeigniter.com/user_guide/libraries/sessions.html#session-drivers). Storing session data in files is the simplest option.

8. Open newbooks.js and set `var baseURL="[https://yoururl/path](https://yoururl/path)";` *(no slash at end)*

9. **Finally, the library-specific customizations:** Go to application/libraries/newBooksConfigRENAME.php (this is where application-specific settings are to help you customize to your library)

    8. Rename file to newBooksConfig.php (this file will contain sensitive information, so if you make this a repo make sure to ignore this file).

    9. Comments in the file will explain what you can configure. Settings include your API access keys, website & catalog URLs, fund codes, and how you’d like to determine the "arrival" date of an item.

# Running application for the first time

The application is controlled by URL commands; you navigate to a URL containing a command using a web browser to execute. The first command you should run is one to load all submitted orders; the same command will trigger an auto-loading of all items contained in those orders. This could be a lot of items, perhaps tens of thousands! My strong suggestion is to ingest this data in small, successive "gulps" rather than a tsunami so that you can easily stop if/when something goes wrong. There are two ways to do this:

1. Make "statute of limitations" a date within the past couple months, so that only the very most recent items are loaded. To do this:

    1. Go to application/libraries/newBooksConfig.php

    2. Find the method getStatute()

    3. Change the date to something within the past month or two (YYYY-MM-DD) just to start

        1. (You can always come back and move this date further back once you’re ready to load more books).

2. Manually restrict how many orders are loaded at a time, then run the load command repeatedly ("load 20 orders, OK that went fine so load another 20, OK now another 20…"). To do this:

    4. Open application/controllers/bookFeed.php

    5. Find these lines in the load function and comment/uncomment such that it reads :

```
else{ //startIndex +=100;

$doneFlag=true; }
```

(This prevents the application from automatically getting more orders once it’s digested its first "gulp").

Then change itemsPerPage in the below line to reflect the number of orders you want to load in each "gulp" of order data, and startIndex to the number where you left off last time you loaded orders:

`$resourceURLp2="/purchaseorders?q=SUBMITTED&startIndex=**0**&itemsPerPage=**100**";`

3. Now you’re ready to run the order command. Enter this URL: https://your/URL/index.php/Bookfeed/load/orders

4. It is quite possible you’ll run into errors (every library is different, perhaps there’s a circumstance I didn’t account for in the code). Don’t worry, this application can only read OCLC data, so none of your master WMS data is at risk of being altered or corrupted. The only thing that might get corrupted is your local MySQL order data stored by this application, so worst-case scenario you have to clean corrupted data manually in a graphic editor (such as myPHPadmin ) or just empty the tables and start over. Any PHP errors should display on your screen, which you are welcome to send to me (or perhaps you are able to figure out a fix on your own which I’d love to know about).

# Back-end commands available in the application

When executing any of the below commands, a raw-text feed of status messages will be printed to your screen (along with any errors).

## Loading new orders, copies & items:

`https://your/URL/index.php/Bookfeed/load/orders`

This command will tell the application to retrieve orderNumbers from all placed orders in WMS. For each orderNumber it gets, it will check to see if your local database already has that number on file (has loaded the order). If it’s a new orderNumber, then it proceeds to load all items associated with that order, and any copies associated with that OCLC number.

If there are multiple copies of an item, it is not always possible to know which barcoded copy is associated with a specific order item. To that end, the application will load all the copies and treat them all as new. 

Order items that have not been received will get item-level info loaded but not copy-level info (it is waiting for receipt before it will try to retrieve that information from OCLC).

To view and alter this process, go to application/controllers/Bookfeed.php ("load" function/method)

## Check on receipt:

`https://your/URL/index.php/Bookfeed/autoUpdateReceived/go`

This will tell the application to check in on any order items that are marked not received (so long as they were ordered after your cutoff date, "statute of limitations"). If it has been received, it will be so marked and the application will try to load/refresh any copy-level data from OCLC. 

If the item has been cancelled, it will be so recorded and the application will no longer try to load its information.

To view and alter this process, go to application/controllers/Bookfeed.php ("autoUpdateReceived" function/method)

## Check on cataloging:

`https://your/URL/index.php/Bookfeed/autoUpdateCopies/go`

This will tell the application to check in on any received items that have incomplete copy info associated (by default it just checks for a missing call number). The copy data in the application database will be erased & replaced with the latest copy data from OCLC.

*There are also a few database-cleanup commands I wrote for my own troubleshooting (like cleaning up corrupted cover image and ISBN info).. perhaps they are useful to others as well.*

# Using this tool as an end-user

The URL of the front-facing main menu will be:

`https://your/URL/index.php/Bookview`

You can also create deep links to go automatically to a filtered list of new books (like for embedding in a LibGuide). Green highlight used to make the pattern clearer.

The below command gives you a list filtered by Fund irregardless of how long ago it was ordered (that could be a lot of books!):

`https://your/URL/index.php/Bookview/viewF/fundCodeHere`

	* Example: [https://jaredcowing.com/newBooks/index.php/Bookview/viewF/GAMEB](https://jaredcowing.com/newBooks/index.php/Bookview/viewF/GAMEB) *

The below command gives you a list filtered by Fund and Age:

`https://your/URL/index.php/Bookview/viewFA/fundCodeHere/ageGoesHere`

	* Example: [https://jaredcowing.com/newBooks/index.php/Bookview/viewFA/GAMEB/6M](https://jaredcowing.com/newBooks/index.php/Bookview/viewFA/GAMEB/6M) *

*(Your options for age are currently 1M, 3M, 6M, 1Y, 2Y)*

It is anticipated that this application will be typically presented inside an iFrame on another page (like a libguide or on a library’s website) rather than a standalone page. To that end, rather than making the layout responsive to screen size, mobile vs full view is determined in the URL you use to call the application. To use the mobile Sizing:

`https://your/URL/index.php/Bookview/viewFS/fundCodeHere/m`

	* Example: [https://jaredcowing.com/newBooks/index.php/Bookview/viewFS/GAMEB/](https://jaredcowing.com/newBooks/index.php/Bookview/viewFS/GAMEB/m)[m](https://jaredcowing.com/newBooks/index.php/Bookview/viewFS/GAMEB/m) *

`https://your/URL/index.php/Bookview/viewFAS/fundCodeHere/ageGoesHere/m`

* Example: [https://jaredcowing.com/newBooks/index.php/Bookview/viewFAS/GAMEB/6M/m](https://jaredcowing.com/newBooks/index.php/Bookview/viewFAS/GAMEB/6M/m) *

# Appendix: Code architecture

This code was built using CodeIgniter, a lightweight web development framework. It uses a Model-View-Controller (MVC) architecture; in other words:

* Transactions with the database (saving new books, queries, etc) are handled by scripts in the Application -> Models folder.

* Transactions that act as intermediary between the client side (web browser), API providers (OCLC, Google, LibraryThing), and the Models are in the Application -> Controllers folder. 

    * Bookfeed contains the code used when loading new books from OCLC or updating book status (used by librarians rather than patrons).

    * Bookview contains the code used when patrons are querying the application for new books to view.

* Transactions that control which data is displayed to the user and in what layout (after it is queried/manipulated) are in the Application -> Views -> Templates folder.

Other useful file locations to know about:

* Client/browser-side code that control interactive elements and styling for the user side are in the root level (there is 1 javascript file, and there are 2 css files).

* Local library preferences that can be customized are in the Libraries folder.

* Some additional configurations are set in the Config folder (most don’t need adjusting).

* Most other folders are framework code that do not require alteration or maintenance.

# Appendix: MySQL setup code

```CREATE TABLE 'copy' (

                  'id' int(10) NOT NULL AUTO_INCREMENT,

                  'callNum' varchar(40) NOT NULL,

                  'ocn' int(15) NOT NULL,

                  'branch' varchar(7) NOT NULL,

                  'location' varchar(40) NOT NULL,

                  'dateLoaded' date NOT NULL,

                  'barcode' varchar(16) NOT NULL,

                  PRIMARY KEY ('id')

) ENGINE=MyISAM AUTO_INCREMENT=25914 DEFAULT CHARSET=latin1;

CREATE TABLE 'item' (

                  'orderItemNum' varchar(15) NOT NULL,

                  'orderNum' varchar(30) NOT NULL,

                  'orderStat' varchar(15) NOT NULL,

                  'receiptStat' varchar(15) NOT NULL,

                  'fund' varchar(40) NOT NULL,

                  'receiptDate' date NOT NULL,

                  'orderDate' date NOT NULL,

                  'matType' varchar(15) NOT NULL,

                  'title' varchar(130) NOT NULL,

                  'person1' varchar(40) NOT NULL,

                  'isbn' varchar(14) NOT NULL,

                  'coverURL' varchar(200) NOT NULL,

                  'ocn' int(15) NOT NULL,

                  PRIMARY KEY ('orderItemNum')

) ENGINE=MyISAM AUTO_INCREMENT=5583 DEFAULT CHARSET=latin1;

CREATE TABLE 'order' (

                  'orderNum' varchar(20) NOT NULL,

                  PRIMARY KEY ('orderNum')

) ENGINE=MyISAM AUTO_INCREMENT=639 DEFAULT CHARSET=latin1;```

