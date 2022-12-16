D3R Developer Test Project Specification
========================================

We've grabbed a SQL dump from data.gov.uk which contains a list of all the
payments made by Chichester District Council to suppliers in October 2010 with
a value of £500 or higher. As we're based in Chichester we like to keep an eye
on these things! The task is to create a simple listing of the data that can
be used to probe where all that money went.

We've split it into 3 distinct chunks. We'd like to be able to see each chunk
seperately so when you please submit a zip / archive for each phase with
sensible names containing the project as it was when you finished that
section.

We'd like you to work with the basic structure that we've outlined but feel
free to create folders to house javascript / css / whatever as you like.
Please keep your classes in lib/Local (using whatever file layout you like).

Please don't spend more than 2 hours on each of the two sections. If you're
done more quickly that this, there is a bonus setion that you are very welcome
to have a go at to impress us if you have the time but don't feel obliged.

When you've finished the project, please send it back to us at jobs@d3r.com.
Please tell us roughly how long it took you to do it and enclose any notes you
feel we'll need to get it all running locally.

Good luck!

The D3R Team


Level 1
=======
In the specification/designs folder you'll find a flat design called
Listing.png. We'd like you to cut up this design into valid, semantic HTML5.
We're happy for the design to gracefully degrade in older browsers (eg: don't
worry about curved corners in IE8!).

We've also provided a PSD, if you're into that kind of thing. We did however already cut out the ratings images and background tile so Photoshop shouldn't be a requirement.

The font we've used is called 'Source Sans Pro' and should be available quite
freely on the web. Here's the page about it on Adobe.com -
http://blogs.adobe.com/typblography/2012/08/source- sans-pro.html.

If you're more confortable using a pre-processor like SASS/LESS, do go ahead and use that here too.


Level 2
=======
Create a suitable MySQL database from the structure and data contained in
specification/data/payments.sql.

Using this data, write some PHP that can pull it into the page and display a
real listing. You'll also need to paginate the data - the design suggests five
rows per page.

There's no requirement to do any sorting of the data (eg: by clicking on
headings) so don't worry about that.

We've provided you with a couple of classes to do some of the heavy lifting -
a simple DB class (lib/D3R/Db.php) and a simple data model class
(lib/D3R/Model.php). As mentioned above, please keep the classes specifically written by you in lib/Local.

We've also given you an autoloader at lib/autoloader.php. As long as you keep to PSR-0 (http://www.php-fig.org/psr/psr-0/) it should play nicely.


Bonus Level
===========

So you finished the above with some time to spare? Great. Entirely optionally, have a look at one of these extras that allow you to show off your specialist skillset a bit more.

1. Bit of a CSS guru? Upgrade your front end built in Level 1 to be responsive. We'll leave it to you to make decisions about breakpoints and how things should look. We know it's just one page, but we're looking here to see how you make decisions about how to structure your CSS.

2. Like to get knees deep in javascript? Enhance the listing you created in Level 2 to pull in the
paginated data via AJAX. We've provided you (in the
javascript folder) with jQuery which we'd like you to stick with as the base library. We'll be particularly impressed if you think about what happens without javascript and how URLs might change.

3. Like to stay strictly back-end? Expose the payments data over an API. We'll let you decide how to structure things and what functionality to include but please make sure it's easy for us to test.


Test Project Layout
===================

This is a very simple test project stub framework that provides some
rudimentary tools for building a small web application. The layout is as
follows:

lib/

    autoloader.php      - a PSR-0 autoloader for the lib folder
    D3R/
        Model.php       - A simple database model base class ready for you to subclass
        Db.php          - A database access layer, used by D3R\Model
        Exception.php   - A generic exception class

    Local/              - The folder where your classes should go - the layout is up to you

config/
    database.php        - Database configuration file - please update with your settings as required

specification/

    data/               - Database dumps that you can use for the project
    design/             - Page designs that you will need to implement
        RatingOff.png   - A transparent PNG of the rating symbol in the off state
        RatingOn.png    - And one in the on state

javascript

    jquery.min.js       - Version 1.11 of the jQuery javascript library

images
    tile.png            - A tileable PNG for the background. Nobody wants to be trying to create that!


README.txt              - This file

index.php               - The 'front controller' - your starting point

