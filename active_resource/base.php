<?php


class AkResourceConnectionError extends Exception{
    public $response;
    public $message;
    public function __construct($response, $message = null){
        $this->response = $response;
        $this->message  = $message;
    }
    public function __toString(){
        return 'Failed with '.$this->response->code.' '.@$this->response->message;
    }
}


/**
 * Raised when a timeout error occurs.
 */
class AkResourceTimeoutError extends AkResourceConnectionError {
}

/**
 * 3xx Redirection
 */
class AkResourceRedirection extends AkResourceConnectionError {
    public function __toString(){
        return parent::__toString().(isset($this->response->Location) ? ' => '.$this->response->Location : '');
    }
}

/**
 * 4xx Client Error
 */
class AkResourceClientError extends AkResourceConnectionError {}

/**
 * 400 Bad Request
 */
class AkResourceBadRequest extends AkResourceClientError {}

/**
 * 401 Unauthorized
 */
class AkResourceUnauthorizedAccess extends AkResourceClientError {}

/**
 * 403 Forbidden
 */
class AkResourceForbiddenAccess extends AkResourceClientError {}

/**
 * 404 Not Found
 */
class AkResourceNotFound extends AkResourceClientError {}

/**
 * 409 Conflict
 */
class AkResourceConflict extends AkResourceClientError {}

/**
 * 5xx Server Error
 */
class AkResourceServerError extends AkResourceConnectionError {}

/**
 * 405 Method Not Allowed
 */
class AkResourceMethodNotAllowed extends AkResourceClientError {
    public function allowedMethods(){
        return array_map('strtolower', explode(',',$this->response['Allow']));
    }
}

