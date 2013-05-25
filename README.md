# Router
======
Simple PHP Router for mapping loaded Class methods based on matching URI string or pattern

*This script only routes classes and methods. It assumes that your library of classes has already been included or autoloaded*

## Basic Usage Example

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

Example of URI: '/blog/post/test-post/1'

  require 'router.php';
  $router = new Router;
  $router->actions(array(
    '/blog/post/:string/:int' => 'blog.post'
  ));
  $router->execute();
  
Then in your class thee patterns are passed through as accessible paramaters e.g.

  class Blog {
  
    function post ($title, $id) {
      //fetch data with title and id
    }
  
  }
  
By using specific patterns from strings and integers you can filter and sanitise as you script so data is valid.
