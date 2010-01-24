<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* == Overview
* 
* 'AkResources' are a way of defining RESTful resources.
* A RESTful resource, in basic terms, is something that can be pointed at and it
* will respond with a representation of the data requested. In real terms this 
* could mean a user with a browser requests an HTML page, or that a desktop
* application requests XML data.
* 
* RESTful design is based on the assumption that there are four generic verbs 
* that a user of an application can request from a resource (the noun).
* 
* Resources can be requested using four basic HTTP verbs (GET, POST, PUT, 
* DELETE), the method used denotes the type of action that should take place.
*
* === The Different Methods and their Usage
* 
* * GET    - Requests for a resource, no saving or editing occur in GET requests
* * POST   - Creation of resources
* * PUT    - Editing of attributes on a resource
* * DELETE - Deletion of a resource
*
* === Examples
* 
* A GET request on the Posts resource is asking for all Posts
* 
*     GET /posts
* 
* A GET request on a single Post resource is asking for that particular Post
* 
*     GET /posts/1
* 
* A POST request on the Posts resource is asking for a Post to be created with 
* the supplied details
*
*     POST /posts # with=>array('post'=>array('title'=>'My Whizzy New Post', 
*                 #         'body'=>'I've got a brand new combine harvester'));
* 
* A PUT request on a single Post resource is asking for a Post to be updated
* 
*     PUT /posts # with=>array('id'=>1, 
*                #             'post'=>array('title'=>'Changed Whizzy Title'));
*
* A DELETE request on a single Post resource is asking for it to be deleted
* 
*     DELETE /posts # with=>array('id'=>1);
* 
* By using the REST convention, users of our application can assume certain
* things about how the data is requested and how it is returned.
* Akelos simplifies the routing part of RESTful design by supplying you 
* with methods to create them in your routes.php file.
* 
* Read more about REST at http://en.wikipedia.org/wiki/REST
*/
class AkResource{

    public
    $default_actions = array('index', 'create', 'add', 'edit', 'show', 'update', 'destroy'),
    $collection_methods,
    $member_methods,
    $add_methods,
    $path_prefix,
    $name_prefix,
    $path_segment,
    $plural,
    $singular,
    $options;

    public function __construct($entities, $options){
        $this->plural       = $entities;
        $this->singular     = isset($options['singular']) ? $options['singular'] : AkInflector::singularize($this->plural);
        $this->path_segment = isset($options['singular']) || isset($options['as']) ? Ak::deleteAndGetValue($options, 'as') : $this->plural;

        $this->options = $options;

        $this->arrangeActions();
        $this->addDefaultActions();
        $this->setAllowedActions();
        $this->setPrefixes();
    }

    private $Router;
    public function setMap(AkRouter &$Router){
        $this->Router = $Router;
    }

    public function &getMap($options){
        $this->Router->options = array_merge($this->options, $options);
        return $this->Router;
    }

    public function getController(){
        if(isset($this->controller)){
            return $this->controller;
        }elseif (isset($this->options['module']) && isset($this->options['controller'])){
            return $this->options['module'].$this->options['controller'];
        }
        return $this->plural;
    }

    public function getRequirements($with_id = false){
        $this->requirements   = isset($this->requirements) ? $this->requirements : (isset($this->options['requirements']) ? $this->options['requirements'] : array());
        $id_requirement = (isset($this->requirements['id']) ? ($with_id ? Ak::deleteAndGetValue($this->requirements, 'id') : Ak::pick($this->requirements, 'id') ) : '/[^\/.?]+/');
        if($with_id){
            $this->requirements['id'] = $id_requirement;
        }
        return $this->requirements;
    }

    public function getConditions(){
        return $this->conditions = (isset($this->conditions) ? $this->conditions : (isset($this->options['conditions']) ? $this->options['conditions'] : array()));
    }

    public function getPath(){
        return $this->path = isset($this->path) ? $this->path : $this->path_prefix.'/'.$this->path_segment;
    }

    public function getAddPath(){
        $add_action   = !empty($this->options['path_names']) ? $this->options['path_names']['add'] : $this->getResourcePathNameFor('add');

        return $this->add_path = (isset($this->add_path) ? $this->add_path : $this->getPath().'/'.$add_action);
    }

