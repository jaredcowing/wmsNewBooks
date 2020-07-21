This service utilizes several APIs to display new books in the library (sorted by fund and date of arrival). It is built on the web framework CodeIgniter, a "rapid development" framework which is useful for quickly writing new features without poring through extensive documentation.

The big picture of how this works (like which APIs it draws from) can be found [here](https://www.oclc.org/content/dam/community/WorldShare/global2019/Presentations/cowing-lightning-talk.pdf), and a working version can be found [here](https://library.woodbury.edu/newbooks).

# What you’ll need

To install a version to test with at your own library, you will need the following:

* A web server to which you can upload HTML pages, PHP scripts, etc.

* A WSKey from OCLC that has access to:

    * Acquisitions API (WMS_ACQ)

    * Collection Management API (WMS_COLLECTION_MANAGEMENT)

* A user created in WMS who has these permissions:

    * ACQ_READ_ONLY_USER

    * COLLECTION_MANAGEMENT_USER

    * *(This type of access will be changing per OCLC, but for now it will work, I’ll update notes to reflect new API access method once that time comes)*
	 
* _(Optional)_ A MySQL database to store data if you'd like to use advanced database features, but the default storage is now a simple SQLite file which comes preformatted when you install. See the SQL code in the appendix that you can use to create the schema (the structure) of the database. The schema is explained in the presentation slides linked above.

* _(Optional)_ A [Google Books API key](https://cloud.google.com/docs/authentication/api-keys?visit_id=637155151722963656-1968642627&rd=1)

* _(Optional)_ A [LibraryThing developer key](http://www.librarything.com/services/keys.php)

* A willingness to experience a setback or two! These things rarely go perfectly and by-the-book, and that’s OK since our libraries and needs are different.

# Installation steps

1. Go to the [repository on Github](https://github.com/jaredcowing/wmsNewBooks), and click "code" button -> download ZIP

2. Extract and rename the wmsNewBooks folder to whatever name you’d like to choose (this will become part of the URL path for your installation, eg mylibrary.edu/nameThatIChose )

3. Go to application/config/databaseRENAME.php (this is where CodeIgniter framework will get its core database settings)

    1. Rename file to database.php
	 
        _* The default database configuration uses SQLite which stores data in a simple file in the db folder. If you'd like to stick with SQLite, you can simply proceed to step 4. If you'd like to use MySQL instead, then you'll need to create a database using [the schema in the appendix](#appendix-mysql-setup-code) and then set the following variables:_

        _* `username` = the username of a user authorized to create/read/update/delete (CRUD) in your database_

        _* `password` = that user’s password_

        _* `database` = name of the db created in MySQL_
	
        _* `dbdriver` = mysqli_

4. Go to application/config/config.php (this is where CodeIgniter framework keeps its core configuration settings)

    * `$config['base_url'] = 'https://yoururl/path';` *(no slash at end)*

    * `$config['sess_driver'] = 'files'`;

    * You may keep session data set to ‘database’ like I do (sessions are stored in a MySQL database), but if you do then a little [extra setup may be required](https://codeigniter.com/user_guide/libraries/sessions.html#session-drivers). Storing session data in files is the simplest option.

5. In root folder, open newbooks.js and in second line set `var baseURL="https://yoururl/path";` *(no slash at end)*

6. **Finally, the library-specific customizations:** Go to application/libraries/newBooksConfigRENAME.php (this is where application-specific settings are to help you customize to your library)

    * Rename file to newBooksConfig.php (this file will contain sensitive information, so if you make this into a repo make sure to ignore this file).

    * Comments in the file will explain what you can configure. Settings include your API access keys, website & catalog URLs, fund codes, and how you’d like to determine the "arrival" date of an item.

7. Upload your renamed folder containing these files to your webserver.

# Running application for the first time

The application is controlled by URL commands; you navigate to a URL containing a command using a web browser to execute. The [next section](#back-end-commands-available-in-the-application) details those commands, but you can alternatively go to a dashboard page which contains links to each of the commands (so all you need to do is point and click).

1. Go to this page to enter your login that was set in your configuration/preferences: `https://your/URL/index.php/Login/login`

2. After login, you'll be presented with the dashboard page. The first thing you'll want to do is load order data from OCLC so that your application has some books that patrons can look up. While there is a way to load all your latest order info at once, I suggest you load a few at a time first just in case there are errors to address. There's a suggested command near the top of that page you can execute which only loads 100 orders at a time.

3. Once you feel comfortable that things are loading OK, you're welcome to use the command on the dashboard that loads all outstanding order data.

4. As mentioned before, it is quite possible you’ll run into errors (every library is different, perhaps there’s a circumstance I didn’t account for in the code). Don’t worry, this application can only read OCLC data, so none of your master WMS data is at risk of being altered or corrupted. The only thing that might get corrupted is your local data stored by this application, so worst-case scenario you have to empty your database and start over. Any PHP errors should display on your screen, which you are welcome to send to me (or perhaps you are able to figure out a fix on your own which I’d love to know about).

# Back-end commands available in the application

When executing any of the below commands, a raw-text feed of status messages will be printed to your screen (along with any errors). You can execute the commands using the tips below, or by logging in and going to the command dashboard: `https://your/URL/index.php/Login/login`

## Loading new orders, copies & items:

`https://your/URL/index.php/Bookfeed/load/orders`

This command will tell the application to retrieve orderNumbers from all placed orders in WMS. For each orderNumber it gets, it will check to see if your local database already has that number on file (has loaded the order). If it’s a new orderNumber, then it proceeds to load all items associated with that order, and any copies associated with that OCLC number.

If there are multiple copies of an item, it is not always possible to know which barcoded copy is associated with a specific order item. To that end, the application will load all the copies and treat them all as new. 

Order items that have not been received will get item-level info loaded but not copy-level info (it is waiting for receipt before it will try to retrieve that information from OCLC).

To view and alter this process, go to application/controllers/Bookfeed.php ("load" function/method)

## Check on receipt:

`https://your/URL/index.php/Bookfeed/updateReceived/default`

This will tell the application to check in on any order items that are marked not received (so long as they were ordered after your cutoff date, "statute of limitations"). If it has been received, it will be so marked and the application will try to load/refresh any copy-level data from OCLC. 

If the item has been cancelled, it will be so recorded and the application will no longer try to load its information.

To view and alter this process, go to application/controllers/Bookfeed.php ("updateReceived" function/method)

## Check on cataloging:

`https://your/URL/index.php/Bookfeed/updateCopies/blankCN`

This will tell the application to check in on any received items that have incomplete copy info associated (by default it just checks for a missing call number). The copy data in the application database will be erased & replaced with the latest copy data from OCLC.

*There are also a few database-cleanup commands I wrote for my own troubleshooting (like cleaning up corrupted cover image and ISBN info).. perhaps they are useful to others as well.*

# Using this tool as an end-user

The URL of the front-facing main menu will be:

`https://your/URL/index.php/Bookview`

You can also create deep links to go automatically to a filtered list of new books (like for embedding in a LibGuide). Green highlight used to make the pattern clearer.

The below command gives you a list filtered by Fund irregardless of how long ago it was ordered (that could be a lot of books!):

`https://your/URL/index.php/Bookview/viewF/fundCodeHere`

*Example:* `https://jaredcowing.com/newBooks/index.php/Bookview/viewF/GAMEB`

The below command gives you a list filtered by Fund and Age:

`https://your/URL/index.php/Bookview/viewFA/fundCodeHere/ageGoesHere`

*Example:* `https://jaredcowing.com/newBooks/index.php/Bookview/viewFA/GAMEB/6M`

*(Your options for age are currently 1M, 3M, 6M, 1Y, 2Y)*

It is anticipated that this application will be typically presented inside an iFrame on another page (like a libguide or on a library’s website) rather than a standalone page. To that end, rather than making the layout responsive to screen size, mobile vs full view is determined in the URL you use to call the application. To use the mobile Sizing:

`https://your/URL/index.php/Bookview/viewFS/fundCodeHere/m`

*Example:* `https://jaredcowing.com/newBooks/index.php/Bookview/viewFS/GAMEB/`

`https://your/URL/index.php/Bookview/viewFAS/fundCodeHere/ageGoesHere/m`

*Example:* `https://jaredcowing.com/newBooks/index.php/Bookview/viewFAS/GAMEB/6M/m`

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

If you so desire to use MySQL instead of SQLite, the below code may help you set up your schema before loading data from OCLC.
```
CREATE TABLE 'copy' (

                  'id' int(10) NOT NULL AUTO_INCREMENT,

                  'callNum' varchar(40) NOT NULL,

                  'ocn' int(15) NOT NULL,

                  'branch' varchar(7) NOT NULL,

                  'location' varchar(40) NOT NULL,

                  'dateLoaded' date NOT NULL,

                  'barcode' varchar(16) NOT NULL,

                  PRIMARY KEY ('id')

) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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

) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE 'order' (

                  'orderNum' varchar(20) NOT NULL,

                  PRIMARY KEY ('orderNum')

) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```
