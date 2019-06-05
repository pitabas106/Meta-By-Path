/**
 * Author: NetTantra
 * @package Meta By Path
 */


jQuery(document).ready(function() {
  WPMBPApp.init();
});

var WPMBPApp = {
  init: function() {
    WPMBPApp.getAllMetaInfoData();

    jQuery('#all_page').on('click', function() {
      if (jQuery(this).prop("checked") == true) {
        jQuery('#page_url').prop("disabled", true);
        jQuery('#page_url').val('');
        jQuery('#page_url').prop('placeholder', '');
      } else {
        jQuery('#page_url').prop("disabled", false);
      }
    });

    jQuery("#submit").on('click', function(e) {
      e.preventDefault();
      WPMBPApp.saveMetaInfoForm();
    });

  },

  hideMessage: function() {
    jQuery('.show-error-msg').fadeOut();
  },

  metaInfoErrorMessage(message) {
    if (!jQuery('.show-error-msg').length) {
      var message_txt = (message) ? message : 'Meta Name and Value can\'t be blank!';
      return jQuery('#meta_info_table').after('<p class="nt-danger show-error-msg" style="padding: 10px 20px;"> ' + message_txt + ' <span class="nt-msg-close" onclick="WPMBPApp.hideMessage()">X</span></p>');
    }
  },

  getAllMetaInfoData: function() {
    jQuery.ajax({
      type: "POST",
      url: WP_Meta_By_Path.ajax_url,
      data: {
        "action": "wpmbp_get_all_data",
        "type": "get",
        nonce: WP_Meta_By_Path.get_nonce
      },
      dataType: 'json',
      success: function(result) {
        jQuery('#all-meta-data').html(result.data);
      }
    });
  },

  Add: function() {
    var metaName = document.getElementById("meta_name");
    var metaValue = document.getElementById("meta_value");

    var meta_error = false;
    if (metaName.value == '') {
      jQuery('#meta_name').addClass('nt-danger');
      meta_error = true;
    }
    if (metaValue.value == '') {
      jQuery('#meta_value').addClass('nt-danger');
      meta_error = true;
    }

    if (meta_error) {
      WPMBPApp.metaInfoErrorMessage()
      return;
    } else {
      jQuery('#meta_value').removeClass('nt-danger');
      jQuery('#meta_name').removeClass('nt-danger');
    }

    WPMBPApp.AddRow(metaName.value, metaValue.value);
    metaName.value = "";
    metaValue.value = "";
  },

  Remove: function(button) {
    var row = button.parentNode.parentNode;
    var name = row.getElementsByTagName("TD")[0].firstElementChild.value;
    if (confirm("Do you want to delete: " + name)) {
      var table = document.getElementById("meta_info_table");
      table.deleteRow(row.rowIndex);
    }
  },

  AddRow: function(meta_name, met_value) {
    var tBody = document.getElementById("meta_info_table").getElementsByTagName("TBODY")[0];
    row = tBody.insertRow(-1);
    var cell = row.insertCell(-1);
    cell.innerHTML = '<input class="regular-text" type="text" value="' + meta_name + '" />';
    cell = row.insertCell(-1);
    cell.innerHTML = '<input class="regular-text" type="text" value="' + met_value + '" />';

    //Add Button cell.
    cell = row.insertCell(-1);
    cell.setAttribute("class", "nt-text-center");
    var btnRemove = document.createElement("span");
    btnRemove.setAttribute("class", "dashicons dashicons-trash");
    btnRemove.setAttribute("onclick", "WPMBPApp.Remove(this);");
    cell.appendChild(btnRemove);
  },

  saveMetaInfoForm: function() {
    var myobj = [];
    var rows = jQuery('#meta_info_table tr').length;
    var meta_field_error = false;

    for (var j = 1; j < rows - 1; j++) {
      var key = document.getElementById("meta_info_table").rows[j].cells[0].firstElementChild.value;
      var value = document.getElementById("meta_info_table").rows[j].cells[1].firstElementChild.value;

      if (key == '') {
        document.getElementById("meta_info_table").rows[j].cells[0].firstElementChild.setAttribute("class", "nt-danger regular-text");
        meta_field_error = true;
      }
      if (value == '') {
        document.getElementById("meta_info_table").rows[j].cells[1].firstElementChild.setAttribute("class", "nt-danger regular-text");
        meta_field_error = true;
      }
      if (meta_field_error) {
        WPMBPApp.metaInfoErrorMessage();
        return;
      } else {
        document.getElementById("meta_info_table").rows[j].cells[0].firstElementChild.setAttribute("class", "regular-text");
        document.getElementById("meta_info_table").rows[j].cells[1].firstElementChild.setAttribute("class", "regular-text");
        meta_field_error = false;
      }

      var data = {
        keyword: key,
        description: value
      };
      myobj.push(data);
    }
    var meta_info_json_string = JSON.stringify(myobj);
    var request_type = (jQuery('#request_type').val()) ? jQuery('#request_type').val() : '';
    var item_id = (jQuery('#item_id').val()) ? jQuery('#item_id').val() : '';
    var page_url = jQuery("#page_url").val();

    var all_page_or_page_url = '';
    if (page_url) {
      all_page_or_page_url = 1;
    }
    if (jQuery('#all_page').prop("checked") == true) {
      page_url = '';
      all_page_or_page_url = 1;
      var all_page = jQuery("#all_page").val();
    }

    if (myobj.length == 0 || all_page_or_page_url == '') {
      WPMBPApp.metaInfoErrorMessage('Page URL or Apply to all page and Meta info fields are can\'t be blank!');
      return;
    }

    var json_obj = {
      page_url: page_url,
      all_page: all_page,
      meta_info: meta_info_json_string,
    };

    jQuery('#submit').after('&nbsp;<img src="' + WP_Meta_By_Path.admin_url + '/images/loading.gif" class="wpmbp-loader">');

    jQuery.ajax({
      type: "POST",
      url: WP_Meta_By_Path.ajax_url,
      dataType: 'json',
      data: {
        "action": "wpmbp_save_metainfo_data",
        "form_data": json_obj,
        "type": request_type,
        "id": item_id,
        nonce: WP_Meta_By_Path.nonce,
      },
      success: function(data) {
        jQuery('.wpmbp-loader').remove();
        if (request_type == 'update') {
          window.location.href = WP_Meta_By_Path.admin_url + "options-general.php?page=meta-by-path";
        }
        WPMBPApp.resetMetaInfoForm();
        WPMBPApp.getAllMetaInfoData();
      }
    });
  },

  deleteMetaInfo: function(item_id) {
    if (confirm("Do you want to delete?")) {
      if (item_id == '') {
        console.log('Item ID doesn\'t exit.');
        return;
      }
      jQuery.ajax({
        type: "POST",
        url: WP_Meta_By_Path.ajax_url,
        dataType: 'json',
        data: {
          "action": "wpmbp_delete_metainfo_data",
          "item_id": item_id,
          "type": 'delete',
          nonce: WP_Meta_By_Path.delete_nonce
        },
        success: function(result) {
          jQuery('#all-meta-data').prepend('<p style="text-align: right;" class="nt-success-txt">' + result.msg + '</p>');
          WPMBPApp.getAllMetaInfoData();
        }
      });
    }
  },

  resetMetaInfoForm: function() {
    jQuery("input[type='text']").each(
      function() {
        jQuery(this).val('');
      });
    document.getElementById("meta_info_table").getElementsByTagName("TBODY")[0].innerHTML = '';
    if (jQuery('.show-error-msg').length) {
      jQuery('.show-error-msg').remove();
    }
    jQuery('#all_page').prop("checked", false);
  }

}