    static function getResourcePathNameFor($name, $default = null){
        $result = Ak::first(Ak::pick($name, AkConfig::getOption('resources_path_names', array('add' => 'add', 'edit' => 'edit'))));
        return empty($result) ? $default : $result;
    }

    public function getShallowPathPrefix(){
        if(!empty($this->options['shallow']) && $this->options['shallow'] === true){
            return $this->shallow_path_prefix = '';
        }
        return $this->shallow_path_prefix = isset($this->shallow_path_prefix) ? $this->shallow_path_prefix : (isset($this->options['shallow']) && $this->options['shallow'][strlen($this->options['shallow'])-1] == '/' ? rtrim($this->options['shallow'], '/') : $this->path_prefix);
    }

    public function getMemberPath(){
        return $this->member_path = isset($this->member_path) ? $this->member_path : $this->getShallowPathPrefix().'/'.$this->path_segment.'/:id';
    }

    public function getNestingPathPrefix(){
        return isset($this->nesting_path_prefix) ? $this->nesting_path_prefix : $this->getShallowPathPrefix().'/'.$this->path_segment.'/:'.$this->singular.'_id';
    }

    public function getShallowNamePrefix(){
        if(!empty($this->options['shallow']) && $this->options['shallow'] === true){
            return $this->shallow_name_prefix = '';
        }
        return isset($this->shallow_name_prefix) ? $this->shallow_name_prefix : (isset($this->options['shallow']) && strstr($this->options['shallow'], '/') ? str_replace('/','_',$this->options['module']) : $this->getNamePrefix());
    }

    public function getNestingNamePrefix(){
        return $this->getShallowNamePrefix().$this->singular.'_';
    }

    public function getNamePrefix(){
        return $this->name_prefix;
    }

    public function getActionSeparator(){
        return isset($this->action_separator) ? $this->action_separator : AkConfig::getOption('resource_action_separator', '/');
    }

    public function isUncountable(){
        return $this->singular == $this->plural;
    }

    public function hasAction($action){
        return !in_array($action, $this->default_actions) || $this->isActionAllowed($action);
    }

    public function getCollectionMethods(){
        return (array)$this->collection_methods;
    }

    public function getMemberMethods(){
        return (array)$this->member_methods;
    }

    public function getAddMethods(){
        return (array)$this->add_methods;
    }

    protected function arrangeActions(){
        $this->collection_methods = $this->arrangeActionsByMethods(Ak::deleteAndGetValue($this->options, 'collection'));
        $this->member_methods     = $this->arrangeActionsByMethods(Ak::deleteAndGetValue($this->options, 'member'));
        $this->add_methods        = $this->arrangeActionsByMethods(Ak::deleteAndGetValue($this->options, 'add'));
    }

    protected function addDefaultActions(){
        $this->addDefaultAction($this->member_methods,  'get', 'edit');
        $this->addDefaultAction($this->add_methods,     'get', 'add');
    }

    protected function setAllowedActions(){
        list($only, $except) = Ak::valuesAt($this->options, array('only', 'except'));

        $this->allowed_actions = isset($this->allowed_actions) ? $this->allowed_actions : array();

        if($only == 'all' || $except == 'none'){
            $only = null;
            $except = array();
        }elseif($only == 'none' || $except == 'all'){
            $only = array();
            $except = null;
        }

        if(!is_null($only)){
            $this->allowed_actions['only'] = Ak::toArray($only);
        }elseif(!is_null($except)){
            $this->allowed_actions['except'] = Ak::toArray($except);
        }
    }

    protected function isActionAllowed($action){
        list($only, $except) = Ak::valuesAt($this->allowed_actions, array('only', 'except'));

        return ((is_null($only) || in_array($action, $only)) && (is_null($except) || !in_array($action, $except)));
    }

    protected function setPrefixes(){
        $this->path_prefix = Ak::deleteAndGetValue($this->options, 'path_prefix');
        $this->name_prefix = Ak::deleteAndGetValue($this->options, 'name_prefix');
    }

    protected function arrangeActionsByMethods($actions){
        $flipped_array = array();
        foreach ((array)$actions as $action => $method){
            if(is_array($method)){
                foreach ($method as $m){
                    $flipped_array[$m][] = $action;
                }
            }else{
                $flipped_array[$method][] = $action;
            }
        }
        return $flipped_array;
    }

