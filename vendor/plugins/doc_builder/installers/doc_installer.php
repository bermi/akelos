<?php

class DocInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('components', "
            id,
            name,
            description,
            parent_id,
            created_at,
            updated_at
        ");

        $this->createTable('categories', "
            id,
            name,
            description,
            created_at,
            updated_at
        ");

        $this->createTable('related_categories', "
            id,
            category_id,
            related_category_id
        ");

        $this->createTable('files', "
            id,
            component_id,
            path,
            body,
            hash,
            has_been_analyzed,
            created_at,
            updated_at
        ");

        $this->createTable('klasses', "
            id,
            name,
            description,
            file_id,
            component_id,
            parent_id,
            created_at,
            updated_at
            ");

        $this->createTable('methods', "
            id,
            name,
            description,
            klass_id,
            category_id,
            is_private,
            returns_reference bool default 0,
            position int,
            created_at,
            updated_at
            ");

        $this->createTable('parameters', "
            id,
            name,
            method_id,
            data_type_id,
            default_value,
            description,
            is_reference,
            position,
            created_at,
            updated_at
            ");

        $this->createTable('data_types', '
        id,
        name
        ');


        $this->createTable('examples', "
            id,
            name,
            method_id,
            component_id,
            category_id,
            body,
            created_at,
            updated_at
            ");

        $this->createTable('comments', "
            id,
            body,
            user,
            email,
            method_id,
            ip_address,
            spam_score integer default 0,
            is_published bool default 0,
            created_at,
            updated_at
            ");
    }

    function down_1()
    {
            $this->dropTables('components,files,methods,klasses,parameters,examples,comments,categories,related_categories,data_types');
    }

}