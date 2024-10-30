jQuery(function ($) {

  setTimeout(function(){ jQuery('[id^=item_] select').trigger('change'); }, 1000);

  wckp_enable_disable_add_kit_btn();
  wckp_add_new_product_field();
  wckp_delete_product_field();
  wckp_set_quantity_field_name();
  wckp_wooSelect_template_adpater();
  wckp_prevent_duplicate_selection();

});

function wckp_init_selectWoo(){
  var options = {
    'templateSelection': wckp_custom_selection_template,
    'templateResult': wckp_custom_result_template,
    'minimumResultsForSearch': -1,
    'selectionAdapter': jQuery.fn.selectWoo.amd.require('wckpSelectionAdapter'),
    }
  jQuery("[id^=item_] select").selectWoo(options);
}

function wckp_add_new_product_field(){
  var i = 1;
  var min_products = wckp_vars.min_products;
  var max_products = wckp_vars.max_products;

  jQuery(document).on('click','.wckp_kit_add_selezione', function(){
    var sel = jQuery('#kit_products_wrapper select').size();

    if( sel <= max_products){
      i++;
    var newRow = jQuery('#wckp-kit-selezione');
    newRow.before("<div id='item_id_"+ i +"' class='kit_product_item'>" + newRow.html() + "</div>");

    var rowId = ('item_id_'+i);
    var kit_product_select = [];

    jQuery( ".kit_product_select" ).each(function( index ) {
      var e = jQuery( this ).find(":selected").val();
      if(e.length > 0) {
        kit_product_select.push(e);
      }
    });
    
    wckp_init_selectWoo();
 
    }else{
        if(jQuery('.product_fields_maxedout_message').length === 0){
        jQuery("<div>", {
          'class': "product_fields_maxedout_message",
      }).appendTo('#kit_products_wrapper').html('<p>Kit\'s maximum limit reached. Sorry you cannot select more products.</p>').slideDown(100);
    }
    setTimeout(function() { 
      jQuery(".product_fields_maxedout_message").slideUp(500); 
    }, 5000);
    setTimeout(function() { 
      jQuery(".product_fields_maxedout_message").remove(); 
    }, 6000);
    }
    
    wckp_prevent_duplicate_selection();

  });
}

function wckp_delete_product_field(){
  jQuery(document).on('click','.wckp_kit_sub_selezione', function(){
    var button_id = jQuery(this).closest('.kit_product_item').attr("id");
    jQuery("#"+button_id+"").remove();

    var kit_product_select = [];

    jQuery( ".kit_product_select" ).each(function( index ) {
      var e = jQuery( this ).find(":selected").val();
      if(e.length > 0) {
        kit_product_select.push(e);
      }
    });

    jQuery('.kit_custom_pricing .kit_product_item .quantity').trigger('change');
    wckp_enable_disable_add_kit_btn();

    var min_products = wckp_vars.min_products;
    var sel_val = jQuery(this).find(":selected").val();
    var select_count = jQuery('#kit_products_wrapper select').size() - 1;

    var selected_count_x = 0;
    jQuery('#kit_products_wrapper select').each(function () {
      if(jQuery(this).find('option:selected').val() !== ''){
        selected_count_x++;
      }
    });

    if( select_count < min_products || selected_count_x < min_products){
      jQuery("button.kit_product_add_to_cart_button").prop('disabled', true);
    }else{
      jQuery("button.kit_product_add_to_cart_button").prop('disabled', false);
    }

    return false;
  });
}

function wckp_enable_disable_add_kit_btn(){

  jQuery(document).on('load', '.kit_product_select', function() {
    jQuery("button.kit_product_add_to_cart_button").prop('disabled', true);
  });

  jQuery(document).on('change','.kit_product_select', function(){
    var min_products = wckp_vars.min_products;
    var kit_product_select = [];
    var sel_val = jQuery(this).find(":selected").val();
    var select_count = jQuery('#kit_products_wrapper select').size() - 1;

    var selected_count_x = 0;
    jQuery('#kit_products_wrapper select').each(function () {
      if(jQuery(this).find('option:selected').val() !== ''){
        selected_count_x++;
      }
    });

    if( select_count < min_products || selected_count_x < min_products){
      jQuery("button.kit_product_add_to_cart_button").prop('disabled', true);
    }else{
      jQuery("button.kit_product_add_to_cart_button").prop('disabled', false);
    }

    return false;
  });


  jQuery('.kit_product_add_to_cart_button').click(function () {
    var isValid = false;
    var min_products = wckp_vars.min_products;
    var select_count = jQuery('#kit_products_wrapper select').size() - 1;
    var selected_count = 0;

    jQuery('#kit_products_wrapper select').each(function () {
      if(jQuery(this).find('option:selected').val() !== ''){
        selected_count++;
      }
        if (selected_count >= min_products && select_count >= min_products) {
          jQuery(".kit_product_add_to_cart_button").prop('disabled', false);
            isValid = true;
            return isValid;
        }

    });
    if (isValid) { return isValid; } else { return false; }
  });
}