    protected function addDefaultAction(&$collection, $method, $action){
        if(!isset($collection[$method])){
            $collection[$method][] = $action;
        }else{
            array_unshift($collection[$method], $action);
        }
    }

}

class AkSingletonResource extends AkResource {
    public function __construct($entity, $options){
        $this->singular = $this->plural = $entity;
        $this->options['controller'] = $this->controller = isset($options['controller']) ? $options['controller'] : AkInflector::pluralize($this->singular);
        parent::__construct($entity, $options);
    }

    public function getShallowPathPrefix(){
        return $this->getPath();
    }

    public function getShallowNamePrefix(){
        return $this->getNamePrefix();
    }

    public function getMemberPath(){
        return $this->getPath();
    }

    public function getNestingPathPrefix(){
        return $this->getPath();
    }
}





class AkResources
{
    protected $_inheritable_options = array('module', 'shallow');
    private $Router;

    public function __construct(AkRouter &$Router){
        $this->Router = $Router;
    }

    /**
    * Creates named routes for implementing verb-oriented controllers
    * for a collection resource.
    *
    * For example:
    *
    *   $Map->resources('messages');
    *
    * will map the following actions in the corresponding controller:
    *
    *      class MessagesController extends ActionController {
    *        // GET messages_url
    *        public function index() {
    *          // return all messages
    *        }
    *      
    *        // GET add_message_url
    *        public function add() {
    *          // return an HTML form for describing a new message
    *        }
    *      
    *        // POST messages_url
    *        public function create() {
    *          // create a add message
    *        }
    *      
    *        // GET message_url(array('id'=>1))
    *        public function show() {
    *          // find and return a specific message
    *        }
    *      
    *        // GET edit_message_url(array('id'=>1))
    *        public function edit() {
    *          // return an HTML form for editing a specific message
    *        }
    *      
    *        // PUT message_url(array('id'=>1))
    *        public function update() {
    *          // find and update a specific message
    *        }
    *      
    *        // DELETE message_url(array('id'=>1))
    *        public function destroy() {
    *          // delete a specific message
    *        }
    *      }
    * 
    * Along with the routes themselves, +resources+ generates named routes for use 
    * in controllers and views. <tt>$Map->resources('messages')</tt> produces the 
    * following named routes and helpers:
    *
    *   Named Route   Helpers
    *   ============  =====================================================
    *   messages      messages_url, array_for_messages_url,
    *                 messages_path, array_for_messages_path
    *
    *   message       message_url(id), array_for_message_url(id),
    *                 message_path(id), array_for_message_path(id)
    *
    *   add_message   add_message_url, array_for_add_message_url,
    *                 add_message_path, array_for_add_message_path
    *
    *   edit_message  edit_message_url(id), array_for_edit_message_url(id),
    *                 edit_message_path(id), array_for_edit_message_path(id)
    *
    * You can use these helpers instead of +url_for+ or methods that take +url_for+ 
    * parameters. For example:
    *
    *     $this->redirectTo(array('controller'=>'messages','action'=>'index'));
    * 
    * and
    * 
    *     <%= link_to 'edit this message', 'controller'=>'messages', 
    *                               'action'=>'edit', 'id'=>$this->message->id %>
    *
    * now become:
    *
    *     $this->redirectTo(messages_url());
    * 
    * and
    *
    *     <%= link_to 'edit this message', edit_message_url(Message) %> 
    *     // calls $Message->id automatically
    *
    * Since web browsers don't support the PUT and DELETE verbs, you will need to 
    * add a parameter '_method' to your form tags. The form helpers make this a
    * little easier. For an update form with a <tt>Message</tt> object:
    *
    *     <%= form_tag message_path(Message), 'method'=>'put' %>
    *
    * or
    *
    *     <?php $f = $form_helper->form_for('message', $Message, array(
    *                                       'url'=>message_path($Message), 
    *                                       'html'=>array('method'=>'put')); >
    *
    * or
    *
    *     <?php $f = $form_helper->form_for($Message); ?>
    *
    * which takes into account whether <tt>Message</tt> is a new record or not and 
    * generates the path and method accordingly.
    *
    * The +resources+ method accepts the following options to customize
    * the resulting routes:
    * 
    * * <tt>'collection'</tt> - Add named routes for other actions that operate on 
    *   the collection.
    *   Takes an array of <tt>'action'=>'method'</tt>, where method is 
    *   <tt>'get'</tt>/<tt>'post'</tt>/<tt>'put'</tt>/<tt>'delete'</tt>,
    *   an array of any of the previous, or <tt>'any'</tt> if the method does 
    *   not matter.
    *   These routes map to a URL like /messages/rss, with a route 
    *   of +rss_messages_url+.
    * * <tt>'member'</tt> - Same as <tt>'collection'</tt>, but for actions that 
    *   operate on a specific member.
    * * <tt>'add'</tt> - Same as <tt>'collection'</tt>, but for actions that operate
    *   on the new resource action.
    * * <tt>'controller'</tt> - Specify the controller name for the routes.
    * * <tt>'singular'</tt> - Specify the singular name used in the member routes.
    * * <tt>'requirements'</tt> - Set custom routing parameter requirements; this is
    *   an array of either regular expressions (which must match for the route 
    *   to match) or extra parameters. For example:
    *
    *       $Map->resource('profile', array(
    *                                  'path_prefix'=>'name', 
    *                                   'requirements'=>
    *                                           array('name'=>'/[a-zA-Z]+/', 
    *                                                   'extra'=>'value'
    *                                           )));
    *
    *    will only match if the first part is alphabetic, and will pass the 
    *    parameter 'extra' to the controller.
    * * <tt>'conditions'</tt> - Specify custom routing recognition conditions.  
    *   Resources sets the <tt>'method'</tt> value for the method-specific routes.
    * * <tt>'as'</tt> - Specify a different resource name to use in the URL path. 
    *   For example:
    *   
    *       // products_path == '/productos'
    *       $Product = $Map->resources('products', array('as'=>'productos'));
    *       // product_reviews_path(product) == '/productos/1234/comentarios'
    *       $Product->resources('product_reviews', array('as'=>'comentarios'));
    *
    * * <tt>'has_one'</tt> - Specify nested resources, this is a shorthand for 
    *   mapping singleton resources beneath the current.
    * * <tt>'has_many'</tt> - Same has <tt>'has_one'</tt>, but for plural resources.
    *
    *   You may directly specify the routing association with +has_one+ 
    *   and +has_many+ like:
    *
    *       $Map->resources('notes', array(
    *                                   'has_one'=>'author', 
    *                                   'has_many'=>array(
    *                                       'comments', 'attachments'
    *                                   )));
    *
    *   This is the same as:
    *
    *       $Notes = $Map->resources('notes');
    *       $Notes->resource('author');
    *       $Notes->resources('comments');
    *       $Notes->resources('attachments');
    *
    * * <tt>'path_names'</tt> - Specify different names for the 'add' and 'edit' 
    *   actions. For example:
    *   
    *       // add_products_path == '/productos/nuevo'
    *       $Map->resources('products', array(
    *                                       'as'=>'productos',
    *                                       'path_names'=> array(
    *                                               'add'=>'nuevo', 
    *                                               'edit'=>'editar'
    *                                                )));
    *
    *   You can also set default action names from an environment, like this:
    * 
    *       AkConfig::setOption('resources_path_names', array(
    *                                   'add'=>'nuevo', 
    *                                   'edit'=>'editar'));
    *
    * * <tt>'path_prefix'</tt> - Set a prefix to the routes with required route 
    *   variables.
    *
    *   Weblog comments usually belong to a post, so you might use +resources+ like:
    *
    *       $Map->resources('articles');
    *       $Map->resources('comments',array('path_prefix'=>'/articles/:article_id');
    *
    *   You can nest +resources+ calls to set this automatically:
    *
    *       $Article = $Map->resources('articles');
    *           $Article->resources('comments');
    *
    *   The comment resources work the same, but must now include a value for 
    *   <tt>'article_id'</tt>.
    *
    *       article_comments_url($Article);
    *       article_comment_url($Article, $Comment);
    *
    *       article_comments_url('article_id'=>$Article->id);
    *       article_comment_url('article_id'=>$Article, 'id'=>$Comment);
    *
    *   If you don't want to load all objects from the database you might want to 
    *   use the <tt>article_id</tt> directly:
    *
    *       articles_comments_url($Comment->article_id, $Comment);
    *
    * * <tt>'name_prefix'</tt> - Define a prefix for all generated routes, usually 
    *   ending in an underscore.
    *   Use this if you have named routes that may clash.
    *
    *       $Map->resources('tags', array('path_prefix'=>'/books/:book_id', 
    *                                     'name_prefix'=>'book_'));
    *       $Map->resources('tags', array('path_prefix'=>'/toys/:toy_id',
    *                                     'name_prefix'=>'toy_'));
    *
    *   You may also use <tt>'name_prefix'</tt> to override the generic named routes 
    *   in a nested resource:
    *
    *       $Map->resources('articles');
    *           $Article->resources('comments', array('name_prefix'=>null));
    *
    *   This will yield named resources like so:
    *
    *   comments_url($this->article)
    *   comment_url($this->article, $this->comment)
    *
    * * <tt>'shallow'</tt> - If true, paths for nested resources which reference a 
    *   specific member (ie. those with an 'id' parameter) will not use the parent 
    *   path prefix or name prefix.
    *
    *   The <tt>'shallow'</tt> option is inherited by any nested resource(s).
    *
    *   For example, 'users', 'posts' and 'comments' all use shallow paths with the 
    *   following nested resources:
    *
    *       $User = $Map->resources('users', array('shallow'=>true));
    *           $Post = $User->resources('posts');
    *               $Post->resources('comments');
    * 
    *   --> GET /users/1/posts (maps to the PostsController#index action as usual)
    *       also adds the usual named route called 'user_posts'
    *   --> GET /posts/2 (maps to the PostsController#show action as if it were not 
    *       nested)
    *       also adds the named route called 'post'
    *   --> GET /posts/2/comments (maps to the CommentsController#index action)
    *       also adds the named route called 'post_comments'
    *   --> GET /comments/2 (maps to the CommentsController#show action as if it 
    *       were not nested)
    *       also adds the named route called 'comment'
    *
    *   You may also use <tt>'shallow'</tt> in combination with the +has_one+ and 
    *   +has_many+ shorthand notations like:
    *
    *       $Map->resources('users', array('has_many'=> array('posts'=>'comments'), 
    *                                       'shallow'=>true));
    *
    * * <tt>'only'</tt> and <tt>'except'</tt> - Specify which of the seven default 
    *   actions should be routed to.
    *
    *   <tt>'only'</tt> and <tt>'except'</tt> may be set to <tt>'all'</tt>, 
    *   <tt>'none'</tt>, an action name or a list of action names. By default, 
    *   routes are generated for all seven actions.
    *
    *   For example:
    *
    *       $Posts = $Map->resources('posts',array('only'=>array('index', 'show')));
    *           $Posts->resources('comments','except'=>array('update', 'destroy'));
    * 
    *   --> GET /posts (maps to the PostsController#index action)
    *   --> POST /posts (fails)
    *   --> GET /posts/1 (maps to the PostsController#show action)
    *   --> DELETE /posts/1 (fails)
    *   --> POST /posts/1/comments (maps to the CommentsController#create action)
    *   --> PUT /posts/1/comments/1 (fails)
    *
    *   If <tt>$Map->resources()</tt> is called with multiple resources, they all
    *   get the same options applied.
    *
    *   Examples:
    *
    *       $Map->resources('messages', array('path_prefix'=>'/thread/:thread_id'));
    * 
    *   --> GET /thread/7/messages/1
    *
    *       $Map->resources('messages', array('collection'=>array('rss'=>'get'));
    * 
    *   --> GET /messages/rss (maps to the #rss action)
    *   also adds a named route called 'rss_messages'
    *
    *       $Map->resources('messages',array('member'=>array('mark'=>'post')));
    *   
    *   --> POST /messages/1/mark (maps to the #mark action)
    *   also adds a named route called 'mark_message'
    *
    *       $Map->resources('messages',array('add'=>array('preview'=>'post')));
    * 
    *   --> POST /messages/add/preview (maps to the #preview action)
    *   also adds a named route called 'preview_add_message'
    *
    *       $Map->resources('messages', array('add'=>array('add'=>'any', 
    *                                                      'preview'=>'post')));
    * 
    *   --> POST /messages/add/preview (maps to the #preview action)
    *   also adds a named route called 'preview_add_message'
    *   --> /messages/add can be invoked via any request method
    *
    *   $Map->resources('messages', array('controller'=>'categories',
    *         'path_prefix'=>'/category/:category_id'',
    *         'name_prefix'=>'category_'));
    * 
    *   --> GET /categories/7/messages/1
    *   has named route 'category_message'
    *
    * The +resources+ method sets HTTP method restrictions on the routes it 
    * generates. For example, making an HTTP POST on <tt>add_message_url</tt> will 
    * raise a RoutingError exception.
    */
    public function resources($entities, $options = array()){
        $entities = Ak::toArray($entities);
        if(!empty($this->Router->options)){
            $options = array_merge(Ak::delete($this->Router->options, 'has_one'), $options);
        }
        foreach ($entities as $entity){
            $Map = $this->mapResource($entity, $options);
        }
        return $Map;
    }