/**
* AkActiveResource is the main class for mapping RESTful resources as models in a Akelos application.
* 
* For an outline of what Active Resource is capable of, see active_resource/README.markdown
* 
* == Automated mapping
* 
* Active Resource objects represent your RESTful resources as manipulatable Akelos objects.  To map resources
* to PHP objects, Active Resource only needs a class name that corresponds to the resource name (e.g., the class
* Person maps to the resources people, very similarly to Active Record) and a +site+ value, which holds the
* URI of the resources.
* 
*   class Person extends AkActiveResource {
*     public $site = "http://api.example.com/";
*   }
* 
* Now the Person class is mapped to RESTful resources located at <tt>http://api.example.com/people/</tt>, and
* you can now use Active Resource's lifecycle methods to manipulate resources. In the case where you already have
* an existing model with the same name as the desired RESTful resource you can set the +element_name+ value.
* 
*   class PersonResource extends AkActiveResource {
*     protected $site = "http://api.example.com/";
*     protected $element_name = "person";
*   }
* 
* 
* == Lifecycle methods
* 
* Active Resource exposes methods for creating, finding, updating, and deleting resources
* from REST web services.
* 
*   $ryan = new Person(array('first' => 'Ryan', 'last' => 'Daigle'));
*   $ryan->save();                # => true
*   $ryan->getId();                    # => 2
*   $Person->exists($ryan->getId());   # => true
*   $ryan->exists();              # => true
* 
*   $ryan = $Person->find(1);
*   # Resource holding our newly created Person object
* 
*   $ryan->first = 'Rizzle';
*   $ryan->save();            # => true
* 
*   $ryan->destroy();         # => true
* 
* As you can see, these are very similar to Active Record's lifecycle methods for database records.
* You can read more about each of these methods in their respective documentation.
* 
* === Custom REST methods
* 
* Since simple CRUD/lifecycle methods can't accomplish every task, Active Resource also supports
* defining your own custom REST methods. To invoke them, Active Resource provides the <tt>get</tt>,
* <tt>post</tt>, <tt>put</tt> and <tt>delete</tt> methods where you can specify a custom REST method
* name to invoke.
* 
*   # POST to the custom 'register' REST method, i.e. POST /people/new/register.xml.
*   $Person->create(array('name' => 'Ryan')->post('register');
*   # => array( id' => 1, name' => 'Ryan', position' => 'Clerk' }
* 
*   # PUT an update by invoking the 'promote' REST method, i.e. PUT /people/1/promote.xml?position=Manager.
*   $Person->find(1)->put('promote', array('position' => 'Manager'));
*   # => array( id' => 1, name' => 'Ryan', position' => 'Manager' }
* 
*   # GET all the positions available, i.e. GET /people/positions.xml.
*   $Person->get('positions');
*   # => [{name' => 'Manager'}, {name' => 'Clerk'}]
* 
*   # DELETE to 'fire' a person, i.e. DELETE /people/1/fire.xml.
*   $Person->find(1)->delete('fire');
* 
* For more information on using custom REST methods, see the
* AkActiveResourceCustomMethods documentation.
* 
* == Validations
* 
* You can validate resources client side by overriding validation methods in the base class.
* 
*   class Person extends AkActiveResource {
*      protected $site = "http://api.example.com/";
*      public function validate() {
*          if(!preg_match('/[a-zA-Z]+/', $this->last))
*          $this->addError("last", "has invalid characters");
*      }
*   }
* 
* See the AkActiveResourceValidations documentation for more information.
* 
* == Authentication
* 
* Many REST APIs will require authentication, usually in the form of basic
* HTTP authentication.  Authentication can be specified by:
* * putting the credentials in the URL for the +site+ variable.
* 
*    class Person extends AkActiveResource {
*      public $site = "http://ryan:password@api.example.com/";
*    }
* 
* * defining +user+ and/or +password+ variables
* 
*    class Person extends AkActiveResource {
*      protected $site     = "http://api.example.com/";
*      protected $user     = "ryan";
*      protected $password = "password";
*    }
* 
* For obvious security reasons, it is probably best if such services are available
* over HTTPS.
* 
* Note: Some values cannot be provided in the URL passed to site.  e.g. email addresses
* as usernames.  In those situations you should use the separate user and password option.
* 
* == Errors & Validation
* 
* Error handling and validation is handled in much the same manner as you're used to seeing in
* Active Record.  Both the response code in the HTTP response and the body of the response are used to
* indicate that an error occurred.
* 
* === Resource errors
* 
* When a GET is requested for a resource that does not exist, the HTTP <tt>404</tt> (Resource Not Found)
* response code will be returned from the server which will raise an AkResourceNotFound exception.
* 
*   # GET http://api.example.com/people/999.xml
*   $ryan = $Person->find(999); # 404, raises AkResourceNotFound
* 
* <tt>404</tt> is just one of the HTTP error response codes that Active Resource will handle with its own exception. The
* following HTTP response codes will also result in these exceptions:
* 
* * 200..399 - Valid response, no exception (other than 301, 302)
* * 301, 302 - AkResourceAkResourceRedirection
* * 400 - AkResourceBadRequest
* * 401 - AkResourceUnauthorizedAccess
* * 403 - AkResourceForbiddenAccess
* * 404 - AkResourceResourceNotFound
* * 405 - AkResourceMethodNotAllowed
* * 409 - AkResourceResourceConflict
* * 422 - AkResourceResourceInvalid (rescued by save as validation errors)
* * 401..499 - AkResourceClientError
* * 500..599 - AkResourceServerError
* * Other - AkResourceConnectionError
* 
* These custom exceptions allow you to deal with resource errors more naturally and with more precision
* rather than returning a general HTTP error.  For example:
* 
*   try{
*     $ryan = $Person->find($my_id);
*   }catch(AkResourceResourceNotFound $e){
*     $this->redirectTo(array('action' => 'not_found'));
*   }catch(AkResourceResourceInvalid $e) {
  *     $this->redirectTo(array('action' => 'create'));
*   }
* 
* === Validation errors
* 
* Active Resource supports validations on resources and will return errors if any of these validations fail
* (e.g., "First name can not be blank" and so on).  These types of errors are denoted in the response by
* a response code of <tt>422</tt> and an XML representation of the validation errors.  The save operation will
* then fail (with a <tt>false</tt> return value) and the validation errors can be accessed on the resource in question.
* 
*   $ryan = $Person->find(1);
*   $ryan->first(); # => ''
*   $ryan->save();  # => false
* 
*   # When
*   # PUT http://api.example.com/people/1.xml
*   # is requested with invalid values, the response is:
*   #
*   # Response (422):
*   # <errors type="array"><error>First cannot be empty</error></errors>
*   #
* 
*   $ryan->getFullErrorMessages();     # => ['First cannot be empty']
* 
* Learn more about Active Resource's validation features in the AkResourceValidations documentation.
* 
* === Timeouts
* 
* Active Resource relies on HTTP to access RESTful APIs and as such is inherently susceptible to slow or
* unresponsive servers. In such cases, your Active Resource method calls could timeout. You can control the
* amount of time before Active Resource times out with the +timeout+ variable.
* 
*   class Person extends AkActiveResource {
*     public $site = "http://api.example.com/";
*     public $timeout = 5;
*   }
* 
* This sets the +timeout+ to 5 seconds. You can adjust the +timeout+ to a value suitable for the RESTful API
* you are accessing. It is recommended to set this to a reasonably low value to allow your Active Resource
* clients (especially if you are using Active Resource in a Akelos application) to fail-fast (see
* http://en.wikipedia.org/wiki/Fail-fast) rather than cause cascading failures that could incapacitate your
* server.
* 
* When a timeout occurs, an AkResourceTimeoutError is raised. You should rescue from
* AkResourceTimeoutError in your Active Resource method calls.
* 
* Internally, Active Resource relies on PHP's PEAR HTTP_Request library to make HTTP requests. Setting +timeout+
* sets the <tt>readTimeout</tt> of the internal HTTP_Request instance to the same value. By default is set to 20 seconds.
*/
class AkActiveResource extends AkBaseModel {

    protected $site             = null;
    protected $connection       = null;
    protected $element_name     = null;
    protected $collection_name  = null;
    protected $primary_key      = 'id';
    protected $user             = null;
    protected $password         = null;
    protected $timeout          = 20;
    protected $format           = 'xml';


