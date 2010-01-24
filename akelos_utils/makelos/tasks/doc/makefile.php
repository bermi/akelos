<?php
/**
./makelos doc:app                   # Build the app documetation Files into docs/app/api
./makelos doc:plugins               # Generate documation for all installed plugins in docs/plugins
./makelos doc:akelos                # Build the akelos documentation files into docs/akelos/api
./makelos doc:website               # Add a new controller at /docs to browse avaliable documentation
./makelos doc:website:remove        # Removed the files added by ./makelos doc:website
*/

makelos_task('doc:akelos', array(
'description' => 'Build the akelos HTML Files'
));

makelos_task('doc:website', array(
'description' => 'Creates a website for browsing your docs at app/controllers/docs_controller.php'
));

makelos_task('doc:website:remove', array(
'description' => 'Removes the files added by ./makelos doc:website'
));

/*
makelos_task('doc:extract_metadata', array(
    'description' => 'Extracts metadata from source code files to generate the documentation'
));

*/