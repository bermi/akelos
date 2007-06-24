<?php  echo '<%'?>= error_messages_for '<?php  echo $singular_name?>' %>

<?php  if(empty($content_columns)) : ?>
<?php  echo '<%'?>= all_input_tags <?php  echo $model_name?>, '<?php  echo $singular_name?>', {} %>
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
        <%= input '$singular_name', '$column' %>
    </p>
";
        }
endif;

?>