    /**
    * Creates named routes for implementing verb-oriented controllers for a 
    * singleton resource. A singleton resource is global to its current context.  
    * For unnested singleton resources, the resource is global to the current user 
    * visiting the application, such as a user's <tt>/account</tt> profile.  
    * For nested singleton resources, the resource is global to its parent resource,
    * such as a <tt>projects</tt> resource that <tt>has_one 'project_manager'</tt>.
    * The <tt>project_manager</tt> should be mapped as a singleton resource 
    * under <tt>projects</tt>:
    *
    *       $Project = $Map->resources('projects');
    *       $Project->resource('project_manager');
    *
    * See +resources+ for general conventions.  These are the main differences:
    * 
    * * A singular name is given to <tt>$Map->resource</tt>.  The default controller
    *   name is still taken from the plural name.
    * * To specify a custom plural name, use the <tt>'plural'</tt> option.  There is
    *   no <tt>'singular'</tt> option.
    * * No default index route is created for the singleton resource controller.
    * * When nesting singleton resources, only the singular name is used as the path
    *   prefix (example: 'account/messages/1')
    *
    * For example:
    *
    *     $Map->resource('account');
    *
    * maps these actions in the Accounts controller:
    *
    *     class AccountsController extends ActionController{
    *       // GET add_account_url
    *       public function add() {
    *           // return an HTML form for describing the new account
    *       }
    *
    *       // POST account_url
    *       public function create() {
    *           // create an account
    *       }
    *
    *       // GET account_url
    *       public function show() {
    *           // find and return the account
    *       }
    *
    *       // GET edit_account_url
    *       public function edit() {
    *           // return an HTML form for editing the account
    *       }
    *
    *       // PUT account_url
    *       public function update() {
    *           // find and update the account
    *       }
    *
    *       // DELETE account_url
    *       public function destroy() {
    *           // delete the account
    *       }
    *   }
    *
    * Along with the routes themselves, +resource+ generates named routes for
    * use in controllers and views. <tt>$Map->resource('account')</tt> produces
    * these named routes and helpers:
    *
    *   Named Route   Helpers
    *   ============  =============================================
    *   account       account_url, array_for_account_url,
    *                 account_path, array_for_account_path
    *
    *   add_account   add_account_url, array_for_add_account_url,
    *                 add_account_path, array_for_add_account_path
    *
    *   edit_account  edit_account_url, array_for_edit_account_url,
    *                 edit_account_path, array_for_edit_account_path
    */
    public function resource($entities, $options = array()){
        $entities = Ak::toArray($entities);
        if(!empty($this->Router->options)){
            $options = array_merge(Ak::delete($this->Router->options, array('has_one')), $options);
        }
        foreach ($entities as $entity){
            $Map = $this->mapSingletonResource($entity, $options);
        }
        return $Map;
    }

