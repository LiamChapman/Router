# Router

A Simple PHP Router for mapping loaded Class methods based on matching URI string or pattern

*This script only routes classes, methods or closures. It assumes that your library of classes has already been included or autoloaded*

*This class has been updated to work with composer and now uses a namespace of 'Router'. If you need compatibility with PHP 5.2. Remove the namespace*

### Basic Usage Example

Below shows a simple example of routing the index to a specific class and method.

    require 'src/Router/router.php';
   
    $router = new Router\Router;
   
    $router->actions(array(
      '/' => 'myclass.mymethod'    
    ));
   
    $router->execute();
  
## URI Patterns

There are three available parameters for matching URLS

- :string (Strings)
- :int (Integers)
- :any (Anything!)

### Basic Usage Example of Patterns

Example of URI: *'/blog/article/test-post/1'*

    require 'src/Router/router.php';
    
    $router = new Router\Router;
    
    $router->actions(array(
      '/blog/article/:string/:int' => 'blog.article'
    ));
    
    $router->execute();
  
Then, in your class the patterns are passed through as accessible paramaters.

For Example:

    class Blog {
  
      function article ($title, $id) {
        // fetch data with title and id
      }
  
    }
  
By using specific patterns for strings and integers you can filter and sanitise so data is valid.

## Request types

By default all requests are found using GET. However, if you need to use an alternative request method such as POST, PUT or DELETE. All you need to do is prepend your method with the request type.

For example:

	class Blog {
	    
	    function POST_article () {
	        // send through post data here
	        $post = $_POST;
	    }                    
	    
	}
        
## Anonymous Functions / Closures

If you are running PHP >= 5.3 you are now able to pass through an anonymous function instead of linking to a specific class or controller. It can be helpful for creating some basic functions for an api or debugging some code.

For example:

    require 'src/Router/router.php';
   
    $router = new Router\Router;
   
    $router->actions(array(
      '/debug/:string/:int' => function ($title, $id) {
      	echo $title . ' - ' . $id;
      	exit;
      }
    ));
   
    $router->execute();
    
## Direct requests

If you do not need to include this class inside a framework or full on project; you can optionally use the router as a basic framework for small tasks.

For example:

	require 'src/Router/router.php';
	
	$app = new Router\Router;
	
	$app->request('/test', function () {
		echo 'Test!';
	});
	
	$app->execute();
	
By using the magic method __call() inside the class you can create specific request types too.

For example:

	require 'src/Router/router.php';
	
	$app = new Router\Router;
	
	$app->get('/test', function () {
		echo 'Test via get!';
	});
	
	$app->post('/test', function () {
		echo 'Test via post!';
	});
	
	$app->execute();
	
If you need to group actions with the direct request approach, you can still use the actions method along with it.

For example:

	require 'src/Router/router.php';
	
	$app = new Router\Router;
	
	$app->get('/test', function () {
		echo 'Test via get!';
	});
	
	$app->post('/test', function () {
		echo 'Test via post!';
	});
	
	$app->actions(array(
		'/testing' => function () {
			echo 'testing'
		},
		'/another-test/:string/:int' => function ($title, $id) {
			echo $title . ' ' . $id;
		}
	));
	
	$app->execute();
	
If you are choosing to use this inside a project, but you are also using the router as the source of the framework, you should still be able to route to exisiting classes that have been included / autoloaded.

For example:

	require 'src/Router/router.php';
	
	$app = new Router\Router;
	
	$app->get('/test', function () {
		echo 'Test via get!';
	});

	$app->request('/blog/article/:int', 'blog.article');
	
	$app->execute();
	
If you're using PHP < 5.4 you'll have to parse through variables to the callback scope with 'use' an example can be seen below. In PHP >= 5.4 the use can be kind of optional.

For example:

	require 'src/Router/router.php';
	
	$app = new Router\Router;
	$db  = new Database; // fake db class
	
	$app->get('/test', function () use ($db) {
		// do stuff here!
	});
	
	$app->execute();
	
## Static Calls

Instead of instantiating the class you can optionally do static calls instead.

For example:

	require 'src/Router/router.php';
	
	Router::get('/hello/:string', function ($string) {
		echo 'Hello ' . $string . '!';
		exit;
	});
	
	Router::post('/test', 'myclass.mymethod');

If you are using a combination of static calls and instatiating, in your static callbacks make sure to exit(); or die(); other wise a 404 Error will be appended to the output.

*If you inspect the source, you'll notice it isn't truly static, but it works. I may need to rebuild parts of it in the future.*

## How to include this in your project

This is now available on packagist so you can include this with composer the vendor/package name is "liam-chapman/router". Alternatively to get started you can place it in your own bepoke project autoloader and instatiate it inside a boot/setup/config file.

So for instance if you have an index.php file, which your requests get routed to; you might have a separate routes file to keep your project clean.

Example project directory structure:

- my_project/
	- config/
		- routes.php
	- lib/
		- router.php
	- index.php
	- .htaccess
	
Inside your index.php file you might have something like this:

	<?php
	
	// other stuff here like constants and settings etc.
	
	// autoload your classes - http://php.net/manual/en/function.spl-autoload-register.php
	spl_autoload_register('your_autoload_function');
	
	// instantiate your router
	$router = new Router\Router;
	
	// get routes
	$router->actions( include_once('config/routes.php') );
	
	// start router
	$router->execute();

*Obviously this is a simple example, you would also use full paths with dirname(__FILE__) or __DIR__*	
	
Inside your config/routes.php you then might have something like this:

	<?php
	
	return array(
		'/blog/article/:int' => 'blog.article',
		'/blog'				 => 'blog.index',
		'/'					 => 'pages.home'
	);
	

*look at other notes below for .htaccess rules!*
	
### Other notes

Something to remember is if you are using this as a framework, you'll have to use a .htaccess file and run these actions from a new file like an index.php. Below is a basic .htaccess code you can use to get started.

	AddDefaultCharset utf-8
	Options FollowSymLinks
	Options +Indexes
	RewriteEngine On
	RewriteRule ^(.*)$ index.php?$1 [QSA,L]

When intialising the router there is the option to ignore the query string as apart of the URI. By default it does. By setting it to false it will append it to the uri so you can create routes that have a query string

For example:

	$router = new Router\Router(0);
	
	$router->actions(array(
		'/test?var=hello' => 'myclass.mymethod'
	));
	
	$router->execute();

With the query string ignored, you should still be able to pass through url parameters.