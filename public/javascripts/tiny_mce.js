jQuery(document).ready(function() {
  jQuery('.tinymce').tinymce({
    script_url : '/javascripts/tiny_mce/tiny_mce.js',
    theme: "advanced",
    mode: "specific_textareas",
    editor_selector: "tinymce",
    skin: "o2k7",
    skin_variant: "silver",
    plugins: "contextmenu,paste,spellchecker,style,table",
    theme_advanced_styles : "Heading 1=mce_header1;Heading 2=mce_header2;Heading 3=mce_header3",
    theme_advanced_toolbar_location: "top",
    theme_advanced_toolbar_align: "left",
    theme_advanced_resizing: true,
    theme_advanced_resize_horizontal: true,
    theme_advanced_statusbar_location: "bottom",
    paste_auto_cleanup_on_paste: true,
    theme_advanced_buttons1: "formatselect,bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,indent,outdent,separator,bullist,numlist,forecolor,backcolor,separator,link,unlink,image,undo,redo,separator,code,cleanup,removeformat",
    theme_advanced_buttons2: "tablecontrols",
    theme_advanced_buttons3: "",
    convert_newlines_to_brs: false,
    // cleanup_serializer: 'xml',
    // encoding: 'xml',
    entity_encoding: "raw"
    });
});