    /**
    * Constructor method for new resources; the optional +attributes+ parameter takes an array
    * of attributes for the \new resource.
    *
    * ==== Examples
    *   $my_course = new Course;
    *   $my_course->name = "Western Civilization";
    *   $my_course->lecturer = "Don Trotter";
    *   $my_course->save();
    *
    *   $my_other_course = new Course(array('name' => "Philosophy: Reason and Being", 'lecturer' => "Ralph Cling"));
    *   $my_other_course->save();
    */
    public function __construct($attributes = array()){
        $this->attributes     = array();
        $this->prefix_options = array();
        $this->load($attributes);
    }

    /**
    * Returns a clone of the resource that hasn't been assigned an +id+ yet and
    * is treated as a new resource.
    *
    *   $ryan = $Person->find(1);
    *   $not_ryan = clone $ryan;
    *   $not_ryan->isNewRecord();  # => true
    *
    * Any active resource member attributes will NOT be cloned, though all other
    * attributes are.  This is to prevent the conflict between any +prefix_options+
    * that refer to the original parent resource and the newly cloned parent
    * resource that does not exist.
    *
    *   $ryan = $Person->find(1);
    *   $ryan->address = $StreetAddress->find(1, array('person_id' => $ryan->getId()));
    *   $ryan->foo = array('not' => "an ARes instance");
    *
    *   $not_ryan = clone $ryan;
    *   $not_ryan->isNewRecord();  # => true
    *   $not_ryan->address;        # => null
    *   $not_ryan->foo;            # => array('not' => "an ARes instance")
    */
    public function __clone() {
        $pk = $this->getPrimaryKey();
        // Clone all attributes except the pk and any nested ARes
        foreach($this->getAttributes() as $k => $v){
            if($k == $pk || $v instanceof AkResource){
                unset($this->asstibutes[$k]);
            }
        }
    }

    /**
    * The logger for diagnosing and tracing Active Resource calls.
    */
    public function &getLogger(){
        return Ak::getLogger();
    }

    /**
      * Gets the URI of the REST resources to map for this class.  The site variable is required for
      * Active Resource's mapping to work.
      */
    public function getSite(){
        return $this->site;
    }

    /**
      * Sets the URI of the REST resources to map for this class to the value in the +site+ argument.
      * The site variable is required for Active Resource's mapping to work.
      */
    public function setSite($site){
        $this->connection = null;
        if(empty($site)){
            $this->site = null;
        }else{
            $this->site = $this->createSiteUriFrom($site);
            if(!empty($this->site['user'])){
                $this->user = urldecode($this->site['user']);
            }
            if(!empty($this->site['password'])){
                $this->password = urldecode($this->site['password']);
            }
        }
    }

    /**
      * Gets the user for REST HTTP authentication.
      */
    public function getUser(){
        return $this->user;
    }

    /**
      * Sets the user for REST HTTP authentication.
      */
    public function setUser($user){
        $this->connection = null;
        $this->user = $user;
    }

    /**
      * Gets the password for REST HTTP authentication.
      */
    public function getPassword(){
        return $this->password;
    }

    /**
      * Sets the password for REST HTTP authentication.
      */
    public function setPassword($password){
        $this->connection = null;
        $this->password = $password;
    }

    /**
      * Sets the format that attributes are sent and received:
      *
      *   $Person->setFormat('json');
      *   $Person->find(1); # => GET /people/1.json
      *
      *   $Person->setFormat('xml');
      *   $Person->find(1); # => GET /people/1.xml
      *
      * Default format is <tt>xml</tt>.
      */
    public function setFormat($format){
        $this->format = $format;
    }

    /**
      * Returns the current format, default is xml.
      */
    public function getFormat(){
        return $this->format;
    }

    /**
      * Sets the number of seconds after which requests to the REST API should time out.
      */
    public function setTimeout($timeout){
        $this->connection = null;
        $this->timeout = $timeout;
    }

    /**
      * Gets the number of seconds after which requests to the REST API should time out.
      */
    public function getTimeout(){
        return $this->timeout;
    }

    /**
      * An instance of AkResourceConnection that is the base connection to the remote service.
      * The +refresh+ parameter toggles whether or not the connection is refreshed at every request
      * or not (defaults to <tt>false</tt>).
      */
    public function getConnection($refresh = false){
        if(empty($this->connection) || $refresh){
            $this->connection = new AkResourceConnection($this->getSite(), $this->getFormat());
            if($this->user)     $this->connection->setUser($this->user);
            if($this->password) $this->connection->setPassword($this->password);
            if($this->timeout)  $this->connection->setTimeout($this->timeout);
        }
        return $this->connection;
    }

    public function getHeaders(){
        return empty($this->headers) ? array() : $this->headers;
    }

    public function getElementName(){
        if(empty($this->element_name)){
            $this->setElementName();
        }
        return $this->element_name;
    }

    public function setElementName($name = null){
        $this->element_name = empty($name) ? AkInflector::underscore(get_class($this)) : $name;
    }

    public function getCollectionName(){
        if(empty($this->collection_name)){
            $this->setCollectionName();
        }
        return $this->collection_name;
    }