    private function mapResource($entities, $options = array()){

        $Resource = new AkResource($entities, $options);
        $Resource->setMap($this->Router);
        $Map = $Resource->getMap(array('controller'=>$Resource->getController()));

        $this->mapCollectionActions($Map, $Resource);
        $this->mapDefaultCollectionActions($Map, $Resource);
        $this->mapAddActions($Map, $Resource);
        $this->mapMemberActions($Map, $Resource);

        $this->mapAssociations($Resource, $options);

        $Map->options = Ak::pick($this->_inheritable_options, $Map->options);
        $Map->options = array_merge(array(
        'path_prefix'=>$Resource->getNestingPathPrefix(),
        'name_prefix'=>$Resource->getNestingNamePrefix()),
        $Map->options);

        return $Map;
    }

    private function mapSingletonResource($entities, $options = array()){

        $Resource = new AkSingletonResource($entities, $options);
        $Resource->setMap($this->Router);

        $Map = $Resource->getMap(array('controller'=>$Resource->getController()));

        $this->mapCollectionActions($Map, $Resource);
        $this->mapAddActions($Map, $Resource);
        $this->mapMemberActions($Map, $Resource);

        $this->mapDefaultSingletonActions($Map, $Resource);
        $this->mapAssociations($Resource, $options);


        $Map->options = Ak::pick($this->_inheritable_options, $Map->options);
        $Map->options = array_merge(array(
        'path_prefix'=>$Resource->getNestingPathPrefix(),
        'name_prefix'=>$Resource->getNestingNamePrefix()),
        $Map->options);
        return $Map;
    }

