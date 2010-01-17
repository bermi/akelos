<?php

require_once(dirname(__FILE__).'/../config.php');

class ResourcesController extends AkActionController{
    public function index () { $this->renderNothing(); }
    public function show () { $this->index(); }
    public function rescue_action ($e){ throw $e; }
}

class ThreadsController extends ResourcesController {}
class MessagesController extends ResourcesController {}


class ElevenDumbMessagesController extends ResourcesController {}
class PhotosController extends ResourcesController {}
class DealsController extends ResourcesController {}
class CommentsController extends ResourcesController {}
class AuthorsController extends ResourcesController {}
class LogosController extends ResourcesController {}
{}
class AccountsController extends ResourcesController {}
class AdminController extends ResourcesController {}
class ProductsController extends ResourcesController {}
class ImagesController extends ResourcesController {}


class BackofficeController extends ResourcesController {}
/**/class Backoffice_ProductsController extends BackofficeController {}
/**/class Backoffice_TagsController extends BackofficeController {}
/**/class Backoffice_ManufacturersController extends BackofficeController {}
/**/class Backoffice_ImagesController extends BackofficeController {}
/**/class Backoffice_AdminController extends BackofficeController {}
/**/ class Backoffice_Admin_ProductsController extends Backoffice_AdminController {}
/**/ class Backoffice_Admin_ImagesController extends Backoffice_AdminController {}


class ResourcesTestCase extends AkControllerUnitTest{

    private $_singletons = array(
    'AkRouterSingleton' => null,
    'AkRequestSingleton' => null,
    'AkUrlWriterSingleton' => null,
    );

    public function setup(){
        foreach (array_keys($this->_singletons) as $singleton){
            $this->_singletons[$singleton] = Ak::getStaticVar($singleton);
            Ak::unsetStaticVar($singleton);
        }
    }

    public function tearDown(){
        foreach (array_keys($this->_singletons) as $singleton){
            Ak::setStaticVar($singleton, $this->_singletons[$singleton]);
        }
    }

    /**/

    public function test_should_arrange_actions () {
        $Resource = new AkResource('messages', array(
        'collection'=> array('rss'=>'get', 'reorder'=>'post', 'csv'=>'post'),
        'member' => array('rss'=>'get', 'atom'=>'get', 'upload'=>'post', 'fix'=>'post'),
        'add' => array('preview'=>'get', 'draft'=>'get')));

        $this->assertResourceMethods(array('rss'), $Resource, 'collection', 'get');
        $this->assertResourceMethods(array('reorder', 'csv'), $Resource, 'collection', 'post');
        $this->assertResourceMethods(array('edit', 'rss', 'atom'), $Resource, 'member', 'get');
        $this->assertResourceMethods(array('upload', 'fix'), $Resource, 'member', 'post');
        $this->assertResourceMethods(array('add', 'preview', 'draft'), $Resource, 'add', 'get');
    }

    public function test_should_resource_controller_name_equal_resource_name_by_default () {
        $Resource = new AkResource('messages', array());
        $this->assertEqual('messages', $Resource->getController());
    }

    public function test_should_resource_controller_name_equal_controller_option () {
        $Resource = new AkResource('messages', array('controller'=>'posts'));
        $this->assertEqual('messages', $Resource->getController());
    }

    public function test_should_all_singleton_paths_be_the_same () {
        foreach (array('getPath', 'getNestingPathPrefix', 'getMemberPath') as $method){
            $Resource = new AkSingletonResource('messages', array('path_prefix'=>'admin'));
            $this->assertEqual('admin/messages', $Resource->$method());
        }
    }

    public function test_default_restful_routes () {
        $this->useRestfulRoutingMapper('messages');
        $this->assertSimplyRestfulFor('messages');
    }

    public function test_override_paths_for_member_and_collection_methods () {

        $resource = 'messages';

        $collection_methods = array('rss'=>'get', 'reorder'=>'post', 'csv'=>'post');
        $member_methods = array('rss'=>'get', 'atom'=>'get', 'upload'=>'post', 'fix'=>'post');
        $path_names = array('rss'=>'canal', 'add'=>'nuevo', 'fix'=>'corregir');

        $options = array(
        'collection' => $collection_methods,
        'member' => $member_methods,
        'path_names'=> $path_names);

        $this->useRestfulRoutingMapper($resource, $options);

        $this->assertRestfulRoutesFor($resource, $options);

        foreach ($member_methods as $action => $method){
            $this->assertRecognizes(array('action'=> $action, 'controller' => $resource, 'id'=>1),
            array('path'=>'/'.$resource.'/1/'.(isset($path_names[$action])?$path_names[$action]:$action),
            'method'=>$method)
            );

            foreach ($collection_methods as $action => $method){
                $this->assertRecognizes(array('action'=> $action, 'controller' => $resource),
                array('path'=>'/'.$resource.'/'.(isset($path_names[$action])?$path_names[$action]:$action),
                'method'=>$method)
                );
            }
        }

        $this->assertRestfulNamedRoutesFor($resource, $options);

        foreach(array_keys($collection_methods) as $action) {
            $this->assertNamedRoute('/'.$resource.'/'.(isset($path_names[$action])?$path_names[$action]:$action), $action.'_'.$resource.'_path', array('action'=>$action, 'controller' => $resource));
        }

        foreach(array_keys($member_methods) as $action) {
            $this->assertNamedRoute('/'.$resource.'/1/'.(isset($path_names[$action])?$path_names[$action]:$action), $action.'_'.AkInflector::singularize($resource).'_path', array('action'=>$action, 'controller' => $resource, 'id'=>1));
        }

    }

    public function test_override_paths_for_default_restful_actions () {
        $Resource = new AkResource('messages', array(
        'path_names'=> array('add'=>'nuevo', 'edit'=>'editar')));

        $this->assertEqual($Resource->getAddPath(), $Resource->getPath().'/nuevo');
    }


    public function test_multiple_default_restful_routes () {
        $this->useRestfulRoutingMapper(array('messages', 'comments'));
        $this->assertSimplyRestfulFor('messages');
        $this->assertSimplyRestfulFor('comments');
    }

    public function test_irregular_id_with_requirements_should_pass () {
        $expected_options = array('controller'=>'messages', 'action'=>'show', 'id'=>'1.1.1');

        $this->useRestfulRoutingMapper('messages', array('requirements'=> array('id'=>'/[0-9]\.[0-9]\.[0-9]/')));

        $this->assertRecognizes($expected_options, array('path'=>'messages/1.1.1', 'method'=>'get', 'controller'=>'messages'));
    }

    public function test_with_path_prefix_requirements () {
        $expected_options = array('controller'=>'messages', 'action'=>'show', 'thread_id'=>'1.1.1', 'id'=>1);

        $this->useRestfulRoutingMapper('messages', array('path_prefix'=>'/thread/:thread_id', 'requirements'=> array('thread_id'=>'/[0-9]\.[0-9]\.[0-9]/')));
        $this->assertRecognizes($expected_options, array('path'=>'thread/1.1.1/messages/1', 'method'=>'get', 'controller'=>'messages'));
    }