    public function setCollectionName($name = null){
        $this->collection_name = empty($name) ? AkInflector::pluralize($this->getElementName()) : $name;
    }

    public function setPrimaryKey($primary_key){
        $this->primary_key = $primary_key;
    }

    public function getPrimaryKey(){
        return $this->primary_key;
    }


    /**
      * Gets the prefix for a resource's nested URL (e.g., <tt>prefix/collectionname/1.xml</tt>)
      */
    public function getPrefix($options= array()){
        if(isset($this->_redefinded['getPrefix'])){
            if(preg_match('/:\w+/', $this->_redefinded['getPrefix'], $matches)){
                return ltrim($matches[0], ':');
            }
            return '';
        }

        $default = $this->site['path'];
        if(substr($default,-1,1) != '/'){
            $default .= '/';
        }
        // generate the actual method based on the current site path
        $this->setPrefix($default);
        return $this->getPrefix($options);
    }

    /**
      * An attribute reader for the source string for the resource path prefix.  This
      * method is regenerated at runtime based on what the prefix is set to.
      */
    public function getPrefixSource() {
        if(isset($this->_redefinded['getPrefixSource'])){
            return $this->_redefinded['getPrefixSource'];
        }
        $this->getPrefix(); // generate #prefix and #prefix_source methods first
        return $this->prefix_source;
    }

    /**
      * Sets the prefix for a resource's nested URL (e.g., <tt>prefix/collectionname/1.xml</tt>).
      * Default value is <tt>$this->site['path']</tt>.
      */
    public function setPrefix($value = '/') {

        // Clear prefix parameters in case they have been cached
        $this->prefix_parameters = null;

        // Redefine the new methods.
        $this->_redefinded['getPrefixSource'] = $value;
        $this->_redefinded['getPrefix'] = $value;
    }


    /**
      * Gets the element path for the given ID in +id+.  If the +query_options+ parameter is omitted, Akelos
      * will split from the prefix options.
      *
      * ==== Options
      * +prefix_options+ - An array to add a prefix to the request for nested URLs (e.g., <tt>account_id' => 19</tt>
      *                    would produce a URL like <tt>/accounts/19/purchases.xml</tt>).
      * +query_options+ - An array to add items to the query string for the request.
      *
      * ==== Examples
      *   $Post->_getElementPath(1);
      *   # => /posts/1.xml
      *
      *   $Comment->_getElementPath(1, array('post_id' => 5));
      *   # => /posts/5/comments/1.xml
      *
      *   $Comment->_getElementPath(1, array('post_id' => 5, 'active' => 1));
      *   # => /posts/5/comments/1.xml?active=1
      *
      *   $Comment->_getElementPath(1, array('post_id' => 5), array('active' => 1));
      *   # => /posts/5/comments/1.xml?active=1
      */
    public function _getElementPath($id, $prefix_options = array(), $query_options = null){
        if(is_null($query_options)){
            list($prefix_options, $query_options) = $this->splitOptions($prefix_options);
        }
        return $this->getPrefix($prefix_options).$this->getCollectionName().'/'.$id.'.'.$this->getFormat().$this->getQueryString($query_options);
    }

    /**
      * Gets the collection path for the REST resources.  If the +query_options+ parameter is omitted, Akelos
      * will split from the +prefix_options+.
      *
      * ==== Options
      * * +prefix_options+ - A array to add a prefix to the request for nested URLs (e.g., <tt>account_id' => 19</tt>
      *   would produce a URL like <tt>/accounts/19/purchases.xml</tt>).
      * * +query_options+ - A array to add items to the query string for the request.
      *
      * ==== Examples
      *   $Post->_getCollectionPath();
      *   # => /posts.xml
      *
      *   Comment->_getCollectionPath(array('post_id' => 5));
      *   # => /posts/5/comments.xml
      *
      *   Comment->_getCollectionPath(array('post_id' => 5, 'active' => 1));
      *   # => /posts/5/comments.xml?active=1
      *
      *   Comment->_getCollectionPath(array(post_id' => 5), array(active' => 1));
      *   # => /posts/5/comments.xml?active=1
      */
    public function _getCollectionPath($prefix_options = array(), $query_options = null){
        if(is_null($query_options)){
            list($prefix_options, $query_options) = $this->splitOptions($prefix_options);
        }
        return $this->getPrefix($prefix_options).$this->getCollectionName().'.'.$this->getFormat().$this->getQueryString($query_options);
    }