    private function mapAssociations($Resource, $options){
        if(isset($options['has_many'])){
            $this->mapHasManyAssociations($Resource, Ak::deleteAndGetValue($options, 'has_many'), $options);
        }
        $path_prefix = Ak::deleteAndGetValue($options, 'path_prefix').$Resource->getNestingPathPrefix();
        $name_prefix = Ak::deleteAndGetValue($options, 'name_prefix').$Resource->getNestingNamePrefix();

        if(isset($options['has_one'])){
            foreach (Ak::toArray($options['has_one']) as $association){
                $options = Ak::pick($this->_inheritable_options, $Resource->options);
                $options = array_merge(array(
                'path_prefix'=>$path_prefix,
                'name_prefix'=>$name_prefix),
                $options);
                $this->resource($association, $options);
            }
        }
    }

    private function mapHasManyAssociations($Resource, $associations, $options) {
        if(is_array($associations)){
            foreach ($associations as $k => $v){
                if(is_int($k)){
                    $this->mapHasManyAssociations($Resource, $v, $options);
                }else{
                    $this->mapHasManyAssociations($Resource, $k, array_merge($options, array('has_many'=>$v)));
                }
            }
        }else{
            $options = array_merge(array(
            'path_prefix'=>$Resource->getNestingPathPrefix(),
            'name_prefix'=>$Resource->getNestingNamePrefix(),
            'has_many' => isset($options['has_many']) ? $options['has_many'] : null),
            Ak::pick($this->_inheritable_options, $options));
            $this->resources($associations, $options);
        }
    }

