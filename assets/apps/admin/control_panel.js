function globalajaxify(file, targetwidget){ 

		      $('<div class="loading"></div>').html("Loading please wait").appendTo('body').fadeIn(); 
		      $.get(file,function(data) { 
		          $(targetwidget).slideUp('slow',function(){ 
		              $(this).html(data).slideDown('slow',function(){ 
		                  $('.loading').fadeOut('slow',function(){$(this).remove();}); 
		              }); 
		          }); 
		      }); 
}


function code_mirror(){
	
    htmleditor = CodeMirror.fromTextArea('widget', {
        height: "350px",
        parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js",
                     "tokenizephp.js", "parsephp.js", "parsephphtmlmixed.js"],
        stylesheet: ["assets/plugins/codemirror/css/xmlcolors.css", "assets/plugins/codemirror/css/jscolors.css", "assets/plugins/codemirror/css/csscolors.css", "assets/plugins/codemirror/css/phpcolors.css"],
        path: "assets/plugins/codemirror/js/",
        continuousScanning: 500,
        lineNumbers: true,
      }); 
    
    sqleditor = CodeMirror.fromTextArea('normal_select', {
        height: "250px",
        parserfile: "parsesparql.js",
        stylesheet: ["assets/plugins/codemirror/css/sparqlcolors.css"],
        path: "assets/plugins/codemirror/js/",
      });  
	
}


function refreshthetree(){
	$.tree_reference('widgettree').refresh();
}

function refreshdatabasetree(){
	$.tree_reference('databaseformstree').refresh();
}

function create_form(selector, id, name, code){
	   $(selector).click(function(event){
			$("#widget-form").load("admin_tools/new/"+id,  {limit: 25}, function(){
				
				var current_form = $("#new_record_form").html();
				
				$('#new_record_form').ajaxForm(function() { 
					refreshthetree();
					$("#new_record_form").html(current_form + "Added new " + name + " successfully");
					if (code == 1){
					code_mirror();
					}
	           }); 
				if (code == 1){
				code_mirror();
				}
			});	
	   });		
}


function urls_widgets_tree(){

    var formoptions = { 
        target:        '#widget-form',
        success:       refreshthetree  
    }; 
	
	 

   create_form("#create_new_url", 16, "url", 0);
   create_form("#create_new_widget", 20, "Widget", 1);   
    
    
   $("#widgettree").tree( {
      
      data  : {
        type  : "xml_flat",
        url   : "assets/apps/admin/urls_widgets.php",
      },
      
      rules : {
        deletable : "all",
        draggable : "all"      	
      },
      
      callback : {
        onselect : function(NODE,TREE_OBJ) {
    	  if (isNaN(NODE.id)){
    	  
    	  }else{

    	      $.get('admin_tools/edit/20/'+NODE.id,function(data) { 
                  $('#widget-form').html(data);
                  $('#edit_form').ajaxForm(formoptions);
                  code_mirror();
    	      });
    		  
    	  }
        },

	
		beforedelete    : function(NODE, TREE_OBJ,RB) { 
				
		$("#deletewidgetdialog").dialog({
			bgiframe: true,
			resizable: false,
			height:140,
			modal: true,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.5
			},
			close: function(event, ui) {
				$(this).dialog('destroy');
			},
			buttons: {
				'Delete widget': function() {


					if(isNaN(NODE.id)) {
					//delete url	
					}else{
						$("#widget-form").load("admin_tools/delete/2/"+NODE.id+":aiki_widgets:yes",  {limit: 25}, function(){
							$.tree_reference('widgettree').refresh();
     					});
					}
     				
     				$(this).dialog('close');
     				$(this).dialog('destroy');
     				
				},
				Cancel: function() {
					$(this).dialog('close');
					$(this).dialog('destroy');
				}
			}
		});

				

 
		}
        
       }

    } );
}


function database_forms_tree(){

    var formoptions = { 
        target:        '#widget-form',
        success:       refreshdatabasetree  
    }; 
	
	 

   //create_form("#create_new_table", 3, "Table", 0);
   create_form("#create_new_form", 6, "Form", 0);   
    
    
   $("#databaseformstree").tree( {
      
      data  : {
        type  : "xml_flat",
        url   : "assets/apps/admin/database_forms.php",
      },
      
      rules : {
        deletable : "all",
        draggable : "all"      	
      },
      
      callback : {
        onselect : function(NODE,TREE_OBJ) {
    	  if (isNaN(NODE.id)){
    	  
    	  }else{

    	      $.get('admin_tools/array/id/form_name/form_array/aiki_forms/'+NODE.id,function(data) { 
                  $('#widget-form').html(data);
                  $('#edit_form').ajaxForm(function() { 
                	 refreshdatabasetree();
					$("#edit_form").html("Edited successfully");
	           }); 
    	      });
    		  
    	  }
        },

	
		beforedelete    : function(NODE, TREE_OBJ,RB) { 
				
		$("#deletewidgetdialog").dialog({
			bgiframe: true,
			resizable: false,
			height:140,
			modal: true,
			overlay: {
				backgroundColor: '#000',
				opacity: 0.5
			},
			close: function(event, ui) {
				$(this).dialog('destroy');
			},
			buttons: {
				'Delete widget': function() {


					if(isNaN(NODE.id)) {
					//delete url	
					}else{
						$("#widget-form").load("admin_tools/delete/2/"+NODE.id+":aiki_widgets:yes",  {limit: 25}, function(){
							$.tree_reference('widgettree').refresh();
     					});
					}
     				
     				$(this).dialog('close');
     				$(this).dialog('destroy');
     				
				},
				Cancel: function() {
					$(this).dialog('close');
					$(this).dialog('destroy');
				}
			}
		});

				

 
		}
        
       }

    } );
}


function system_accordion(){
	$("#system_accordion").accordion({
		fillSpace: true
	});
}

function structur_accordion(){
		$("#structur_accordion").accordion({
			fillSpace: true
});
		urls_widgets_tree(); 
}

function widget_accordion(){
	$("#widget_accordion").accordion({
		fillSpace: true
	});
}

$().ready(function() {
	$("#dialog").dialog({ autoOpen: false });
	$("#aiki-icon-button").click(function(){
		$("#dialog").dialog('open');
	});
	
	var outerLayout; // init global vars

		// PAGE LAYOUT
		outerLayout = $('body').layout({
			applyDefaultStyles:	true
			// AUTO-RESIZE Accordion widget when west pane resizes
		,	west__onresize:		function () { $("#structur_accordion").accordion("resize"); }
		,	west__onopen:		function () { $("#structur_accordion").accordion("resize"); }
		,	center__onresize:	function () { $("#accordion-center").accordion("resize"); }
		,	center__onopen:		function () { $("#accordion-center").accordion("resize"); }
		,	west__size:			300
		});

		structur_accordion();

		widget_accordion();

	
	//global ajaxify
	 $("a").click(function(event){

		  if($(this).attr('rel') && $(this).attr('href') && $(this).attr('ajax')) {
		      globalajaxify($(this).attr("rel"), $(this).attr("href")); 

		  return false; 
		  }
			 
	 });	
	 	 
	   $("#database_forms").click(function(event){
		   $.tree_reference('widgettree').destroy();
		   database_forms_tree();
	   });	 
	   
	   $("#urls_widgets").click(function(event){
		   $.tree_reference('databaseformstree').destroy();
		   urls_widgets_tree();
	   });	   
	   

});