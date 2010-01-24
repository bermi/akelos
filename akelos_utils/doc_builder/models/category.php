<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class Category extends AkActiveRecord
{
    public $has_many = array('methods');
    public $habtm = array('related_categories'=>array(
    'association_foreign_key'=>'related_category_id',
    'join_table'=>'related_categories',
    'join_class_name'=>'RelatedCategory',
    'unique' => true
    ));

    public function validate()
    {
        $this->validatesUniquenessOf('name');
    }

    public function &updateCategoryDetails(&$Method, $method_details, &$SourceAnalyzer)
    {
        static $updated_categories = array();
        if($method_details['category'] != 'none' && !in_array($method_details['category'], $updated_categories)){
            $Category = $this->findOrCreateBy('name', $method_details['category']);
            $Category->setAttributes(array(
                'description' => $method_details['category_details']
                ));

            $Category->save();
            $Method->category->assign($Category);

            $updated_categories[] = $method_details['category'];

            if(false && !empty($method_details['category_relations'])){
                $RelatedCategories = array();
                foreach($method_details['category_relations'] as $category_name){
                    $RelatedCategories[] = $this->findOrCreateBy('name', $category_name);
                }
                $Category->related_category->set($RelatedCategories);
                $Category->save();
            }
        }

        // parameters doc_metadata  category_id

        return $Category;
    }
}
