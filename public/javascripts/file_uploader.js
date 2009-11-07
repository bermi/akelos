/*  FileUploader for the Prototype JavaScript framework, version 0.1
 *  (c) 2006 Bermi Ferrer <info -a-t bermi org>
 *
 *  FileUploader is freely distributable under the terms of an MIT-style license.
 *
/*--------------------------------------------------------------------------*/


var WindowOpenener = {

    popup : function(owner,url,name,options){
        var opened_page=this._open_page(top,url,name,options);
        if(!opened_page||opened_page.closed||!opened_page.focus){
            opened_page=this._open_page(owner,url,name,options);
        }
        if(!opened_page||opened_page.closed||!opened_page.focus){
            alert("Grrr! A popup blocker may be preventing you from opening the page. If you have a popup blocker, try disabling it to open the window.");
        }else{
            opened_page.focus();
        }
        return opened_page;
    },
    _open_page : function(owner,url,name,options){
        var opened_page;
        if(options){
            opened_page=owner.open(url,name,options);
        }else if(name){
            opened_page=owner.open(url,name);
        }else{
            opened_page=owner.open(url);
        }
        return opened_page;
    }
}


//var _file_uploader_fields = new Array();
var FileUploader = {
    version: '0.1',

    _file_uploader_fields : new Array(),
    _file_uploader_current_uploads : $H(),
    _file_uploader_form : null,
    _file_counter : new Array(),

    start : function(form, options) {
        var defaultOptions = {
            select_message: 'Select a file to upload',
            add_message: 'Add another file',
            limit_message: 'Maximum upload files reached',
            remove_message: 'remove',
            max_files: 0,
            partial: false,
            target: $(form).target,
            action: $(form).action,
            form: $(form)
        }
        this.options = Object.extend(Object.extend({},defaultOptions), options || {});
        this.nodes = new Array();

        this._file_uploader_form = $(form);

        $A($(form).getElementsByTagName('input')).each(function(node){
            if(node.type=='file'){
                this.enable_field(node);
            }
        }.bind(this));

        if(this.options['partial']){
            this._insert_target_iframe($(form));
        }
    },

    enable_field : function(form_field){
        var id = form_field.id;
        var file_field = form_field.cloneNode(false);
        var options = this.get_options(file_field.id);
        file_field.title = options['select_message'];

        this.create_file_box(id);
        this._file_uploader_fields[id] = file_field;
        this._file_uploader_fields[id]['options'] = options;

        options['form'].removeChild(form_field);

        $(id+'_message_box').innerHTML = '<span style="cursor:pointer;text-decoration:underline" class="select_message" onclick="FileUploader.add_file_upload_field(\''+id+'\')" id="file_uploader_select_message_'+id+'">'+options['select_message']+'</span>';
    },

    _has_empty_upload_fields : function(id){
        if(!this._file_counter[id]){
            return false;
        }
        var has_empty_upload_fields = false;
        new ObjectRange(1,this._file_counter[id]).each(function(count){
            var file_field_id = this._file_uploader_fields[id].id+'_'+count;
            if(!$(file_field_id) == false && $(file_field_id).value == ''){
                has_empty_upload_fields = true;
            }
        }.bind(this));

        return has_empty_upload_fields;
    },


    add_file_upload_field : function(id){

        if(!this._has_empty_upload_fields(id)){
            var options = this._file_uploader_fields[id]['options'];
            this._file_counter[id] = this._file_counter[id] || 0;
            this._file_counter[id]++;
            var file_input_id = 'file_uploader_'+id+'_'+this._file_counter[id];

            var new_file_field = this._file_uploader_fields[id].cloneNode(false);
            new_file_field.id += '_'+this._file_counter[id];

            $('file_uploader_select_message_'+id).hide();

            var add_message  = '';

            if(!$('file_uploader_add_message_'+id) == false){
                Element.remove($('file_uploader_add_message_'+id));
            }
            if(options.max_files == 0 || options.max_files > this._file_counter[id]){
                add_message = '<div style="cursor:pointer;text-decoration:underline;" class="select_message" onclick="FileUploader.add_file_upload_field(\''+id+'\')" id="file_uploader_add_message_'+id+'">'+options.add_message+'</div>';
            }

            var remove_link = '<span style="cursor:pointer;text-decoration:underline" class="remove_message" onclick="FileUploader.remove_upload_field(\''+id+'\',\''+file_input_id+'\')">'+options.remove_message+'</span>';

            new Insertion.Bottom($(id+'_message_box'), '<div id="'+file_input_id+'"><span id="'+file_input_id+'_box"></span>'+remove_link+'</div>'+add_message);
            $(file_input_id).show();

            $(file_input_id+'_box').appendChild(new_file_field);

            if(options.partial){
                $(new_file_field.id).onchange = this.onChange.bind(this, id);
            }
        }

    },

    remove_upload_field : function(id, input_box_id){

        var options = this._file_uploader_fields[id]['options'];

        this._file_counter[id]--;

        if(!$('file_uploader_add_message_'+id) == false){
            Element.remove($('file_uploader_add_message_'+id));
        }
        if(this._file_counter[id] != '0' && (options.max_files == 0 || options.max_files > this._file_counter[id])){
            add_message = '<div style="cursor:pointer;text-decoration:underline;" class="select_message" onclick="FileUploader.add_file_upload_field(\''+id+'\')" id="file_uploader_add_message_'+id+'">'+options.add_message+'</div>';
            new Insertion.Bottom($(id+'_message_box'), add_message);
            $('file_uploader_add_message_'+id).show();
        }else{
            $('file_uploader_select_message_'+id).show();
        }

        if(!$(input_box_id) == false){
            Element.remove($(input_box_id));
        }
    },


    get_options : function(id){
        return $H(!$(id).title ? this.options : Object.extend(Object.extend({},this.options), $(id).title.parseQuery() || {}));
    },

    create_file_box : function(id){
        if(!$(id+'_file_box')){
            var file_box = document.createElement('div');
            file_box.id = id+'_file_box';
            var message_box = document.createElement('div');
            message_box.id = id+'_message_box';
            var files = document.createElement('div');
            files.id = id+'_uploaded_files';
            file_box.appendChild(message_box);
            file_box.appendChild(files);
            $(id).parentNode.insertBefore(file_box,$(id));
        }
    },

    download_file : function(persistence_key){
        WindowOpenener.popup(window, this._file_uploader_form.action+(this._file_uploader_form.action.match(/\?/) ? '&' : '?')+'persistence_key='+persistence_key, name)
    },

    link_items : function(iframe, form){
        if(!$(form).submiting_to_iframe == false){
            try {
                // NS6
                var json = iframe.contentDocument.document.body.innerHTML;
                iframe.contentDocument.document.close();
            }catch (e){
                try{
                    // For IE5.5 and IE6
                    var json = iframe.contentWindow.document.body.innerHTML;
                    iframe.contentWindow.document.close();
                }catch (e){
                    // for IE5
                    try {
                        var json = iframe.document.body.innerHTML;
                    }catch (e) {
                        // for really nasty browsers
                        try	{
                            var json = window.frames[iframe.id].document.body.innerText;
                        }
                        catch (e) {
                            // Forget about file preoloading
                        }
                    }
                }
            }
            try{
                eval("var server_response = "+json);
            }catch(e){
                return;
            }
            this._file_uploader_current_uploads.collect(function(index, counter){
                var tmp = index.toString().replace(/\[\]$/,'['+counter+']').split(',');
                var item_id = tmp[0];
                var input_name = tmp[1].replace(/^([A-Za-z0-9_ ]+)/,'[$1]').replace(/\[/g,'[\'').replace(/\]/g,'\']');
                var item_details = $H(eval('server_response' + input_name));
                new Insertion.After($(item_id), '<div id="uploaded_file_'+item_id+'_info" class="uploaded_info"><a class="uploaded_download" href="javascript:FileUploader.download_file(\''+item_details['persistence_key']+'\')">'+item_details['name']+'</a> <span class="uploaded_type">'+item_details['type']+'</span> <span class="uploaded_size">'+item_details['human_size']+'</span></div>');
                $$('div#file_uploader_'+item_id+' .remove_message').each(function(element){Element.remove(element);});

                //this.uploaded_items.push([item_details['persistence_key'],item_details]);

                new Insertion.Top($('uploaded_file_'+item_id+'_info'), '<input type="hidden" name="persisted_keys[]" value="'+item_details['persistence_key']+'" /><input id="'+item_details['persistence_key']+'" type="checkbox" checked="checked" name="'+'persisted_files'+input_name.replace(/\'/g,'').replace(/\[[0-9]+\]$/,'[]')+'" value="'+item_details['persistence_key']+'" />');
                                
                Element.remove($(item_id));

            }.bind(this));
            
            this._file_uploader_current_uploads = $H();

        }
    },

    _insert_target_iframe : function(form){
        this.options.target_id = 'file_uploader_target_for_'+$(form).id;
        var iframe_html = '<iframe id="'+this.options.target_id+'" name="'+this.options.target_id+'" frameborder=0 scrolling="no" style="width:0;height:0;position:absolute;top:0;left:0" onload="try{top.FileUploader.link_items(this, \''+$(form).id+'\')}catch(e){throw(e)}"></iframe>';
        new Insertion.Bottom($(form), iframe_html);
        $(this.options.target_id).hide();
    },

    _submit_form_to_iframe : function(){

        var form = this._file_uploader_form;
        var original_target = form.target;
        form.target = this.options.target_id;
        form.submiting_to_iframe = true;

        $A(form.getElementsByTagName('input')).each(function(node){
            if(node.type == 'file' && node.value != ''){
                this._file_uploader_current_uploads[node.id] = node.name;
            }
        }.bind(this));

        if(!$('calling_from_a_iframe_file_uploader_for_form_'+form.id)){
            var iframe_id = document.createElement('input');
            iframe_id.type = 'hidden';
            iframe_id.name = '__iframe_file_uploader_call_from';
            iframe_id.id = 'calling_from_a_iframe_file_uploader_for_form_'+form.id;
            iframe_id.value = form.id;
            $(form).appendChild(iframe_id);
        }

        form.submit();
        form.target = original_target;

        if(!$('calling_from_a_iframe_file_uploader_for_form_'+form.id) == false){
            Element.remove($('calling_from_a_iframe_file_uploader_for_form_'+form.id));
        }

        return;
    },

    onChange: function(event) {
        this._submit_form_to_iframe();
    }

}