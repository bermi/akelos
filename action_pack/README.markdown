= Action Pack -- On Akelos from request to response

Action Pack splits the response to a web request into a controller part
(performing the logic) and a view part (rendering a template). This two-step
approach is known as an action, which will normally create, read, update, or
delete (CRUD for short) some sort of model part (often backed by a database)
before choosing either to render a template or redirecting to another action.

Action Pack implements these actions as public methods on Action Controllers
and uses Action Views to implement the template rendering. Action Controllers
are then responsible for handling all the actions relating to a certain part
of an application. This grouping usually consists of actions for lists and for
CRUDs revolving around a single (or a few) model objects. So ContactsController
would be responsible for listing contacts, creating, deleting, and updating
contacts. A WeblogController could be responsible for both posts and comments.

Action View templates are written using PHP ans Sintags (sor of Ruby) 
mingled in with the HTML. To avoid cluttering the templates with code, a bunch 
of helper classes provide common behavior for forms, dates, and strings. 
And it's easy to add specific helpers to keep the separation as the application 
evolves.

Note: Some of the features, such as scaffolding and form building, are tied to
AkActiveRecord (an object-relational mapping package), but that doesn't mean 
that Action Pack depends on Active Record. Action Pack is an independent package
that can be used with any sort of backend. Read more about the role Action
Pack can play when used together with Active Record on
http://www.akelos.org.

A short rundown of the major features:

* Actions grouped in controller as methods instead of separate command objects
  and can therefore share helper methods

    class CustomersController extends AkActionController{
        public function show(){
            $this->Customer = $this->_findCustomer();
        }
        
        public function update(){
            $this->Customer = $this->_findCustomer();
            $this->Customer->attributes = $this->params['customer'];
            $this->Customer.save() ?
              $this->redirectTo(array('action' => 'show')) :
              $this->render(array('action' => 'edit'));
        }
        
        private function _findCustomer(){
            $Customer = new Cutomer();
            return $Customer->find($this->params['id']);
        }
    }

  {Learn more}[link:AkActionController]


* PHP and Sintags for templates

    {loop Posts}
      Title: {Posts.title}
    {end}

    All post titles: <?php join(', ', Ak::collect($Posts, 'title')); ?>

    {!Person.is_client}
      Not for clients to see...
    {end}
  
  {Learn more}[link:AkActionView]


* Builder-based templates using Sintags (great for XML content, like RSS)

    <%= xml_instruct 'xml', :version => "1.0" %> 
    <%= xml_rss_open :version => "2.0" %>
      <%= xml_channel_open %>
        <%= xml_title "Articles" %>
        <%= xml_description "Lots of articles" %>
        <%= xml_link formatted_articles_url(:rss) %>
        
        {loop articles}
          <%= xml_item_open %>
            <%= xml_title article.name %>
            <%= xml_description article.content %>
            <%= xml_pubDate article.created_at.to_s(:rfc822) %>
            <%= xml_link formatted_article_url(article, :rss) %>
            <%= xml_guid formatted_article_url(article, :rss) %>
          <%= xml_item_close %>
        {end}
      <%= xml_channel_close %>
    <% xml_close %>

  {Learn more}[link:AkXmlHelper]


* Filters for pre and post processing of the response (as methods, and classes)

    class WeblogController extends AkActionController{
    
        public function __construct(){
            $this->beforeFilter(array('authenticate','cache','audit'));
            $this->afterFilter(array('compress_body' => function ($Controller){
                $Controller->Response->body = Ak::compress(
                    $Controller->Response->body);
            }));  // PHP5.3
            $this->afterFilter(new LocalizeFilter());
        }

        public funciton index(){
            # Before this action is run, the user will be authenticated, the 
            # cache will be examined to see if a valid copy of the results 
            #already exists, and the action will be logged for auditing.
        
            # After this action has run, the output will first be localized then 
            # compressed to minimize bandwidth usage
        }
        
        private function authenticate(){
          # Implement the filter with full access to both request and response
        }
    }
  
  {Learn more}[link:AkControllerFilter]
  

* Helpers for forms, dates, action links, and text

    <%= text_field "post", "title", "size" => 30 %>
    <?php= $date_helper->html_date_select(Ak::getDate()); ?>
    <%= link_to "New post", :controller => "post", :action => "create" %>
    <%= truncate(post.title, :length => 25) %>
 
  {Learn more}[link:AkBaseHelper]


* Layout sharing for template reuse (think simple version of Struts 
  Tiles[http://jakarta.apache.org/struts/userGuide/dev_tiles.html])

    class WeblogController extends AkActionController {
        public $layout = "weblog_layout";
        
        public function hello_world(){
        }
    }

    Layout file (called weblog_layout):
      <html><body>{content_for_layout}</body></html>
    
    Template for hello_world action:
      <h1>Hello world</h1>
    
    Result of running hello_world action:
      <html><body><h1>Hello world</h1></body></html>

  {Learn more}[link:AkActionView,AkSintags]


* Routing makes pretty urls incredibly easy

    $Map->connect('users/:name/:site/:controller/:action');

    Accessing /users/james/personal/post/write calls PostController#write with
    { "name" => "james", "site" => "personal" } in $Controller->params
    
    From that URL, you can rewrite the redirect in a number of ways:
    
    $Controller->redirectTo(array('action' => 'edit')) =>
      /users/james/personal/post/edit

    $Controller->redirectTo(array('name' => 'pete', 'site' => 'private')) =>
      /users/pete/private/post/write

  {Learn more}[link:AkRouter]


* Javascript and Ajax integration

    <%= link_to_function "Greeting", "alert('Hello world!')" %>
    <%= link_to_remote "Delete this post", :update => "posts", 
                   :url => { :action => "destroy", :id => post.id } %>
  
  {Learn more}[link:AkJavaScriptHelper]


* Easy testing of both controller and rendered template through AkActionControllerTest

    class LoginController_TestCase extends AkActionControllerTest{
        public function test_failing_authenticate(){
            $this->post('authenticate', array('user'=>"nop", 'password'=>''));
            $this->assertText('Invalid username or password');
        }
    }

  {Learn more}[link:AkActionControllerTest]


* Integrated logging

    Processing WeblogController#index (for 127.0.0.1)
    Parameters: {"action"=>"index", "controller"=>"weblog"}
    Rendering weblog/index (200 OK)
    Completed in 0.029281 (34 reqs/sec)



