# Router

A Simple PHP Router for mapping loaded Class methods based on matching URI string or pattern

*This script only routes classes and methods. It assumes that your library of classes has already been included or autoloaded*

### Basic Usage Example

Below shows a simple example of routing the index to a specific class and method.

    require 'router.php';
    $router = new Router;
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

    require 'router.php';
    $router = new Router;
    $router->actions(array(
      '/blog/article/:string/:int' => 'blog.article'
    ));
    $router->execute();
  
Then, in your class the patterns are passed through as accessible paramaters e.g.

    class Blog {
  
      function article ($title, $id) {
        //fetch data with title and id
      }
  
    }
  
By using specific patterns for strings and integers you can filter and sanitise so data is valid.

## Request types

By default all requests are found using GET. However, if you need to use an alternative request method such as POST, PUT or DELETE. All you need to do is prepend your method with the request type e.g.

        class Blog {
            
            function POST_article () {
                //send through post data here
                $post = $_POST;
            }                    
            
        }
        
## Anonymous Functions / Closures

If you are running PHP >= 5.3 you are now able to pass through an anonymous function instead of linking to a specifc class or controller. It can be helpful for creating some basic functions for an api or debugging some code.


    require 'router.php';
    $router = new Router;
    $router->actions(array(
      '/debug/:string/:int' => function ($title, $id) {
      	echo $title . ' - ' . $id;
      	exit;
      }
    ));
    $router->execute();