    private function mapCollectionActions($Map, $Resource) {
        foreach ($Resource->getCollectionMethods() as $k => $actions){
            foreach ($actions as $action){
                foreach (Ak::toArray($k) as $method){
                    $route_action = isset($Resource->options['path_names'][$action]) ? $Resource->options['path_names'][$action] : $action;
                    $this->mapResourceRoutes($Map, $Resource, $action, $Resource->getPath().$Resource->getActionSeparator().$route_action, $action.'_'.$Resource->getNamePrefix().$Resource->plural, $method);
                }
            }
        }
    }

    private function mapDefaultCollectionActions($Map, $Resource){
        $index_route_name = $Resource->getNamePrefix().$Resource->plural;

        if ($Resource->isUncountable()){
            $index_route_name .= '_index';
        }
        $this->mapResourceRoutes($Map, $Resource, 'index',  $Resource->getPath(), $index_route_name);
        $this->mapResourceRoutes($Map, $Resource, 'create', $Resource->getPath(), $index_route_name);
    }

    private function mapDefaultSingletonActions($Map, $Resource){
        $this->mapResourceRoutes($Map, $Resource, 'create', $Resource->getPath(), $Resource->getShallowNamePrefix().$Resource->singular);
    }

    private function mapAddActions($Map, $Resource){
        foreach ($Resource->getAddMethods() as $method => $actions){
            foreach ($actions as $action){
                $route_path = $Resource->getAddPath();
                $route_name = 'add_'.$Resource->getNamePrefix().$Resource->singular;
                if($action != 'add'){
                    $route_path = $route_path.$Resource->getActionSeparator().$action;
                    $route_name = $action.'_'.$route_name;
                }
                $this->mapResourceRoutes($Map, $Resource, $action, $route_path, $route_name, $method);
            }
        }
    }