function wckp_custom_selection_template(obj){
  var data = jQuery(obj.selected[0].element).data();
  var text = jQuery(obj.selected[0].element).text();;
  //console.log(data);

  if (obj.selected[0].id === '') { // adjust for custom placeholder values
    return 'Select Product...';
  }
  if(data && data['img_src']){

    img_src = data['img_src'];
    
    template = jQuery("<div class=\"kit-list-content\"><img src=\"" + img_src + "\" /><span>" + text + "</span></div>");
    //console.log(template);
    return template;
  }
}

function wckp_custom_result_template(obj){
  var data = jQuery(obj.element).data();
  var text = jQuery(obj.element).text();
  //console.log(obj);

  if (obj.id === '') { // adjust for custom placeholder values
    return 'Select Product...';
  }
  if(data && data['img_src']){
    img_src = data['img_src'];
    var product_price = '';
    if(data['price']){
      product_price = data['price'];
    }
    
    template = jQuery("<div class=\"kit-list-content\"><img src=\"" + img_src + "\" /><span><p>" + text +"</p>"+ product_price +"</span></div>");
    
    return template;
  }
}

function wckp_set_quantity_field_name(){

  jQuery(document).on('change','.kit_product_select', function(){

    var kit_product_select = [];
    var c = jQuery(this).find(":selected").val();
    jQuery(this).closest('.kit_product_item').find(".quantity").attr('name', 'qty-'+c);
    jQuery( ".kit_product_select" ).each(function( index ) {
      var e = jQuery( this ).find(":selected").val();
      if(e.length > 0) {
        kit_product_select.push(e);
      }
    });

    //wckp_init_selectWoo();
    //wckp_prevent_duplicate_selection();

    return false;
  });
}

function wckp_prevent_duplicate_selection(){

  jQuery('[id^=item_] select').on("select2:selecting", function(e) {
    var selected_products = [];
    jQuery("[id^=item_] select option:selected").each(function(){
      var p = jQuery(this).val();
      if(p.length > 0) {
        selected_products.push(p);
      }
    });

    var current_val = e.params.args.data.id;
    if(jQuery.inArray(current_val, selected_products) !== -1 ){
      console.log(current_val);
      //e.stopPropogation();
      e.preventDefault();
      jQuery(this).selectWoo("close");
      //alert('Product already selected!');
      if(jQuery('.product_already_selected_message').length === 0){
        jQuery("<div>", {
          'class': "product_already_selected_message",
      }).appendTo('#kit_products_wrapper').html('<p>This product is already in the kit!</p>').slideDown(100);
    }
    setTimeout(function() { 
      jQuery(".product_already_selected_message").slideUp(500); 
    }, 5000);
    setTimeout(function() { 
      jQuery(".product_already_selected_message").remove(); 
    }, 6000);
      return false;
    }
    });
}

function wckp_wooSelect_template_adpater(){
  
  jQuery.fn.select2.amd.define("wckpSelectionAdapter", [
    "select2/utils",
    "select2/selection/multiple",
    "select2/selection/placeholder",
    "select2/selection/eventRelay",
    "select2/selection/single",
  ],
  function(Utils, MultipleSelection, Placeholder, EventRelay, SingleSelection) {

    // Decorates MultipleSelection with Placeholder
    let adapter = Utils.Decorate(MultipleSelection, Placeholder);
    // Decorates adapter with EventRelay - ensures events will continue to fire
    // e.g. selected, changed
    adapter = Utils.Decorate(adapter, EventRelay);

    adapter.prototype.render = function() {
      // Use selection-box from SingleSelection adapter
      // This implementation overrides the default implementation
      let $selection = SingleSelection.prototype.render.call(this);
      return $selection;
    };

    adapter.prototype.update = function(data) {
      // copy and modify SingleSelection adapter
      this.clear();

      let $rendered = this.$selection.find('.select2-selection__rendered');
      let noItemsSelected = data.length === 0;
      let formatted = "";

      if (noItemsSelected) {
        formatted = this.options.get("placeholder") || "";
      } else {
        let itemsData = {
          selected: data || [],
          all: this.$element.find("option") || []
        };
        // Pass selected and all items to display method
        // which calls templateSelection
        formatted = this.display(itemsData, $rendered);
      }

      $rendered.empty().append(formatted);
      $rendered.prop('title', formatted);
    };

    return adapter;
  });
  wckp_init_selectWoo();
}