    public function test_irregular_id_requirements_should_get_passed_to_member_actions () {
        $expected_options = array('controller'=>'messages', 'action'=>'custom', 'id'=>'1.1.1');

        $this->useRestfulRoutingMapper('messages', array('member'=> array('custom'=>'get'), 'requirements'=> array('id'=>'/[0-9]\.[0-9]\.[0-9]/')));
        $this->assertRecognizes($expected_options, array('path'=>'messages/1.1.1/custom', 'method'=>'get', 'controller' => 'messages'));
    }

    public function test_with_path_prefix () {
        $this->useRestfulRoutingMapper('messages', array('path_prefix'=>'/thread/:thread_id'));
        $this->assertSimplyRestfulFor('messages', array('path_prefix'=>'thread/5/', 'options'=> array('thread_id' => 5), 'controller' => 'messages'));
    }

    public function test_multiple_with_path_prefix () {
        $this->useRestfulRoutingMapper(array('messages', 'comments'), array('path_prefix'=>'/thread/:thread_id'));
        $this->assertSimplyRestfulFor('messages', array('path_prefix'=>'thread/5/', 'options'=> array('thread_id'=>5), 'controller' => 'messages'));
        $this->assertSimplyRestfulFor('comments', array('path_prefix'=>'thread/5/', 'options'=> array('thread_id'=>5), 'controller' => 'comments'));
    }