    /**
      * Creates a new resource instance and makes a request to the remote service
      * that it be saved, making it equivalent to the following simultaneous calls:
      *
      *   $ryan = new Person(array('first' => 'ryan'));
      *   $ryan->save();
      *
      * Returns the newly created resource.  If a failure has occurred an
      * exception will be raised (see <tt>save</tt>).  If the resource is invalid and
      * has not been saved then <tt>isValid()</tt> will return <tt>false</tt>,
      * while <tt>isNewRecord()</tt> will still return <tt>true</tt>.
      *
      * ==== Examples
      *   $Person->create(array('name' => 'Jeremy', 'email' => 'myname@example.com', 'enabled' => true));
      *   $my_person = $Person->findFirst();
      *   $my_person->email; # => myname@example.com
      *
      *   $dhh = $Person->create(array('name' => 'David', 'email' => 'dhh@example.com', 'enabled' => true));
      *   $dhh->isValid(); # => true
      *   $dhh->isNewRecord(); # => false
      *
      *   # We'll assume that there's a validation that requires the name attribute
      *   $that_guy = $Person->create('name' => '', 'email' => 'thatguy@example.com', 'enabled' => true);
      *   $that_guy->isValid(); # => false
      *   $that_guy->isNewRecord();   # => true
      */
    public function create($attributes = array()){
        $class_name = get_class($this);
        $Resource = new $class_name($attributes);
        $Resource->save();
        return $Resource;
    }

    /**
      * Core method for finding resources.  Used similarly to Active Record's +find+ method.
      *
      * ==== Arguments
      * The first argument is considered to be the scope of the query.  That is, how many
      * resources are returned from the request.  It can be one of the following.
      *
      * * <tt>one</tt> - Returns a single resource.
      * * <tt>first</tt> - Returns the first resource found.
      * * <tt>last</tt> - Returns the last resource found.
      * * <tt>all</tt> - Returns every resource that matches the request.
      *
      * ==== Options
      *
      * * <tt>from</tt> - Sets the path or custom method that resources will be fetched from.
      * * <tt>params</tt> - Sets query and prefix (nested URL) parameters.
      *
      * ==== Examples
      *   $Person->find(1)
      *   # => GET /people/1.xml
      *
      *   $Person->find('all)
      *   # => GET /people.xml
      *
      *   $Person->find('all', array('params' => array( 'title' => "CEO" )));
      *   # => GET /people.xml?title=CEO
      *
      *   $Person->find('first', array('from' => 'managers'));
      *   # => GET /people/managers.xml
      *
      *   $Person->find('last', array('from' => 'managers'));
      *   # => GET /people/managers.xml
      *
      *   $Person->find('all', array('from' => "/companies/1/people.xml"));
      *   # => GET /companies/1/people.xml
      *
      *   $Person->find('one', array('from' => 'leader'))
      *   # => GET /people/leader.xml
      *
      *   $Person->find('all', array('from' => 'developers', 'params' => array( 'language' => 'PHP' )))
      *   # => GET /people/developers.xml?language=PHP
      *
      *   $Person->find('one', array('from' => "/companies/1/manager.xml"))
      *   # => GET /companies/1/manager.xml
      *
      *   $StreetAddress->find(1, array('params' => array( 'person_id' => 1 )));
      *   # => GET /people/1/street_addresses/1.xml
      */
    public function find(){
        $arguments = func_get_args();
        $scope   = array_shift($arguments);
        $options = !empty($arguments) ? array_shift($arguments) : array();

        switch($scope) {
            case 'all'    : return $this->findEvery($options);
            case 'first'  : return $this->findEvery($options)->first();
            case 'last'   : return $this->findEvery($options)->last();
            case 'one'    : return $this->findOne($options);
            default       : return $this->findSingle($scope, $options);
        }
    }

    /**
      * Deletes the resources with the ID in the +id+ parameter.
      *
      * ==== Options
      * All options specify prefix and query parameters.
      *
      * ==== Examples
      *   $Event->delete(2); # sends DELETE /events/2
      *
      *   $Event->create(array('name' => 'Free Concert', 'location' => 'Community Center'))
      *   $my_event = $Event->find('first); # let's assume this is event with ID 7
      *   $Event->delete($my_event->getId()); # sends DELETE /events/7
      *
      *   # Let's assume a request to events/5/cancel.xml
      *   $Event->delete($params['id']); # sends DELETE /events/5
      */
    public function delete($id, $options = array()) {
        $this->connection->delete($this->_getElementPath($id, $options));
    }

    /**
      * Asserts the existence of a resource, returning <tt>true</tt> if the resource is found.
      *
      * ==== Examples
      *   $Note->create(array('title' => 'Hello, world.', 'body' => 'Nothing more for now...'))
      *   $Note->exists(1); # => true
      *
      *   $Note->exists(1349); # => false
      */
    public function exists($id, $options = array()) {
        if($id) {
            list($prefix_options, $query_options) = $this->splitOptions(isset($options['params']) ? $options['params'] : array());
            $path = $this->_getElementPath($id, $prefix_options, $query_options);
            $response = $this->connection->head($path, $this->headers);
            return $response->code == 200;
        }
        throw new AkResourceResourceNotFound();
    }

    /**
        * Find every resource
        */
    private function findEvery($options){
        if(!empty($options['from']) && is_string($options['from'])) {
            $path = $options['from'].$this->getQueryString(isset($options['params']) ? $options['params'] : array());
            return $this->instantiateCollection($this->connection->get($path, $this->headers));
        } else {
            list($prefix_options, $query_options) = $this->splitOptions(isset($options['params']) ? $options['params'] : array());
            $path = $this->get_getCollectionPath($prefix_options, $query_options);
            return $this->instantiateCollection($this->connection->get($path, $this->headers), $prefix_options);
        }
    }

