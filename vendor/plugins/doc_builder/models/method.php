<?php

class Method extends AkActiveRecord
{
    public $has_many = array('parameters');//,'examples', array('comments'=>array('condition'=>'is_published = 1')));
    public $belongs_to = array('akelos_class', 'category');
    public $acts_as = array('list' => array('scope'=>'akelos_class_id'));


    public function &updateMethodDetails(&$Class, $method_name, $method_details, &$SourceAnalyzer)
    {
        $Method = $this->findOrCreateBy('name AND akelos_class_id', $method_name, $Class->getId());

        $SourceAnalyzer->log(($Method->has_been_created ? 'Adding ' : 'Updating').' method '.$method_name);

        $Method->setAttributes(array(
        'description' => $method_details['doc'],
        'is_private' => $method_details['is_private'],
        'returns_reference' => $method_details['returns_reference'],
        ));

        $Method->save();

        $ParameterInstance = new Parameter();
        foreach ($method_details['params'] as $parameter_details){
            $ParameterInstance->updateParameterDetails($Method, $parameter_details);
        }

        if($method_details['category'] != 'none'){
            $Category = new Category();
            $Category->updateCategoryDetails($Method, $method_details, $SourceAnalyzer);
        }

        // parameters doc_metadata  category_id

        return $Method;
    }
}
