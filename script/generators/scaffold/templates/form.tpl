<?='<?='?>$active_record_helper->error_messages_for('<?=$singular_name?>');?>

<? if(empty($content_columns)) : ?>
<?='<?='?>$active_record_helper->all_input_tags($<?=$model_name?>, '<?=$singular_name?>', array()) ?>
<? else : 
        foreach ($content_columns as $column=>$details){
            echo "
    <p>
        <label for=\"{$singular_name}_{$column}\">_{".
            AkInflector::humanize($details['name']).
            "}</label><br />
        <?=\$active_record_helper->input('$singular_name', '$column')?>
    </p>
";
        }
endif;

?>