    /**
        * Find a single resource from a one-off URL
        */
    private function findOne($options) {
        $path = $options['from'].$this->getQueryString($options['params']);
        return $this->instantiateCollection($this->connection->get($path, $this->headers));
    }

    /**
        * Find a single resource from the default URL
        */
    private function findSingle($scope, $options) {
        list($prefix_options, $query_options) = $this->splitOptions(isset($options['params']) ? $options['params'] : array());
        $path = $this->_getElementPath($scope, $prefix_options, $query_options);
        return $this->instantiateRecord($this->connection->get($path, $this->headers), $prefix_options);
    }

    private function instantiateCollection($collection, $prefix_options = array()) {
        $collection = empty($collection) ? array() : $collection;
        $records = array();
        foreach($collection as $record){
            $records[] = $this->instantiateRecord($record, $prefix_options);
        }
        return $records;
    }

    private function instantiateRecord($record, $prefix_options = array()) {
        $class_name = get_class($this);
        $resource = new $class_name($record);
        $resource->setPrefixOptions($prefix_options);
        return $resource;
    }

    /**
        * Accepts a URI and creates the site URI array from that.
        */
    private function createSiteUriFrom($site) {
        return is_string($site) ? parse_url($site) : $site;
    }

    /**
        * Returns a set of the current prefix parameters.
        */
    private function getPrefixParameters() {
        if(!empty($this->prefix_parameters)){
            return $this->prefix_parameters;
        }
        return $this->getPrefixSource();
    }

    /**
        * Builds the query string for the request.
        */
    private function getQueryString($options) {
        return !empty($options) ? '?'.http_build_query($options) : '';
    }

    /**
        * Split an option array into two arrays, one containing the prefix options,
        * and the other containing the leftovers.
        */
    private function splitOptions($options = array()) {
        $prefix_options = $query_options = array();
        $prefix_parameters = $this->getPrefixParameters();
        foreach($options as $key => $value) {
            if(!empty($key)){
                if(in_array($key, $prefix_parameters)){
                    $prefix_options[$key] = $value;
                }else{
                    $query_options[$key] = $value;
                }
            }
        }
        return array($prefix_options, $query_options);
    }


    public function getAttributes(){
        return $this->attributes;
    }

    /**
    * Returns +true+ if this object hasn't yet been saved, otherwise, returns +false+.
    *
    * ==== Examples
    *   $not_new = $Computer->create(array('brand' => 'Apple', 'make' => 'MacBook', 'vendor' => 'MacMall'));
    *   $not_new->isNewRecord(); # => false
    *   
    *   $is_new = new Computer(array('brand' => 'IBM', 'make' => 'Thinkpad', 'vendor' => 'IBM'));
    *   $is_new->isNewRecord(); # => true
    *   
    *   $is_new->save();
    *   $is_new->isNewRecord(); # => false
    */
    public function isNewRecord() {
        return $this->getId() == null;
    }

    /**
    * Gets the <tt>id</tt> attribute of the resource.
    */
    public function getId() {
        $pk = $this->getPrimaryKey();
        return isset($this->attributes[$pk]) ? $this->attributes[$pk] : null;
    }

    /**
    * Sets the <tt>id</tt> attribute of the resource.
    */
    public function setId($id){
        $this->attributes[$this->getPrimaryKey()] = $id;
    }

    /**
    * Allows Active Resource objects to be used as parameters in Action Pack URL generation.
    */
    public function toParam() {
        return $this->getId();
    }

    /**
    * Test for equality.  Resource are equal if and only if +other+ is the same object or
    * is an instance of the same class, is not <tt>isNewRecord()</tt>, and has the same +id+.
    *
    * ==== Examples
    *   $ryan = $Person->create(array('name' => 'Ryan'));
    *   $jamie = $Person->create(array('name' => 'Jamie'));
    *   
    *   $ryan->equals($jamie);
    *   # => false (Different name attribute and id)
    *   
    *   $ryan_again = new Person(array('name' => 'Ryan'));
    *   $ryan->equals($ryan_again);
    *   # => false ($ryan_again isNewRecord())
    *   
    *   $ryans_clone = $Person->create(array('name' => 'Ryan'));
    *   $ryan->equals($ryans_clone);
    *   # => false (Different id attributes)
    *   
    *   $ryans_twin = $Person->find($ryan->getId());
    *   $ryan->equals($ryans_twin);
    *   # => true
    */
    public function equals($other) {
        $this_class = get_class($this);
        $other_class = get_class($other);
        return ($other_class instanceof $this_class) && $other->getId() == $this->getId() && $this->prefix_options == $other->prefix_options;
    }