    private function mapMemberActions($Map, $Resource) {
        foreach ($Resource->getMemberMethods() as $k => $actions){
            foreach ($actions as $action){
                foreach (Ak::toArray($k) as $method){
                    if(!empty($Resource->options['path_names'][$action])){
                        $action_path = $Resource->options['path_names'][$action];
                    }else{
                        $action_path = $Resource->getResourcePathNameFor($action, $action);
                    }
                    $this->mapResourceRoutes($Map, $Resource, $action, $Resource->getMemberPath().$Resource->getActionSeparator().$action_path, $action.'_'.$Resource->getShallowNamePrefix().$Resource->singular, $method, array('force_id'=>true));
                }
            }

            $route_path = $Resource->getShallowNamePrefix().$Resource->singular;
            $this->mapResourceRoutes($Map, $Resource, 'show', $Resource->getMemberPath(), $route_path);
            $this->mapResourceRoutes($Map, $Resource, 'update', $Resource->getMemberPath(), $route_path);
            $this->mapResourceRoutes($Map, $Resource, 'destroy', $Resource->getMemberPath(), $route_path);

        }
    }

    private function mapResourceRoutes(AkRouter $Map, $Resource, $action, $route_path, $route_name = null, $method = null, $resource_options = array()){
        if($Resource->hasAction($action)){
            $action_options = $this->actionOptionsFor($action, $Resource, $method, $resource_options);

            $formatted_route_path = $route_path.'.:format';

            $requirements = Ak::deleteAndGetValue($action_options, array('requirements'));

            $conditions = Ak::deleteAndGetValue($action_options, array('conditions'));

            if($route_name && !in_array($route_name, $Map->getNamedRouteNames())){
                $Map->connectNamed($route_name, $formatted_route_path, $action_options, (array)$requirements, (array)$conditions);
            }else{
                $Map->connectNamed(null, $formatted_route_path, $action_options, (array)$requirements, (array)$conditions);
            }
        }
    }

    private function addConditionsFor($conditions, $method){
        $method = strtolower($method);
        $options = array('conditions' => $conditions);
        if($method != 'any'){
            if(!in_array($method, array('get', 'post', 'put', 'delete'))){
                throw new RouteException('Illegal HTTP verb '.$method);
            }
            $options['conditions']['method'] = $method;
        }
        return $options;
    }

    private function actionOptionsFor($action, $Resource, $method = null, $resource_options = array()) {

        $default_options = array('action'=>(string)$action);
        $require_id = !($Resource instanceof AkSingletonResource);
        $force_id = !empty($resource_options['force_id']) && $require_id;

        $default_options['controller'] = isset($default_options['controller']) ? $default_options['controller'] : $Resource->getController();

        switch ($default_options['action']) {
            case 'index':
            case 'add':
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(), (empty($method) ? 'get' : $method)), $default_options, array('requirements' => $Resource->getRequirements()));
                break;
            case 'create':
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(), (empty($method) ? 'post' : $method)), $default_options, array('requirements' => $Resource->getRequirements()));
                break;
            case 'show':
            case 'edit':
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(), (empty($method) ? 'get' : $method)), $default_options, array('requirements' => $Resource->getRequirements($require_id)));
                break;
            case 'update':
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(), (empty($method) ? 'put' : $method)), $default_options, array('requirements' => $Resource->getRequirements($require_id)));
                break;
            case 'destroy':
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(), (empty($method) ? 'delete' : $method)), $default_options, array('requirements' => $Resource->getRequirements($require_id)));
                break;
            default:
                $default_options = array_merge($this->addConditionsFor($Resource->getConditions(),$method), $default_options, array('requirements' => $Resource->getRequirements($require_id)));
        }

        return $default_options;
    }
}

