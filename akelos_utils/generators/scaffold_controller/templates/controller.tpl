<?php echo "<?php\n"; ?>

class <?php echo $controller_class_name; ?>Controller extends ApplicationController {
<?php if(empty($options['singleton'])): ?>

    // GET /<?php echo $table_name."\n"; ?>
    // GET /<?php echo $table_name; ?>.xml
    public function index() {
        $this-><?php echo $table_name; ?> = $this-><?php echo $class_name; ?>->all();

        switch( $this->respondTo() ) {
            case 'html': break; // index.html.tpl
            case 'xml' : $this->render(array('xml' => $this-><?php echo $table_name; ?> ) );
        }
    }
<?php endif; ?>

    // GET /<?php echo $table_name; ?>/1
    // GET /<?php echo $table_name; ?>/1.xml
    public function show() {
        $this-><?php echo $file_name ; ?> = $this-><?php echo $class_name; ?>->find($this->params['id']);

        switch($this->respondTo()){
            case 'html': break; // show.html.tpl
            case 'xml' : $this->render(array('xml' => $this-><?php echo $file_name; ?> ) );
        }
    }

    // GET /<?php echo $table_name; ?>/add
    // GET /<?php echo $table_name; ?>/add.xml
    public function add(){
        $this-><?php echo $file_name ; ?> = new <?php echo $class_name; ?>;

        switch( $this->respondTo() ) {
            case 'html': break; // add.html.tpl
            case 'xml' : $this->render(array('xml' => $this-><?php echo $file_name; ?> ) );
        }
    }

    // GET /<?php echo $table_name; ?>/1/edit
    public function edit() {
        $this-><?php echo $file_name ; ?> = $this-><?php echo $class_name; ?>->find($this->params['id']);
    }

    // POST /<?php echo $table_name."\n"; ?>
    // POST /<?php echo $table_name; ?>.xml
    public function create() {
        $this-><?php echo $file_name ; ?> = new <?php echo $class_name; ?>($this->params['<?php echo $file_name ; ?>']);

        $format = $this->respondTo();
        if( $this-><?php echo $file_name; ?>->save() ) {
            if('html' == $format){
                $this->redirectTo($this-><?php echo $file_name; ?>, array('notice' => $this->t('<?php echo AkInflector::humanize($singular_name); ?> was successfully created.')));
            }elseif('xml' == $format){
                $this->render(array('xml' => $this-><?php echo $file_name; ?>, 'status' => 'created', 'location' => $this-><?php echo $file_name; ?> ));
            }
        }else{
            if('html' == $format){ 
                $this->renderAction('add');
            }elseif('xml' == $format){
                $this->render(array('xml' => $this-><?php echo $class_name; ?>->getErrors(), 'status' => 'unprocessable_entity'));
            }
        }
    }

    // PUT /<?php echo $table_name; ?>/1
    // PUT /<?php echo $table_name; ?>/1.xml
    public function update() {
        $this-><?php echo $file_name ; ?> = $this-><?php echo $class_name; ?>->find($this->params['id']);

        $format = $this->respondTo();
        if($this-><?php echo $file_name; ?>->updateAttributes($this->params['<?php echo $file_name ; ?>'])){
            if('html' == $format){
                $this->redirectTo($this-><?php echo $file_name; ?>, array('notice' => $this->t('<?php echo AkInflector::humanize($singular_name); ?> was successfully updated.')));
            }elseif('xml' == $format){
                $this->head('ok');
            }
        }else{
            if('html' == $format){ 
                $this->renderAction('edit');
            }elseif('xml' == $format){
                $this->render(array('xml' => $this-><?php echo $class_name; ?>->getErrors(), 'status' => 'unprocessable_entity'));
            }
        }
    }

    // DELETE /<?php echo $table_name; ?>/1
    // DELETE /<?php echo $table_name; ?>/1.xml
    public function destroy() {
        $this-><?php echo $file_name ; ?> = $this-><?php echo $class_name; ?>->find($this->params['id']);
        $this-><?php echo $file_name; ?>->destroy();

        switch( $this->respondTo() ) {
            case 'html': $this->redirectTo(<?php echo $table_name; ?>_url(), array('notice' => $this->t('<?php echo AkInflector::humanize($singular_name); ?> was successfully destroyed.'))); break;
            case 'xml' : $this->head('ok');
        }
    }
}