    /**
    * Saves (+POST+) or updates (+PUT+) a resource.  Delegates to +create+ if the object +isNewRecord+,
    * +update+ if it exists. If the response to the save includes a body, it will be assumed that this body
    * is XML for the final object as it looked after the save (which would include attributes like +created_at+
    * that weren't part of the original submit).
    *
    * ==== Examples
    *   $my_company = new Company(array('name' => 'RoleModel Software', 'owner' => 'Ken Auer', 'size' => 2));
    *   $my_company->isNewRecord(); # => true
    *   $my_company->save(); # sends POST /companies/ (create)
    *
    *   $my_company->isNewRecord(); # => false
    *   $my_company->size = 10;
    *   $my_company->save(); # sends PUT /companies/1 (update)
    */
    public function save() {
        $this->_callback('beforeSave');
        $this->isNewRecord() ? $this->_create() : $this->_update();
        $this->_callback('afterSave');
    }

    /**
    * Deletes the resource from the remote service.
    *
    * ==== Examples
    *   $my_id = 3;
    *   $my_person = $Person->find($my_id);
    *   $my_person->destroy();
    *   $Person->find($my_id); # 404 (Resource Not Found)
    *   
    *   $new_person = $Person->create(array('name' => 'James'));
    *   $new_id = $new_person->getId(); # => 7
    *   $new_person->destroy();
    *   $Person->find($new_id); # 404 (Resource Not Found)
    */
    public function destroy() {
        $this->_callback('beforeDestroy');
        $this->connection->delete($this->_getElementPath(), $this->headers);
        $this->_callback('afterDestroy');
    }

    /**
    * Converts the resource to an XML string representation.
    *
    * ==== Options
    * The +options+ parameter is handed off to the +to_xml+ method on each
    * attribute, so it has the same options as the +to_xml+ methods in
    * Active Support.
    *
    * * <tt>indent</tt> - Set the indent level for the XML output (default is +2+).
    * * <tt>dasherize</tt> - Boolean option to determine whether or not element names should
    *   replace underscores with dashes (default is <tt>false</tt>).
    * * <tt>skip_instruct</tt> - Toggle skipping the +instruct!+ call on the XML builder
    *   that generates the XML declaration (default is <tt>false</tt>).
    *
    * ==== Examples
    *   $my_group = $SubsidiaryGroup->find('first');
    *   $my_group->toXml();
    *   # => <?xml version="1.0" encoding="UTF-8"?>
    *   #    <subsidiary_group> [...] </subsidiary_group>
    *
    *   $my_group->toXml(array('dasherize' => true));
    *   # => <?xml version="1.0" encoding="UTF-8"?>
    *   #    <subsidiary-group> [...] </subsidiary-group>
    *
    *   $my_group->toXml(array('skip_instruct' => true));
    *   # => <subsidiary_group> [...] </subsidiary_group>
    */
    public function toXml($options = array()) {
        $options['root'] = $this->getElementName();
        return Ak::convert('array', 'xml', $this->getAttributes(), $options);
    }

    public function fromXml($xml) {
        return Ak::convert('xml', 'array', $xml);
    }

    /**
    * Coerces to an array for JSON encoding.
    *
    * ==== Options
    * The +options+ are passed to the +to_json+ method on each
    * attribute, so the same options as the +to_json+ methods in
    * Active Support.
    *
    * * <tt>only</tt> - Only include the specified attribute or list of
    *   attributes in the serialized output. Attribute names must be specified
    *   as strings.
    * * <tt>except</tt> - Do not include the specified attribute or list of
    *   attributes in the serialized output. Attribute names must be specified
    *   as strings.
    *
    * ==== Examples
    *   $person = $Person->create(array('first_name' => "Jim", 'last_name' => "Smith"));
    *   $person->toJson();
    *   # => {"first_name": "Jim", "last_name": "Smith"}
    *
    *   $person->toJson('only' => ["first_name"])
    *   # => {"first_name": "Jim"}
    *
    *   $person->toJson(array('except' => array("first_name")));
    *   # => {"last_name": "Smith"}
    */
    public function toJson($options = array()) {
        $attributes = $this->getAttributes();
        if(!empty($options['except'])){
            $attributes = Ak::delete($attributes, $options['except']);
        }elseif(!empty($options['only'])){
            $attributes = Ak::pick($options['only'], $attributes);
        }
        return json_encode($attributes);
    }

    public function fromJson($json) {
        return json_decode($json);
    }

    /**
    * Returns the serialized string representation of the resource in the configured
    * serialization format specified in AkResource->format. The options
    * applicable depend on the configured encoding format.
    */
    public function encode($options= array()){
        $format_method = 'to'.AkInflector::camelize($this->getFormat());
        if(method_exists($this, $format_method)){
            return $this->$format_method($options);
        }
        throw new Exception('Invalid format encoding method '.$format_method);
    }

    /**
    * Returns the resource i unserialized from the format specified in 
    * AkResource->format. The options applicable depend on the configured encoding format.
    */
    public function decode($response_string){
        $format_method = 'from'.AkInflector::camelize($this->getFormat());
        if(method_exists($this, $format_method)){
            return $this->$format_method($response_string);
        }
        throw new Exception('Invalid format encoding method '.$format_method);
    }

    /**
    * A method to reload the attributes of this object from the remote web service.
    *
    * ==== Examples
    *   $my_branch = $Branch->find('first');
    *   $my_branch->name # => "Wislon Raod"
    *   
    *   # Another client fixes the typo...
    *   
    *   $my_branch->name # => "Wislon Raod"
    *   $my_branch->reload();
    *   $my_branch->name(); # => "Wilson Road"
    */
    public function reload() {
        $this->load($this->find($this->toParam(), array('params' => $this->prefix_options))->getAttributes());
    }