    public function test_with_name_prefix () {
        $this->useRestfulRoutingMapper('messages', array('name_prefix'=>'post_'));
        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'post_', 'controller' => 'messages'));
    }




    public function test_with_collection_actions () {
        $controller = 'messages';

        $actions = array('a'=>'get', 'b'=>'put', 'c'=>'post', 'd'=>'delete');
        $options = array('collection'=>$actions);

        $this->useRestfulRoutingMapper($controller, $options);

        $this->assertRestfulRoutesFor($controller, $options);

        foreach($actions as $action => $method){
            $this->assertRecognizes(array('action'=>$action, 'controller' => $controller), array('path'=>'/'.$controller.'/'.$action, 'method'=>$method));
        }

        $this->assertRestfulNamedRoutesFor($controller, $options);

        foreach(array_keys($actions) as $action) {
            $this->assertNamedRoute('/'.$controller.'/'.$action, $action.'_'.$controller.'_path', array('action'=>$action,'controller' => $controller));
        }
    }


    public function test_with_collection_actions_and_name_prefix () {
        $controller = 'messages';

        $actions = array('a'=>'get', 'b'=>'put', 'c'=>'post', 'd'=>'delete');
        $options = array(
        'collection'=>$actions,
        'path_prefix'=>'/threads/:thread_id',
        'name_prefix'=>'thread_',
        );

        $this->useRestfulRoutingMapper($controller, $options);

        $this->assertRestfulRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1), 'controller' => $controller));
        $this->assertRestfulNamedRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1), 'controller' => $controller));

        foreach(array_keys($actions) as $action) {
            $this->assertNamedRoute('/threads/1/'.$controller.'/'.$action, $action.'_thread_'.$controller.'_path', array('action'=>$action,'controller' => $controller));
        }
    }


    public function test_with_collection_actions_and_name_prefix_and_member_action_with_same_name () {
        $controller = 'messages';

        $actions = array('a'=>'get');
        $options = array('path_prefix'=>'/threads/:thread_id', 'name_prefix'=>'thread_', 'collection'=>$actions, 'member'=>$actions);

        $this->useRestfulRoutingMapper($controller, $options);

        $this->assertRestfulRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));
        $this->assertRestfulNamedRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));

        foreach(array_keys($actions) as $action) {
            $this->assertNamedRoute('/threads/1/'.$controller.'/'.$action, $action.'_thread_'.$controller.'_path', array('action'=>$action));
        }
    }

    public function test_with_collection_action_and_name_prefix_and_formatted () {
        $controller = 'messages';
        $actions = array('a'=>'get', 'b'=>'put', 'c'=>'post', 'd'=>'delete');
        $options = array('path_prefix'=>'/threads/:thread_id', 'name_prefix'=>'thread_', 'collection'=>$actions);

        $this->useRestfulRoutingMapper($controller, $options);
        $this->assertRestfulRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));

        $this->assertRestfulNamedRoutesFor($controller, array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));

        foreach(array_keys($actions) as $action) {
            $this->assertNamedRoute('/threads/1/'.$controller.'/'.$action.'.xml', $action.'_thread_'.$controller.'_path', array('action'=>$action, 'format'=>'xml'));
        }
    }



    public function test_with_member_action () {
        foreach(array('put', 'post') as $method){
            $this->useRestfulRoutingMapper('messages', array('member'=> array('mark'=>$method)));
            $mark_options = array('action'=>'mark', 'id'=>1, 'controller' => 'messages');
            $mark_path = '/messages/1/mark';

            $this->assertRecognizes($mark_options, array('path'=>$mark_path, 'method'=>$method));
        }
    }


    public function test_with_member_action_and_requirement () {
        $expected_options = array('controller'=>'messages', 'action'=>'mark', 'id'=>'1.1.1');

        $this->useRestfulRoutingMapper('messages', array('requirements'=> array('id'=>'/[0-9]\.[0-9]\.[0-9]/'), 'member'=> array('mark'=>'get')));
        $this->assertRecognizes($expected_options, array('path'=>'messages/1.1.1/mark', 'method'=>'get'));
    }

    public function test_member_when_override_paths_for_default_restful_actions_with () {
        foreach(array('put', 'post') as $method){
            $this->useRestfulRoutingMapper('messages', array('member'=> array('mark'=>$method), 'path_names'=> array('add'=>'nuevo')));
            $mark_options = array('action'=>'mark', 'id'=>1, 'controller' => 'messages');
            $mark_path = '/messages/1/mark';
            $this->assertRecognizes($mark_options, array('path'=>$mark_path, 'method'=>$method));
        }
    }

    public function test_member_when_changed_default_restful_actions_and_path_names_not_specified () {
        $controller = 'messages';

        $default_path_names = AkConfig::getOption('resources_path_names', array('add' => 'add', 'edit' => 'edit'));
        AkConfig::setOption('resources_path_names', array('add'=>'nuevo', 'edit'=>'editar'));

        $add_options = array('action'=>'add', 'controller'=>$controller);
        $add_path = '/'.$controller.'/nuevo';

        $edit_options = array('action'=>'edit', 'id'=>1, 'controller'=>$controller);
        $edit_path = '/'.$controller.'/1/editar';

        $this->useRestfulRoutingMapper($controller);

        $this->assertRestfulRoutesFor($controller, array('path'=>$add_path, 'method'=>'get'));
        $this->assertRestfulRoutesFor($controller, array('path'=>$edit_path, 'method'=>'get'));
        AkConfig::setOption('resources_path_names', $default_path_names);
    }


    public function test_with_two_member_actions_with_same_method () {
        foreach (array('put', 'post') as $method){
            $member = array('mark'=>$method, 'unmark'=>$method);
            $options = array('member'=>$member);
            $this->useRestfulRoutingMapper('messages', $options);
            foreach ($member as $action){
                $action_options = array('action'=>$action, 'id'=>1, 'controller' => 'messages');
                $action_path = '/messages/1/'.$action;
                $this->assertRestfulRoutesFor('messages', $action_options);
            }
        }
    }

    public function test_array_as_collection_or_member_method_value () {
        $options = array('collection'=> array('search'=>array('get', 'post')), 'member'=> array('toggle'=>array('get', 'post')));
        $this->useRestfulRoutingMapper('messages', $options);

        $this->assertRestfulRoutesFor('messages', $options);

        foreach (array('get', 'post') as $method){
            $this->assertRecognizes(array('action'=>'search', 'controller'=>'messages'), array('path'=>'/messages/search', 'method'=>$method));
            $this->assertRecognizes(array('action'=>'toggle', 'id'=>1, 'controller'=>'messages'), array('path'=>'/messages/1/toggle', 'method'=>$method));
        }
    }

    public function test_with_add_action () {
        $controller = 'messages';

        $options = array('add'=> array('preview'=>'post'));
        $this->useRestfulRoutingMapper($controller, $options);
        $preview_options = array('action'=>'preview', 'controller' => $controller);
        $preview_path = '/'.$controller.'/add/preview';

        $this->assertRestfulRoutesFor($controller, $options);

        $this->assertRecognizes($preview_options, array('path'=>$preview_path, 'method'=>'post'));

    }

    public function test_with_add_action_with_name_prefix () {
        $options = array('add'=> array('preview'=>'post'), 'path_prefix'=>'/threads/:thread_id', 'name_prefix'=>'thread_');

        $this->useRestfulRoutingMapper('messages', $options);
        $preview_options = array('action'=>'preview', 'thread_id'=>1, 'controller' => 'messages');
        $preview_path = '/threads/1/messages/add/preview';

        $options = array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1));
        $this->assertRestfulRoutesFor('messages', $options);

        $this->assertRecognizes($preview_options, array('path'=>$preview_path, 'method'=>'post'));

        $this->assertRestfulNamedRoutesFor('messages', array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));

        $this->assertNamedRoute($preview_path, 'preview_add_thread_message_path', $preview_options);
    }

    public function test_with_formatted_add_action_with_name_prefix () {
        $options = array('add'=> array('preview'=>'post'), 'path_prefix'=>'/threads/:thread_id', 'name_prefix'=>'thread_');
        $this->useRestfulRoutingMapper('messages', $options);
        $preview_options = array('action'=>'preview', 'thread_id'=>1, 'format'=>'xml', 'controller'=>'messages');
        $preview_path = '/threads/1/messages/add/preview.xml';

        $options = array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1));
        $this->assertRestfulRoutesFor('messages', $options);
        $this->assertRecognizes($preview_options, array('path'=>$preview_path, 'method'=>'post'));

        $this->assertRestfulNamedRoutesFor('messages', array('path_prefix'=>'threads/1/', 'name_prefix'=>'thread_', 'options'=> array('thread_id'=>1)));
        $this->assertNamedRoute($preview_path, 'preview_add_thread_message_path', $preview_options);
    }


    public function test_override_add_method () {
        $this->useRestfulRoutingMapper('messages');
        $this->assertRestfulRoutesFor('messages');
        $this->assertRecognizes(array('action'=>'add', 'controller'=>'messages'), array('path'=>'/messages/add', 'method'=>'get'));

        $this->expectException('NoMatchingRouteException');
        $this->recognizeRouteForPath('/messages/add', array('method'=>'post'));

        $this->useRestfulRoutingMapper('messages', array('add'=> array('add'=>'any')));
        $this->assertRestfulRoutesFor('messages');
        $this->assertRecognizes(array('action'=>'add', 'controller'=>'messages'), array('path'=>'/messages/add', 'method'=>'post'));
        $this->assertRecognizes(array('action'=>'add', 'controller'=>'messages'), array('path'=>'/messages/add', 'method'=>'get'));
    }



    public function test_nested_restful_routes () {
        $Map = $this->getRouteMapper();
        $Map->resources('threads')->resources('messages')->resources('comments');

        $this->assertSimplyRestfulFor('threads');

        $this->assertSimplyRestfulFor('messages',
        array(
        'name_prefix'=>'thread_',
        'path_prefix'=>'threads/1/',
        'options'=> array('thread_id'=>1)
        ));
        return;
        $this->assertSimplyRestfulFor('comments',
        array(
        'name_prefix'=>'thread_message_',
        'path_prefix'=>'threads/1/messages/2/',
        'options'=> array('thread_id'=>1, 'message_id'=>'2')
        ));
    }




    public function test_nested_restful_routes_with_overwritten_defaults () {
        $Map = $this->getRouteMapper();

        $Map->resources('threads')->
        resources('messages', array('name_prefix'=>null))->
        resources('comments', array('name_prefix'=>null));

        $this->assertSimplyRestfulFor('threads');

        $this->assertSimplyRestfulFor('messages',
        array(
        'path_prefix'=>'threads/1/',
        'options'=> array('thread_id'=>1)
        ));

        $this->assertSimplyRestfulFor('comments',
        array(
        'path_prefix'=>'threads/1/messages/2/',
        'options'=> array('thread_id'=>1, 'message_id'=>'2')
        ));
    }

    public function test_shallow_nested_restful_routes () {
        $Map = $this->getRouteMapper();
        $Map->resources('threads', array('shallow'=>true))->
        resources('messages')->
        resources('comments');

        $this->assertSimplyRestfulFor('threads',
        array(
        'shallow'=>true
        ));


        $this->assertSimplyRestfulFor('messages',
        array(
        'name_prefix'=>'thread_',
        'path_prefix'=>'threads/1/',
        'shallow'=>true,
        'options'=> array('thread_id'=>1)
        ));

        return;
        $this->assertSimplyRestfulFor('comments',
        array(
        'name_prefix'=>'message_',
        'path_prefix'=>'messages/2/',
        'shallow'=>true,
        'options'=> array('message_id'=>'2')
        ));
    }

    public function test_restful_routes_dont_generate_duplicates () {
        $Router = $this->useRestfulRoutingMapper('messages');
        foreach($Router->getRoutes() as $Route) {
            foreach($Router->getRoutes() as $R) {
                if($Route === $R) continue;
                $this->assertDistinctRoutes($Route, $R);
            }
        }
    }



    public function test_should_create_singleton_resource_routes () {
        $this->useSingletonRouteMapper('account');
        $this->assertSingletonRestfulFor('account');
    }


    public function test_should_create_multiple_singleton_resource_routes () {
        $this->useSingletonRouteMapper(array('account', 'logo'));
        $this->assertSingletonRestfulFor('account');
        $this->assertSingletonRestfulFor('logo');
    }

    public function test_should_create_nested_singleton_resource_routes () {
        $Map = $this->getRouteMapper();
        $Map->resource('admin', array('controller'=>'admin'))->
        resource('account');

        $this->assertSingletonRestfulFor('admin', array('controller'=>'admin'));
        # @todo $this->assertSingletonRestfulFor('account', array('name_prefix'=>'admin_', 'path_prefix'=>'admin'));
    }

    public function test_resource_has_many_should_become_nested_resources () {
        $Map = $this->getRouteMapper();
        $Map->resources('messages', array('has_many'=>array('comments', 'authors')));

        $this->assertSimplyRestfulFor('messages');
        $this->assertSimplyRestfulFor('comments', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'options'=> array('message_id'=>1)));
        $this->assertSimplyRestfulFor('authors', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'options'=> array('message_id'=>1)));
    }


    public function test_resources_has_many_hash_should_become_nested_resources () {
        $Map = $this->getRouteMapper();
        $Map->resources('threads', array('has_many'=> array('messages'=> array('comments', 'authors'))));
        $this->assertSimplyRestfulFor('threads');
        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'thread_', 'path_prefix'=>'threads/1/', 'options'=> array('thread_id'=>1)));
        $this->assertSimplyRestfulFor('comments', array('name_prefix'=>'thread_message_', 'path_prefix'=>'threads/1/messages/1/', 'options'=> array('thread_id'=>1, 'message_id'=>1)));
        $this->assertSimplyRestfulFor('authors', array('name_prefix'=>'thread_message_', 'path_prefix'=>'threads/1/messages/1/', 'options'=> array('thread_id'=>1, 'message_id'=>1)));
    }

    public function test_shallow_resource_has_many_should_become_shallow_nested_resources () {
        $Map = $this->getRouteMapper();
        $Map->resources('messages', array('has_many'=> array('comments', 'authors'), 'shallow'=>true));

        $this->assertSimplyRestfulFor('messages', array('shallow'=>true));
        $this->assertSimplyRestfulFor('comments', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'shallow'=>true, 'options'=> array('message_id'=>1)));
        $this->assertSimplyRestfulFor('authors', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'shallow'=>true, 'options'=> array('message_id'=>1)));
    }

    public function test_resource_has_one_should_become_nested_resources () {
        $Map = $this->getRouteMapper();
        $Map->resources('messages', array('has_one'=>'logo'));
        $this->assertSimplyRestfulFor('messages');
        # @todo $this->assertSingletonRestfulFor('logo', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'options'=> array('message_id'=>1)));
    }



    public function test_shallow_resource_has_one_should_become_shallow_nested_resources () {
        $Map = $this->getRouteMapper();

        $Map->resources('messages', array('has_one'=>'logo', 'shallow'=>true));

        $this->assertSimplyRestfulFor('messages', array('shallow'=>true));
        # @todo $this->assertSingletonRestfulFor('logo', array('name_prefix'=>'message_', 'path_prefix'=>'messages/1/', 'shallow'=>true, 'options'=> array('message_id'=>1)));
    }


    public function test_singleton_resource_with_member_action () {
        foreach (array('put', 'post') as $method){
            $this->useSingletonRouteMapper('account', array('member'=> array('reset'=>$method)));
            $reset_options = array('action'=>'reset', 'controller'=>'accounts');
            $reset_path = '/account/reset';
            $this->assertSingletonRoutesFor('account');
            $this->assertRecognizes($reset_options, array('path'=>$reset_path, 'method'=>$method));

            # @todo
            $this->assertSingletonNamedRoutesFor('account');
            #$this->assertNamedRoute($reset_path, 'reset_account_path', $reset_options);
        }
    }

    public function test_singleton_resource_with_two_member_actions_with_same_method() {
        foreach (array('put', 'post') as $method){
            $this->useSingletonRouteMapper('account', array('member'=> array('reset'=>$method, 'disable'=>$method)));
            foreach (array('reset', 'disable') as $action){
                $action_options = array('action'=>$action, 'controller' => 'accounts');
                $action_path = '/account/'.$action;
                $this->assertSingletonRoutesFor('account');
                $this->assertRecognizes($action_options, array('path'=>$action_path, 'method'=>$method));
            }
        }
        $this->assertSingletonNamedRoutesFor('account');
        $this->assertNamedRoute($action_path, $action.'_account_path', $action_options);
    }



    public function test_should_nest_resources_in_singleton_resource () {
        $Map = $this->getRouteMapper();

        $Map->resource('account')->
        resources('messages');

        $this->assertSingletonRestfulFor('account');

        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'account_', 'path_prefix'=>'account/'));
    }

    public function test_should_nest_resources_in_singleton_resource_with_path_prefix () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('path_prefix'=>'/:site_id'))->
        resources('messages');

        # @todo $this->assertSingletonRestfulFor('account', array('path_prefix'=>'7/', 'options'=> array('site_id'=>7)));
        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'account_', 'path_prefix'=>'7/account/', 'options'=> array('site_id'=>7)));
    }

    public function test_should_nest_singleton_resource_in_resources () {
        $Map = $this->getRouteMapper();
        $Map->resources('threads')->
        resource('admin', array('controller'=>'admin'));

        $this->assertSimplyRestfulFor('threads');
        # @todo $this->assertSingletonRestfulFor('admin', array('controller'=>'admin', 'name_prefix'=>'thread_', 'path_prefix'=>'threads/5/', 'options'=> array('thread_id'=> 5)));
    }

    public function test_should_not_allow_invalid_head_method_for_member_routes () {
        $Map = $this->getRouteMapper();
        $this->expectException('RouteException');
        $Map->resources('messages', array('member'=> array('something'=>'head')));
    }

    public function test_should_not_allow_invalid_http_methods_for_member_routes () {
        $Map = $this->getRouteMapper();
        $this->expectException('RouteException');
        $Map->resources('messages', array('member'=> array('something'=>'invalid')));
    }


    public function test_resource_action_separator () {
        $Map = $this->getRouteMapper();

        $Map->resources('messages', array('collection'=> array('search'=>'get'), 'add'=> array('preview'=>'any'), 'name_prefix'=>'thread_', 'path_prefix'=>'/threads/:thread_id'));
        $Map->resource('account', array('member'=> array('login'=>'get'), 'add'=> array('preview'=>'any'), 'name_prefix'=>'admin_', 'path_prefix'=>'/admin'));

        $action_separator = '/';

        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'thread_', 'path_prefix'=>'threads/1/', 'options'=> array('thread_id'=>1)));
        $this->assertNamedRoute('/threads/1/messages'.$action_separator.'search', 'search_thread_messages_path', array());

        $this->assertNamedRoute('/threads/1/messages/add', 'add_thread_message_path', array());

        $this->assertNamedRoute('/threads/1/messages/add'.$action_separator.'preview', 'preview_add_thread_message_path', array());

        # @todo $this->assertSingletonRestfulFor('account', array('name_prefix'=>'admin_', 'path_prefix'=>'admin/'));

        $this->assertNamedRoute('/admin/account'.$action_separator.'login', 'login_admin_account_path', array());
        $this->assertNamedRoute('/admin/account/add', 'add_admin_account_path', array());
        $this->assertNamedRoute('/admin/account/add'.$action_separator.'preview', 'preview_add_admin_account_path', array());
    }


    public function test_add_style_named_routes_for_resource () {
        $Map = $this->getRouteMapper();

        $Map->resources('messages', array('collection'=> array('search'=>'get'), 'add'=> array('preview'=>'any'), 'name_prefix'=>'thread_', 'path_prefix'=>'/threads/:thread_id'));

        $this->assertSimplyRestfulFor('messages', array('name_prefix'=>'thread_', 'path_prefix'=>'threads/1/', 'options'=> array('thread_id'=>1)));
        $this->assertNamedRoute('/threads/1/messages/search', 'search_thread_messages_path', array());
        $this->assertNamedRoute('/threads/1/messages/add', 'add_thread_message_path', array());
        $this->assertNamedRoute('/threads/1/messages/add/preview', 'preview_add_thread_message_path', array());
    }


    public function test_add_style_named_routes_for_singleton_resource () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('member'=> array('login'=>'get'), 'add'=> array('preview'=>'any'), 'name_prefix'=>'admin_', 'path_prefix'=>'/admin'));
        # @todo $this->assertSingletonRestfulFor('account', array('name_prefix'=>'admin_', 'path_prefix'=>'admin/'));
        $this->assertNamedRoute('/admin/account/login', 'login_admin_account_path', array());
        $this->assertNamedRoute('/admin/account/add', 'add_admin_account_path', array());
        $this->assertNamedRoute('/admin/account/add/preview', 'preview_add_admin_account_path', array());
    }


    public function test_with_path_segment () {
        $this->useRestfulRoutingMapper('messages');
        $this->assertSimplyRestfulFor('messages');
        $this->assertRecognizes(array('controller'=>'messages', 'action'=>'index'), '/messages');
        $this->assertRecognizes(array('controller'=>'messages', 'action'=>'index'), '/messages/');

        $this->useRestfulRoutingMapper('messages', array('as'=>'reviews'));
        $this->assertSimplyRestfulFor('messages', array('as'=>'reviews'));
        $this->assertRecognizes(array('controller'=>'messages', 'action'=>'index'), '/reviews');
        $this->assertRecognizes(array('controller'=>'messages', 'action'=>'index'), '/reviews/');
    }


    public function test_multiple_with_path_segment_and_controller () {
        $Map = $this->getRouteMapper();

        $Map->resources('products')->
        resources('product_reviews', array('as'=>'reviews', 'controller'=>'messages'));
        $this->assertSimplyRestfulFor('product_reviews', array('controller'=>'messages', 'as'=>'reviews', 'name_prefix'=>'product_', 'path_prefix'=>'products/1/', 'options'=> array('product_id'=>1)));

        return;
        $Map->resources('tutors')->
        resources('tutor_reviews', array('as'=>'reviews', 'controller'=>'comments'));

        $this->assertSimplyRestfulFor('tutor_reviews', array('controller'=>'comments', 'as'=>'reviews', 'name_prefix'=>'tutor_', 'path_prefix'=>'tutors/1/', 'options'=> array('tutor_id'=>1)));
    }

    public function test_with_path_segment_path_prefix_requirements () {
        $expected_options = array('controller'=>'messages', 'action'=>'show', 'thread_id'=>'1.1.1', 'id'=>1);
        $this->useRestfulRoutingMapper('messages', array('as'=>'comments','path_prefix'=>'/thread/:thread_id', 'requirements'=> array('thread_id'=>'/[0-9]\.[0-9]\.[0-9]/')));
        $this->assertRecognizes($expected_options, array('path'=>'thread/1.1.1/comments/1', 'method'=>'get'));
    }

    public function test_resource_has_only_show_action () {
        $Map = $this->getRouteMapper();
        $Map->resources('products', array('only'=>'show'));
        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'));

    }



    public function test_singleton_resource_has_only_show_action () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('only'=>'show'));

        $this->assertSingletonResourceAllowedRoutes('accounts', array(), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'));
    }

    public function test_resource_does_not_have_destroy_action () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('except'=>'destroy'));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), array('index', 'add', 'create', 'show', 'edit', 'update'), 'destroy');
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), array('index', 'add', 'create', 'show', 'edit', 'update'), 'destroy');
    }

    public function test_singleton_resource_does_not_have_destroy_action () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('except'=>'destroy'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array(), array('add', 'create', 'show', 'edit', 'update'), 'destroy');
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), array('add', 'create', 'show', 'edit', 'update'), 'destroy');
    }

    public function test_resource_has_only_create_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>'create'));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), 'create', array('index', 'add', 'show', 'edit', 'update', 'destroy'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), 'create', array('index', 'add', 'show', 'edit', 'update', 'destroy'));
    }


    public function test_resource_has_only_update_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>'update'));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), 'update', array('index', 'add', 'create', 'show', 'edit', 'destroy'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), 'update', array('index', 'add', 'create', 'show', 'edit', 'destroy'));
    }

    public function test_resource_has_only_destroy_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>'destroy'));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), 'destroy', array('index', 'add', 'create', 'show', 'edit', 'update'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), 'destroy', array('index', 'add', 'create', 'show', 'edit', 'update'));
    }

    public function test_singleton_resource_has_only_create_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('only'=>'create'));

        $this->assertSingletonResourceAllowedRoutes('accounts', array(), 'create', array('add', 'show', 'edit', 'update', 'destroy'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), 'create', array('add', 'show', 'edit', 'update', 'destroy'));
    }

    public function test_singleton_resource_has_only_update_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('only'=>'update'));

        $this->assertSingletonResourceAllowedRoutes('accounts', array(), 'update', array('add', 'create', 'show', 'edit', 'destroy'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), 'update', array('add', 'create', 'show', 'edit', 'destroy'));

    }
    public function test_singleton_resource_has_only_destroy_action_and_named_route () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('only'=>'destroy'));

        $this->assertSingletonResourceAllowedRoutes('accounts', array(), 'destroy', array('add', 'create', 'show', 'edit', 'update'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), 'destroy', array('add', 'create', 'show', 'edit', 'update'));
    }


    public function test_resource_has_only_collection_action () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('except'=>'all', 'collection'=> array('sale'=>'get')));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), array(), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), array(), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'));

        $this->assertRecognizes(array('controller'=>'products', 'action'=>'sale'), array('path'=>'products/sale', 'method'=>'get'));
        $this->assertRecognizes(array('controller'=>'products', 'action'=>'sale', 'format'=>'xml'), array('path'=>'products/sale.xml', 'method'=>'get'));
    }

    public function test_resource_has_only_member_action () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('except'=>'all', 'member'=> array('preview'=>'get')));

        $this->assertResourceAllowedRoutes('products', array(), array('id'=>1), array(), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'));
        $this->assertResourceAllowedRoutes('products', array('format'=>'xml'), array('id'=>1), array(), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'));

        $this->assertRecognizes(array('controller'=>'products', 'action'=>'preview', 'id'=>1), array('path'=>'products/1/preview', 'method'=>'get'));
        $this->assertRecognizes(array('controller'=>'products', 'action'=>'preview', 'id'=>1, 'format'=>'xml'), array('path'=>'products/1/preview.xml', 'method'=>'get'));
    }

    public function test_singleton_resource_has_only_member_action () {
        $Map = $this->getRouteMapper();

        $Map->resource('account', array('except'=>'all', 'member'=> array('signup'=>'get')));

        $this->assertSingletonResourceAllowedRoutes('accounts', array(), array(), array('add', 'create', 'show', 'edit', 'update', 'destroy'));
        $this->assertSingletonResourceAllowedRoutes('accounts', array('format'=>'xml'), array(), array('add', 'create', 'show', 'edit', 'update', 'destroy'));

        $this->assertRecognizes(array('controller'=>'accounts', 'action'=>'signup'), array('path'=>'account/signup', 'method'=>'get'));
        $this->assertRecognizes(array('controller'=>'accounts', 'action'=>'signup', 'format'=>'xml'), array('path'=>'account/signup.xml', 'method'=>'get'));
    }


    public function test_nested_resource_has_only_show_and_member_action () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>array('index', 'show')))->
        resources('images', array('member'=> array('thumbnail'=>'get'), 'only'=>'show'));

        $this->assertResourceAllowedRoutes('images', array('product_id'=>1), array('id'=>'2'), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'), 'products/1/images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1, 'format'=>'xml'), array('id'=>'2'), 'show', array('index', 'add', 'create', 'edit', 'update', 'destroy'), 'products/1/images');

        $this->assertRecognizes(array('controller'=>'images', 'action'=>'thumbnail', 'product_id'=>1, 'id'=>'2'), array('path'=>'products/1/images/2/thumbnail', 'method'=>'get'));
        $this->assertRecognizes(array('controller'=>'images', 'action'=>'thumbnail', 'product_id'=>1, 'id'=>'2', 'format'=>'jpg'), array('path'=>'products/1/images/2/thumbnail.jpg', 'method'=>'get'));
    }

    public function test_nested_resource_does_not_inherit_only_option () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>'show'))->
        resources('images', array('except'=>'destroy'));

        $this->assertResourceAllowedRoutes('images', array('product_id'=>1), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update'), 'destroy', 'products/1/images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1, 'format'=>'xml'), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update'), 'destroy', 'products/1/images');
    }


    public function test_nested_resource_does_not_inherit_only_option_by_default () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('only'=>'show'))->
        resources('images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update', 'destory'), array(), 'products/1/images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1, 'format'=>'xml'), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'), array(), 'products/1/images');

    }

    public function test_nested_resource_does_not_inherit_except_option () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('except'=>'show'))->
        resources('images', array('only'=>'destroy'));

        $this->assertResourceAllowedRoutes('images', array('product_id'=>1), array('id'=>'2'), 'destroy', array('index', 'add', 'create', 'show', 'edit', 'update'), 'products/1/images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1, 'format'=>'xml'), array('id'=>'2'), 'destroy', array('index', 'add', 'create', 'show', 'edit', 'update'), 'products/1/images');
    }

    public function test_nested_resource_does_not_inherit_except_option_by_default () {
        $Map = $this->getRouteMapper();

        $Map->resources('products', array('except'=>'show'))->
        resources('images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'), array(), 'products/1/images');
        $this->assertResourceAllowedRoutes('images', array('product_id'=>1, 'format'=>'xml'), array('id'=>'2'), array('index', 'add', 'create', 'show', 'edit', 'update', 'destroy'), array(), 'products/1/images');
    }

    /**/

    // ASSERTIONS

    // runs assertRestfulRoutesFor and assertRestfulNamedRoutes for on the controller_name and options
    protected function assertSimplyRestfulFor($controller_name, $options = array()){
        $this->assertRestfulRoutesFor($controller_name, $options);
        $this->assertRestfulNamedRoutesFor($controller_name, null, $options);
    }

    protected function assertResourceMethods ($expected, $Resource, $action_method, $method){
        $this->assertEqual($expected, $Resource->{$action_method.'_methods'}[$method]);
    }


    public function assertRestfulRoutesFor($controller_name, $options = array()) {
        $options['options'] = isset($options['options']) ? $options['options'] : array();
        $options['options']['controller'] = isset($options['options']['controller']) ? $options['controller'] : $controller_name;

        if(!empty($options['shallow'])){
            $options['shallow_options'] = isset($options['shallow_options']) ? $options['shallow_options'] : array();
            $options['shallow_options']['controller'] = $options['options']['controller'];
        }else{
            $options['shallow_options'] = $options['options'];
        }

        $add_action = AkResource::getResourcePathNameFor('add', 'add');
        $edit_action = AkResource::getResourcePathNameFor('edit', 'edit');

        if(!empty($options['path_names'])){
            if(isset($options['path_names']['add'])){
                $add_action = $options['path_names']['add'];
            }
            if(isset($options['path_names']['edit'])){
                $edit_action = $options['path_names']['edit'];
            }
        }

        $options['path_prefix'] = isset($options['path_prefix']) ? $options['path_prefix'] : '';

        $path = isset($options['as']) ? $options['as'] : $controller_name;
        $collection_path = '/'.$options['path_prefix'].$path;
        $shallow_path = '/'.(isset($options['shallow']) ? @$options['module'] : $options['path_prefix']).$path;
        $member_path = $shallow_path.'/1';
        $add_path = $collection_path.'/'.$add_action;
        $edit_member_path = $member_path.'/'.$edit_action;
        $formatted_edit_member_path = $member_path.'/'.$edit_action.'.xml';

        if(!empty($options['options'])){
            $this->assertRouting($collection_path, array_merge($options['options'], array('action'=>'index')));
            $this->assertRouting($add_path, array_merge($options['options'], array('action'=>'add')));
            $this->assertRouting($collection_path.'.xml', array_merge($options['options'], array('action'=>'index', 'format'=>'xml')));
            $this->assertRouting($add_path.'.xml', array_merge($options['options'], array('action'=>'add', 'format'=>'xml')));
        }

        if(!empty($options['shallow_options'])){
            $this->assertRouting($member_path, array_merge($options['shallow_options'], array('action'=>'show', 'id'=>1)));
            $this->assertRouting($edit_member_path, array_merge($options['shallow_options'], array('action'=>'edit', 'id'=>1)));
            $this->assertRouting($member_path.'.xml', array_merge($options['shallow_options'], array('action'=>'show', 'id'=>1, 'format'=>'xml')));
            $this->assertRouting($formatted_edit_member_path, array_merge($options['shallow_options'], array('action'=>'edit', 'id'=>1, 'format'=>'xml')));
        }

        $this->assertRecognizes(array_merge($options['options'], array('action'=>'index', 'controller' => $controller_name)), array('path'=>$collection_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'add', 'controller' => $controller_name)), array('path'=>$add_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'create', 'controller' => $controller_name)), array('path'=>$collection_path, 'method'=>'post'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'show', 'controller' => $controller_name, 'id'=>1)), array('path'=>$member_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'edit', 'controller' => $controller_name, 'id'=>1)), array('path'=>$edit_member_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'update', 'controller' => $controller_name, 'id'=>1)), array('path'=>$member_path, 'method'=>'put'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'destroy', 'controller' => $controller_name,'id'=>1)), array('path'=>$member_path, 'method'=>'delete'));

        $this->assertRecognizes(array_merge($options['options'], array('action'=>'index', 'controller' => $controller_name, 'format'=>'xml')), array('path'=>$collection_path.'.xml', 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'add', 'controller' => $controller_name, 'format'=>'xml')), array('path'=>$add_path.'.xml', 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'create', 'controller' => $controller_name, 'format'=>'xml')), array('path'=>$collection_path.'.xml', 'method'=>'post'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'show', 'controller' => $controller_name, 'id'=>1, 'format'=>'xml')), array('path'=>$member_path.'.xml', 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'edit', 'controller' => $controller_name, 'id'=>1, 'format'=>'xml')), array('path'=>$formatted_edit_member_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'update', 'controller' => $controller_name, 'id'=>1, 'format'=>'xml')), array('path'=>$member_path.'.xml', 'method'=>'put'));
        $this->assertRecognizes(array_merge($options['shallow_options'], array('action'=>'destroy', 'controller' => $controller_name, 'id'=>1, 'format'=>'xml')), array('path'=>$member_path.'.xml', 'method'=>'delete'));

    }

    // test named routes like foo_path and foos_path map to the correct options.
    public function assertRestfulNamedRoutesFor($controller_name, $singular_name = null, $options = array()) {

        if(is_array($singular_name)){
            $options = $singular_name;
            $singular_name = null;
        }
        $singular_name = !empty($singular_name) ? $singular_name : AkInflector::singularize($controller_name);


        $options['options'] = isset($options['options']) ? $options['options'] : array();
        $options['options']['controller'] = isset($options['controller']) ? $options['controller'] : $controller_name;

        if(!empty($options['shallow'])){
            $options['shallow_options'] = empty($options['shallow_options']) ? array() : $options['shallow_options'];
            $options['shallow_options']['controller'] = $options['options']['controller'];
        }else{
            $options['shallow_options'] = $options['options'];
        }
        $options['path_prefix'] = isset($options['path_prefix']) ? $options['path_prefix'] : '';
        $options['name_prefix'] = isset($options['name_prefix']) ? $options['name_prefix'] : '';
        $options['shallow'] = isset($options['shallow']) ? $options['shallow'] : '';

        $controller_class_name = AkInflector::camelize($options['options']['controller']).'Controller';

        $path = isset($options['as']) ? $options['as'] : $controller_name;

        $this->recognizeRouteForPath($options['path_prefix'].$path);

        $this->Controller = new $controller_class_name();
        $this->Request = new AkTestRequest();
        $this->Response = new AkTestResponse();

        Ak::deleteAndGetValue($options['options'], 'action');

        $shallow_path = '/'.((!empty($options['shallow']) ? @$options['module'] : $options['path_prefix']).$path);
        $full_path = '/'.$options['path_prefix'].$path;
        $name_prefix = $options['name_prefix'];
        $shallow_prefix = $options['shallow'] ? str_replace('/', '_', @$options['module']) : $options['name_prefix'];

        $add_action = 'add';
        $edit_action = 'edit';
        if(!empty($options['path_names'])){
            $add_action = !empty($options['path_names']['add']) ? $options['path_names']['add'] : 'add';
            $edit_action = !empty($options['path_names']['edit']) ? $options['path_names']['edit'] : 'edit';
        }

        unset($options['options']['controller']);
        unset($options['shallow_options']['controller']);

        $this->assertNamedRoute($full_path, $name_prefix.$controller_name.'_path', $options['options']);
        $this->assertNamedRoute($full_path.'.xml', $name_prefix.$controller_name.'_path', array_merge($options['options'],array('format'=>'xml')));
        $this->assertNamedRoute($shallow_path.'/1', $shallow_prefix.$singular_name.'_path', array_merge($options['shallow_options'], array('id'=>1)));
        $this->assertNamedRoute($shallow_path.'/1.xml', $shallow_prefix.$singular_name.'_path', array_merge($options['shallow_options'], array('id'=>1, 'format'=>'xml')));

        $this->assertNamedRoute($full_path.'/'.$add_action, 'add_'.$name_prefix.$singular_name.'_path', $options['options']);
        $this->assertNamedRoute($full_path.'/'.$add_action.'.xml', 'add_'.$name_prefix.$singular_name.'_path', array_merge(array('format'=>'xml'), $options['options']));
        $this->assertNamedRoute($shallow_path.'/1/'.$edit_action, 'edit_'.$shallow_prefix.$singular_name.'_path', array_merge(array('id'=>1), $options['shallow_options']));
        $this->assertNamedRoute($shallow_path.'/1/'.$edit_action.'.xml', 'edit_'.$shallow_prefix.$singular_name.'_path', array_merge(array('id'=>1, 'format'=>'xml'), $options['shallow_options']));
    }

    public function assertNamedRoute ($expected, $route, $options){
        if(!function_exists($route)){
            $this->fail('Could not find helper function '.$route.' on named route: '.$route.'('.json_encode($options).')');
        }else{
            $actual = $route($options);
            $this->assertEqual($expected, $actual, 'Error on route: '.$route.'('.json_encode($options).') "'.$expected.'" != "'.$actual.'"');
        }
    }

    public function assertDistinctRoutes($r1, $r2){
        if($r1->getConditions() == $r2->getConditions() && $r1->getRequirements() == $r2->getRequirements()){
            if(join('',$r1->getSegments()) == join('', $r2->getSegments())){
                $this->fail('Duplicated routes');
            }else{
                $this->pass();
            }
        }
    }

    public function assertSingletonRestfulFor($singleton_name, $options = array()){
        $this->assertSingletonRoutesFor($singleton_name, $options);
        $this->assertSingletonNamedRoutesFor($singleton_name, $options);
    }

    public function assertSingletonRoutesFor($singleton_name, $options = array()){

        $options['options'] = empty($options['options']) ? array() : $options['options'];
        $options['options']['controller'] = isset($options['controller']) ? Ak::deleteAndGetValue($options, 'controller') : AkInflector::pluralize($singleton_name);

        $full_path = '/'.rtrim(strlen(@$options['path_prefix'].@$options['as']) == 0 ? $singleton_name : @$options['path_prefix'].@$options['as'], '/');
        $add_path = $full_path.'/add';
        $edit_path = $full_path.'/edit';
        $formatted_edit_path = $full_path.'/edit.xml';

        if(!empty($options['options'])){
            $this->assertRouting($full_path, array_merge($options['options'], array('action'=>'show')));
            $this->assertRouting($add_path, array_merge($options['options'], array('action'=>'add')));
            $this->assertRouting($edit_path, array_merge($options['options'], array('action'=>'edit')));
            $this->assertRouting($full_path.'.xml', array_merge($options['options'], array('action'=>'show', 'format'=>'xml')));
            $this->assertRouting($add_path.'.xml', array_merge($options['options'], array('action'=>'add', 'format'=>'xml')));
            $this->assertRouting($formatted_edit_path, array_merge($options['options'], array('action'=>'edit', 'format'=>'xml')));
        }

        $this->assertRecognizes(array_merge($options['options'], array('action'=>'show')), array('path'=>$full_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'add')), array('path'=>$add_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'edit')), array('path'=>$edit_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'create')), array('path'=>$full_path, 'method'=>'post'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'update')), array('path'=>$full_path, 'method'=>'put'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'destroy')), array('path'=>$full_path, 'method'=>'delete'));

        $this->assertRecognizes(array_merge($options['options'], array('action'=>'show', 'format'=>'xml')), array('path'=>$full_path.'.xml', 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'add', 'format'=>'xml')), array('path'=>$add_path.'.xml', 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'edit', 'format'=>'xml')), array('path'=>$formatted_edit_path, 'method'=>'get'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'create', 'format'=>'xml')), array('path'=>$full_path.'.xml', 'method'=>'post'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'update', 'format'=>'xml')), array('path'=>$full_path.'.xml', 'method'=>'put'));
        $this->assertRecognizes(array_merge($options['options'], array('action'=>'destroy', 'format'=>'xml')), array('path'=>$full_path.'.xml', 'method'=>'delete'));

    }

    public function assertSingletonNamedRoutesFor($singleton_name, $options = array()){

        $options['options'] = empty($options['options']) ? array() : $options['options'];
        $options['options']['controller'] = !empty($options['controller']) ? Ak::deleteAndGetValue($options, 'controller') : AkInflector::pluralize($singleton_name);

        $controller_class_name = AkInflector::camelize($options['options']['controller']).'Controller';

        $this->recognizeRouteForPath($singleton_name);

        $this->Controller = new $controller_class_name();
        $this->Request = new AkTestRequest();
        $this->Response = new AkTestResponse();

        Ak::deleteAndGetValue($options['options'], 'action');
        $path = isset($options['as']) ? $options['as'] : $singleton_name;
        $name_prefix = @$options['path_prefix'];
        $full_path = '/'.$name_prefix.$path;

        $this->assertNamedRoute($full_path, $name_prefix.$singleton_name.'_path', $options['options']);
        $this->assertNamedRoute($full_path.'.xml', $name_prefix.$singleton_name.'_path', array_merge($options['options'], array('format'=>'xml')));

        $this->assertNamedRoute($full_path.'/add', 'add_'.$name_prefix.$singleton_name.'_path', $options['options']);
        $this->assertNamedRoute($full_path.'/add.xml', 'add_'.$name_prefix.$singleton_name.'_path', array_merge($options['options'], array('format'=>'xml')));
        $this->assertNamedRoute($full_path.'/edit', 'edit_'.$name_prefix.$singleton_name.'_path', $options['options']);
        $this->assertNamedRoute($full_path.'/edit.xml', 'edit_'.$name_prefix.$singleton_name.'_path', array_merge($options['options'], array('format'=>'xml')));
    }

    public function assertResourceAllowedRoutes($controller, $options, $shallow_options, $allowed, $not_allowed, $path = null) {
        $path = empty($path) ? $controller : $path;
        $shallow_path = $path.'/'.$shallow_options['id'];

        $format = isset($options['format']) ? '.'.$options['format'] : '';

        $options = array_merge(array('controller'=>$controller), $options);
        $shallow_options = array_merge($options, $shallow_options);

        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'index', $path.$format, 'get');
        return;
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'add', $path.'/add'.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'create', $path.$format, 'post');
        $this->assertWhetherAllowed($allowed, $not_allowed, $shallow_options, 'show', $shallow_path.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $shallow_options, 'edit', $shallow_path.'/edit'.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $shallow_options, 'update', $shallow_path.$format, 'put');
        $this->assertWhetherAllowed($allowed, $not_allowed, $shallow_options, 'destroy', $shallow_path.$format, 'delete');
    }


    public function assertSingletonResourceAllowedRoutes ($controller, $options, $allowed, $not_allowed, $path = null){
        $path = empty($path) ? AkInflector::singularize($controller) : $path;
        $format = isset($options['format']) ? '.'.$options['format'] : '';
        $options = array_merge(array('controller'=>$controller), $options);

        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'add', $path.'/add'.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'create', $path.$format, 'post');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'show', $path.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'edit', $path.'/edit'.$format, 'get');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'update', $path.$format, 'put');
        $this->assertWhetherAllowed($allowed, $not_allowed, $options, 'destroy', $path.$format, 'delete');
    }

    public function assertWhetherAllowed ($allowed, $not_allowed, $options, $action, $path, $method){
        $options = array_merge(array('action'=>$action), $options);
        $path_options = array('path'=>$path, 'method'=>$method);

        if(in_array($action, Ak::toArray($allowed))){
            $this->assertRecognizes($options, $path_options);
        }elseif(in_array($action, Ak::toArray($not_allowed))){
            $this->assertNotRecognizes($options, $path_options);
        }
    }

    public function assertNotRecognizes ($expected_options, $path){
        try{
            if($this->assertRecognizes($expected_options, $path, array(), null, false)){
                $this->fail('Recognized unexpected path "'.(is_array($path)?@$path['path']:$path).'" with options '.json_encode($expected_options));
            }else{
                throw new Exception();
            }
        }catch(Exception $e){
            $this->pass();
        }
    }


    // HELPER METHODS

    private function &getRouteMapper(){
        $Map = new AkRouter();
        $this->nextAssertionUsingRouter($Map);
        return $Map;
    }

    /**
 * @returns AkRouter
 */
    private function useRestfulRoutingMapper() {
        $args = func_get_args();
        $Map = $this->getRouteMapper();
        call_user_func_array(array($Map, 'resources'), $args);
        $this->nextAssertionUsingRouter($Map);

        return $Map;
    }

    protected function useSingletonRouteMapper () {
        $args = func_get_args();
        $Map = $this->getRouteMapper();
        call_user_func_array(array($Map, 'resource'), $args);
        $this->nextAssertionUsingRouter($Map);
        return $Map;
    }


    /**
     * @todo implement module routing
     */

}

ak_test_case('ResourcesTestCase');
