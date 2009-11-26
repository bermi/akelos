<?php  echo '<?php  echo '?>$active_record_helper->error_messages_for('<?php  echo $singular_name?>');?>

<?php  if(empty($content_columns)) : ?>
<?php  echo '<?php  echo '?>$active_record_helper->all_input_tags($<?php  echo $model_name?>, '<?php  echo $singular_name?>', array()) ?>
<?php  else : 
        foreach ($content_columns as $column=>$details){
            if($column == 'id'){
                continue;
            }
            echo "
    <p>
        <label for=\"{$singular_name}_{$column}\">_{".
            AkInflector::humanize($details['name']).
            "}</label><br />
        <?php  echo \$active_record_helper->input('$singular_name', '$column')?>
    </p>
";
        }
endif;

?>