    /**
    * A method to manually load attributes from an array. Recursively loads collections of
    * resources.  This method is called in +__construct+ and +create+ when an array of attributes
    * is provided.
    *
    * ==== Examples
    *   $my_attrs = array('name' => 'J&J Textiles', 'industry' => 'Cloth and textiles');
    *   $my_attrs = array('name' => 'Marty', 'colors' => array("red", "green", "blue"));
    *   
    *   $the_supplier = Ak::get('Supplier')->find('first');
    *   $the_supplier->name # => 'J&M Textiles';
    *   $the_supplier->load($my_attrs);
    *   $the_supplier->name # => 'J&J Textiles'
    *   
    *   # These two calls are the same as new Supplier($my_attrs)
    *   $my_supplier = new Supplier();
    *   $my_supplier->load($my_attrs);
    *   
    *   # These three calls are the same as Supplier->create($my_attrs)
    *   $your_supplier = new Supplier();
    *   $your_supplier->load($my_attrs)
    *   $your_supplier->save();
    */
    public function load($attributes) {
        if(!is_array($attributes)){
            throw new ArgumentErrorException("expected an attributes array, got ".gettype($attributes));
        }

        list($this->prefix_options, $this->attributes) = $this->splitOptions($attributes);
        foreach($attributes as $key => $value) {
            if(is_array($value)){
                $resource = $this->_findOrCreateResourceFor($key);
                $resource->setAttributes($value);
                $value = $resource;
            }
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Update the resource on the remote service.
     */
    protected function _update() {
        $this->_callback('beforeUpdate');
        if($response = $this->connection->put($this->_getElementPath($this->prefix_options), $this->encode(), $this->headers)){
            $this->_loadAttributesFromResponse($response);
            $this->_callback('afterUpdate');
        }
    }

    /**
      * Create (i.e., save to the remote service) the new resource.
      */
    protected function _create() {
        $this->_callback('beforeCreate');
        if($response = $connection->post($this->_getCollectionPath(), $this->encode(), $this->headers)){
            $this->setId($this->_getIdFromResponse($response));
            $this->_loadAttributesFromResponse($response);
            $this->_callback('afterCreate');
        }
    }

    protected function _loadAttributesFromResponse($response) {
        if($response['Content-Length'] != 0 && strlen(trim($response->body)) > 0){
            return $this->load($this->decode($response->body));
        }
        return array();
    }

    /**
      * Takes a response from a typical create post and pulls the ID out.
      */
    protected function _getIdFromResponse($response) {
        if(!empty($response['Location']) && preg_match('/\/([^\/]*?)(\.\w+)?$/', $response['Location'], $matches)){
            return $matches[1];
        }
    }

    protected function _getElementPath($options = null){
        return $this->_getElementPath($this->toParam(), empty($options) ? $this->prefix_options : $options);
    }

    protected function _getCollectionPath($options = null){
        return $this->_getCollectionPath(empty($options) ? $this->prefix_options : $options);
    }


    /**
      * Tries to find a resource for a given collection name; if it fails, then the resource is created
      */
    private function _findOrCreateResourceForCollection($name) {
        return $this->_findOrCreateResourceFor(AkInflector::singularize($name));
    }

    /**
      * Tries to find a resource in a non empty list of nested modules
      * Raises a NameError if it was not found in any of the given nested modules
      */
    private function _findResourceInModules($resource_name, $module_names) {
        #$receiver = new stdClass;
        #$namespaces = $module_names[0, module_names.size-1].map do |module_name|
        #  receiver = receiver.const_get(module_name)
        #}
        #if namespace = namespaces.reverse.detect { |ns| ns.const_defined?(resource_name) }
        #  return namespace.const_get(resource_name)
        #else
        #  raise NameError
        #}
    }

    /**
      * Tries to find a resource for a given name; if it fails, then the resource is created.
      */
    private function _findOrCreateResourceFor($name) {
        $resource_name = AkInflector::camelize($name);
        $ancestors = strstr($resource_name, '_') ? explode('_', $resource_name) : array();
        if(!empty($ancestors)){
            $this->_findResourceInModules($resource_name, $ancestors);
        }
        if(isset($this->$resource_name)){
            $resource = $this->$resource_name;
        }else{
            $resource = $this->$resource_name = new AkResource();
        }
        $resource->setPrefix($this->getPrefix());
        $resource->setSite($this->getSite());
        return $resource;
    }

    public function __get($attribute){
        return $this->_attributes[$attribute];
    }

    public function __set($attribute, $value){
        $this->_attributes[$attribute] = $value;
    }

    // Lazy loading
    protected function _enableLazyLoadingExtenssions($options = array()) {
        empty($options['skip_observers'])   && $this->_enableObservers();
        empty($options['skip_errors'])      && $this->_enableErrors();
        empty($options['skip_validations']) && $this->_enableValidations();
    }
}

