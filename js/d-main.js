var first_name = "";
var last_name = "";
var email = "";
var phone = "";
var street_address = "";
var city = "";
var state = "";
var zipcode = ""; 
var currentTab = 0;
var modal_content_loaded = false;
var show_plan_building_wizard = false;
var show_steps_box = false;
var search_attempts = 0;
var failed_search_attempts = 0;
var max_failed_search_attempts = 2;
var selected_modem = 0;
var selected_internet_plan = 0;
var selected_phone = 0;
var selected_phone_name = "";
var todate = new Date();
var dg_rand = Math.random();
var change_address = false;
var limited_speed = false;
var email_reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
var copy_cc_field_added = false;

var searched_address = {};
var autocomplete;

var componentForm = {
  street_number: 'short_name',
  route: 'long_name',
  locality: 'long_name',
  administrative_area_level_1: 'short_name',
  administrative_area_level_2: 'short_name',
  sublocality_level_1 : 'long_name',
  country: 'long_name',
  postal_code: 'short_name'
};

jQuery(document).ready(function() {
	
	adjust_hero_section();
	launch_plan_building_wizard();
	activate_dg_custom_tabs();
	//cta_buttons_hover_effect();
	override_texts();
	timeout_function();
	dg_cart_module();
	validate_date_fields();
	check_current_state ();

	// if there is a panel id= panel-showinternetplans, it will fill with either res internet plans or
	// check availability button 
	show_internetplans();

	//load_phone_rates();
	
});

jQuery(document).on("input", ".numeric", function() {
    this.value = this.value.replace(/\D/g,'');
});


jQuery(window).on('resize', function(){
      
      adjust_hero_section();
      
});

window.addEventListener("popstate",function(event) {
  	//console.log(event);
	if (event.state!=null && event.state.step!=null && event.state.dg_rand!=null && event.state.dg_rand==dg_rand ) {
		var current_step = parseInt(event.state.step) - 1;
		change_cart_step (current_step);
		if (jQuery("body").hasClass("page-id-2867") && event.state.option!=null ) {
			
			jQuery(".woocommerce-monthly-billing-fields__field-wrapper").hide();
			jQuery(".woocommerce-monthly-billing-fields__field-wrapper."+event.state.option+"_details").show();
		}
	}
});  

/*
function select_internet_plan(id) {
                var user_data = {};

                user_data.selected_internet_plan = id; //jQuery("input[name='InternetPlan']:checked").val();
                if( user_data.selected_internet_plan == undefined ) {
                        return;
                }

                // show modems matching the internet plan
                var skus = jQuery("input[name='InternetPlan']:checked + label .internet-sku").text();
                var li = skus.split(",");
                if( li.length > 0 ) {
                        jQuery(".modemsku-all").hide(); // hide all
                        for( var i = 0; i<li.length; i++ ) {
                                var s = li[i];
                                var name = ".modemsku-" + s;
                                jQuery(name).show();
                        }
                }

                UpdateUserData(user_data, 2);

                var stateObj = { "step" : 4 , "dg_rand": dg_rand }
                history_push_state(stateObj,"Installation","#stepdates");

                UpdateBuildYourPlan_AddPlan(user_data.selected_internet_plan, "Internet Plan");
}
*/

function UpdateUserData(user_data, state, content_mod=false ) {

	user_data.order_step = state;

	jQuery.post(
		et_pb_custom.ajaxurl, 
		{
			'action': 'update_user_data',
			'data':  user_data  
		}, 
		function(response_full) {

			if( state == 6 ) {
				// response should be in json
				response = JSON.parse( response_full );
				if( response.status == "success" ) {
					var stateObj = { "step" : 7 , "dg_rand": dg_rand } 
					history_push_state(stateObj,"Review Upfront Payment","#stepupfront");
					SignupWizardNextTap_ex(content_mod);
				} else {
					alert( response.msg );
				}
				return;
			}

			response = CleanStringFromSpecialCharacters( response_full );
			if (response!="success") {
				if( state == 1 ) {
					// go back becasue response failed
					//var stateObj = { "step" : 1 , "dg_rand": dg_rand }
					//history_push_state(stateObj,"Internet Plan","#stepinternet");
				} else if ( state == 3 ) {
					jQuery(".display_date_1").html(user_data.preffered_installation_date_1+" "+user_data.preffered_installation_time_1);
					jQuery(".display_date_2").html(user_data.preffered_installation_date_2+" "+user_data.preffered_installation_time_2);
					jQuery(".display_date_3").html(user_data.preffered_installation_date_3+" "+user_data.preffered_installation_time_3);
					//
					//						
					var stateObj = { "step" : 4 , "dg_rand": dg_rand } 
					history_push_state(stateObj,"Phone Plan","#stepphone");
				}
			}

			return;
		}
	);
}

function UpdateBuildYourPlan_AddPlan(productid, type) {
	jQuery('table.woocommerce-checkout-review-order-table').addClass('loading_act');
				jQuery.post(
				    et_pb_custom.ajaxurl, 
				    {
				        'action': 'add_plan',
				        'plan_id': productid,
				        'type'	 : type   
				    }, 
				    function(response) {
						
						response = CleanStringFromSpecialCharacters( response );

						if (response!="success") {
							
							 failed = true;	

						} else {
							dg_update_order_review();
						}
				    }
				);
}

function history_push_state(stateObj , title , path_or_hash ) {

	window.history.pushState(stateObj, title , path_or_hash);

}

function check_current_state () {
	
	var currentState = window.history.state;
	
	//console.log(currentState);
	
	/*if (currentState!=null) {
		var current_step = parseInt(currentState.step) - 1;
		change_cart_step (current_step);
		
		//signup page
	} else*/ if (jQuery("body").hasClass("page-id-2935")) {
		var stateObj = {"step" : 1 , "dg_rand": dg_rand };
		history_push_state(stateObj,"Internet Plans","#stepinternet");
	
		//checkout page
	} else if (jQuery("body").hasClass("page-id-2867")) {
		var stateObj = {"step" : 2 , "dg_rand": dg_rand  };
		history_push_state(stateObj,"Monthly Review","#stepmonthly");
	}
	
	
	
}

function CleanStringFromSpecialCharacters(str) {
	var newstr = "";
	for( var i = 0; i < str.length; i++ )  {
		if( (str[i] >= '0' && str[i] <= '9') || 
		(str[i] >= 'A' && str[i] <= 'Z') ||
		(str[i] >= 'a' && str[i] <= 'z'))
        	newstr += str[i];
	}
	return newstr;
}

function latterOnly(fieldValue){
	var alphaExp = /^[a-zA-Z]+$/;
	if(fieldValue.match(alphaExp)){
		return true;

	} else{
		return false;
	}
}

function dismiss_modal_toc() {
	var modal = document.getElementById("upfront_toc_modal");
	modal.style.display = "none";
}

function agree_terms(){
	// Get the modal
	var modal = document.getElementById("upfront_toc_modal");
	modal.style.display = "block";

	//jQuery('#terms').attr('disabled',false);
	//jQuery('#terms_label').fadeIn(1000);
	//jQuery('.place_order_button_wrapper').fadeIn(1000);
}


function dg_setCookie(name,value,days) {
	var expires = "";
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days*24*60*60*1000));
		expires = "; expires=" + date.toUTCString();
	}
	document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function dg_getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function dg_eraseCookie(name) {   
	document.cookie = name+'=; Max-Age=-99999999;';  
}

function adjust_hero_section() {
	
	var win_width = jQuery(window).width();
	var win_height = jQuery(window).height();
	var top_height = jQuery("#top-header").height();
	var menu_height = jQuery("#main-header").height();
	var height_to_minus = menu_height + top_height;
	
	 
	//if larger than  
	if (win_width>1200) {
	
		jQuery("#diallog-herosection .et_pb_fullwidth_header").css("Height",win_height-height_to_minus);	
		jQuery("#diallog-herosection .et_pb_fullwidth_header_container").css("maxWidth","100vw");
		jQuery("#diallog-herosection .et_pb_fullwidth_header_container img").css("width","100%");	
	
	} else {
		
		jQuery("#diallog-herosection .et_pb_fullwidth_header").css("minHeight","");	
		jQuery("#diallog-herosection .et_pb_fullwidth_header_container").css("maxWidth","");
		jQuery("#diallog-herosection .et_pb_fullwidth_header_container img").css("width","");
		
	}
}

function launch_plan_building_wizard () {
	
	//jQuery("#plan-building-wizard-modal").modal();
	/*
	jQuery("#help-me-build-my-plan a").click(function(e) {
		
		e.preventDefault();
		
		show_plan_building_wizard = true;
		jQuery("#plan-building-wizard-modal").modal("show");
		
	});
	*/
	
	jQuery("a[data-target=#plan-building-wizard-modal]").click(function(e) {
		
		show_plan_building_wizard = false;
		
	});
	
	
	jQuery("#plan-building-wizard-modal").on("show.bs.modal",function(e){
		
		if (modal_content_loaded) {
			return;
		}
		load_plan_wizard_content();
	
	});	
	
}

function address_not_found_notify() {
	
	var user_data = {};
	var full_name = jQuery("#your_full_name");
	var phone_f = jQuery("#your_phone");
	var email_f = jQuery("#your_email");
	var failed = false;
	
	if (full_name.val()=="" || full_name.val().length<3 ) {
		
		full_name.addClass("flash_error");
		failed = true;
	
	} else {
		
		full_name.removeClass("flash_error");
		var names = full_name.val().split(" ");
		user_data.first_name = names[0];
		user_data.last_name  = names[1];
	
	}
	
	if (phone_f.val()=="" || phone_f.val().length<6 || phone_f.val().length>11) {
		
		phone_f.addClass("flash_error");
		failed = true;
	
	} else {
		
		phone_f.removeClass("flash_error");
		user_data.phone = phone_f.val();
	}
	
	if (email_f.val()=="" || email_reg.test(email_f.val()) == false) {
		
		email_f.addClass("flash_error");
		failed = true;
	
	} else {
		
		email_f.removeClass("flash_error");
		user_data.email = email_f.val();
	
	}
	
	if(!failed) {
		
		user_data.lead_status = "Need manual check";
		user_data.onboarding_stage = "address_search";
		
		jQuery("#address_not_found_notify").attr("disabled",true);
		jQuery("#address_not_found_notify").html("<i class='fa fa-spinner fa-pulse fa-2x fa-fw'></i><span class='sr-only'>Checking...</span>");
		jQuery("#address_not_found_notify").addClass("checking");
		
		jQuery.post(
		    
		    et_pb_custom.ajaxurl, 
		    {
		        'action': 'update_user_data',
		        'data':  user_data  
		    }, 
		    function(response) {
				
				response = CleanStringFromSpecialCharacters( response );

				jQuery("#address_not_found_notify").attr("disabled",false);
				jQuery("#address_not_found_notify").html("Notify");
				jQuery("#address_not_found_notify").removeClass("checking");
				
				if (response!="success") {
					failed = true;
					
					 	
				} else {
					
					goto_tab(12);
					
				}
		    }
		);
		if (failed) {
			return;
		}
	} else {
		return;
	}
}

function load_plan_wizard_content() {
	jQuery.post(
	    et_pb_custom.ajaxurl,
	    {
	        'action': 'show_pbw',  
	    }, 
	    function(response) {
			if (response!="error" && response!="" && response!="0") {
				jQuery("#plan-building-wizard-modal .modal-body").html(response);
				showTab(currentTab);
				initAutocomplete();
				modal_content_loaded = true;
				jQuery("#address_not_found_notify").click(function(e) {
					address_not_found_notify();
				});
			} 
	    }
	);
}

function changeAddress () {
	change_address = true;
	checkAvailability();
}

function checkAvailability_buildPlan() {
	var user_data = {};

	user_data.fname = jQuery("#first_name").val();
	user_data.lname = jQuery("#last_name").val();
	user_data.full_name =  jQuery("#first_name").val() + " " + jQuery("#last_name").val();
	user_data.email =  jQuery("#email").val() || "";
	user_data.lead_status = "";
	user_data.onboarding_stage = "signup_initiated"

	//manual address search form
	//var unitType = jQuery("#add_unitType").val() || "";
	var unitNumber = jQuery("#add_unitNumber").val() || "";
	var buzzerCode = jQuery("#add_buzzerCode").val() || "";
		
	searched_address.street_number = jQuery("#add_streetNumber").val() || "";
	searched_address.street_name =  jQuery("#add_streetName").val() || "";
	searched_address.street_type =  jQuery("#add_streetType").val() || "";
	searched_address.street_dir =  jQuery("#add_streetDirection").val() || "";
	searched_address.locality = jQuery("#add_municipalityCity").val() || "";
	searched_address.administrative_area_level_1 = jQuery("#add_provinceOrState").val() || "";
	searched_address.postal_code = jQuery("#add_postalCode").val() || "";
	searched_address.manual_search = 1;
	streetAddress = searched_address.street_number + " " + searched_address.street_name + " " + 
		searched_address.street_type + " " + searched_address.street_dir + ", " + searched_address.locality + 
		", "+searched_address.administrative_area_level_1+" "+searched_address.postal_code;
		
	var gaddress_component = JSON.stringify(searched_address);
	
	var state = 100;

	var ccd = "";
	queryString = window.location.search;
	if( queryString.length > 0 ) {
		const params = new URLSearchParams(queryString);
		if( params.has('ccde') ) {
			ccd = params.get('ccde');
		}else if( params.has( 'ccd' ) ) {
			ccd = btoa( params.get('ccd').toLowerCase() );
		}
	}

	jQuery.post(
		et_pb_custom.ajaxurl,
		{
			'action': "find_address_signup",
			'streetAddress' : streetAddress,
			//'unitType'	: unitType,
			'unitNumber' : unitNumber,
			'buzzerCode' : buzzerCode,
			'searched_address' : searched_address,
			'ccd_param' : ccd,
			'user_data' : user_data 
		}, 
		function(response) {

			availability_results (response, state);	

		}
	);

}

function checkAvailability() {
	
	if (jQuery("body").hasClass("page-id-2935")) {
		change_address = true;
	}
	
	//console.log(searched_address);
	//console.log(Object.keys(searched_address).length);
	
	if (currentTab==0 && !Object.keys(searched_address).length ) {
		
		jQuery(".address_res_error").text("Start typing your street address and select one of the suggestions.");
		jQuery("#ssadd").addClass('flash_error');
			
	} else {
		
		search_attempts++;
		var state = 1;
		var user_data = {};
		
		user_data.full_name =  jQuery("#search_full_name").val() || "";
		user_data.email =  jQuery("#search_email").val() || "";
		user_data.lead_status = "";
		user_data.onboarding_stage = "address_search";
		
		//manual address search form
		if (currentTab==1) {
			state = 2;
			//var unitType = jQuery("#add_unitType").val() || "";
			var unitNumber = jQuery("#add_unitNumber").val() || "";
			var buzzerCode = jQuery("#add_buzzerCode").val() || "";
			searched_address.street_number = jQuery("#add_streetNumber").val() || "";
			searched_address.street_name =  jQuery("#add_streetName").val() || "";
			searched_address.street_type =  jQuery("#add_streetType").val() || "";
			searched_address.street_dir =  jQuery("#add_streetDirection").val() || "";
			//searched_address.sublocality_level_1 = ;
			searched_address.locality = jQuery("#add_municipalityCity").val() || "";
			//searched_address.administrative_area_level_2 = ;  
			searched_address.administrative_area_level_1 = jQuery("#add_provinceOrState").val() || "";
			//searched_address.country = ;
			searched_address.postal_code = jQuery("#add_postalCode").val() || "";
			
			searched_address.manual_search = 1;
			
			streetAddress = searched_address.street_number+" "+searched_address.street_name+" "+searched_address.street_type+" "+searched_address.street_dir+", "+searched_address.locality+", "+searched_address.administrative_area_level_1+" "+searched_address.postal_code;
			
		// autofill address search field	
		} else {
			state = 1;
			var streetAddress = jQuery("#ssadd").val();
			//var unitType = jQuery("#unitType").val() || "";
			var unitNumber = jQuery("#unitNumber").val() || "";
			var buzzerCode = jQuery("#buzzerCode").val() || "";
			searched_address.manual_search = 0;
			//streetAddress = streetAddress.replace("Canada","");
			streetAddress = streetAddress.replace("Canada",searched_address.postal_code);
			
			//jQuery("#add_unitType").val("");
                        jQuery("#add_unitNumber").val("");
                        jQuery("#add_buzzerCode").val("");
                        jQuery("#add_streetNumber").val("");
                        jQuery("#add_streetName").val("");
                        jQuery("#add_streetType").val("");
                        jQuery("#add_streetDirection").val("");
                        jQuery("#add_municipalityCity").val("");
                        jQuery("#add_provinceOrState").val("");
                        jQuery("#add_postalCode").val("");


		}
		
		
		var gaddress_component = JSON.stringify(searched_address);
		
		jQuery(".address_res_error").text("");
		jQuery("#ssadd").removeClass('flash_error');
		jQuery(".checkAvailabilityBtn").attr("disabled",true);
		jQuery(".checkAvailabilityBtn").html("<i class='fa fa-spinner fa-pulse fa-2x fa-fw'></i><span class='sr-only'>Checking...</span>");
		jQuery(".checkAvailabilityBtn").addClass("checking");
		
		dg_setCookie("dgSearchedAddress",streetAddress,1);
	
		var ccd = "";
		queryString = window.location.search;
		if( queryString.length > 0 ) {
			queryString = queryString.toLowerCase();
			const params = new URLSearchParams(queryString);
			if( params.has('ccd') ) {
				ccd = btoa( params.get('ccd') );
			}
		}
		jQuery.post(
		    et_pb_custom.ajaxurl, 
		    {
		        'action': "find_address",
		        'streetAddress' : streetAddress,
		        //'unitType'	: unitType,
		        'unitNumber' : unitNumber,
		        'buzzerCode' : buzzerCode,
		        'searched_address' : searched_address,
		        'user_data' : user_data,
		        'ccd_param' : ccd 
		    }, 
		    function(response) {

		    	availability_results(response, state);	
		    	 
		    }
		);		
	}	
}

function availability_results (response, state) {
	
	// This is for the signup plans
	if( state == 100 ) {

		var panel_plans = document.getElementById("panel-showinternetplans");
		if( panel_plans != undefined ) {
			panel_plans.innerHTML = response;
		}

		jQuery(".confirm_customer_details_button").attr("disabled",false);
		jQuery(".confirm_customer_details_button").removeClass("checking");
		jQuery(".confirm_customer_details_button").html("Confirm");

		jQuery(".plan_check_other_availability_btn").hide();

		var stateObj = { "step" : 2 , "dg_rand": dg_rand }
		history_push_state(stateObj,"Installation","#stepdates");

		SignupWizardNextTap();

		return;
	}
	
	
	jQuery(".checkAvailabilityBtn").attr("disabled",false);
	jQuery(".checkAvailabilityBtn").removeClass("checking");
	jQuery(".checkAvailabilityBtn").html("Check Availability");

	var panel_plans = document.getElementById("panel-showinternetplans");
	if( panel_plans != undefined ) {
		panel_plans.innerHTML = response;
	}
	
	var found = response.search("Check Service Availability in Your Area");
	if( found < 0 ) {

		// done
		jQuery("#plan-building-wizard-modal").modal("hide");
		panel_plans.scrollIntoView();

	} else {
		// adderss not found
		if( state ==  0 ) {
			// just show the response and thats it

		} else if(state == 2 ) {

			// go to next step in the availability check
			jQuery(".checkAvailabilityBtn").html("Check Availability");
		//	show_plans_check_availability();
			jQuery(".internet-packages-check-availability-button").html("Check Availability");
			goto_tab(2);

		} else if( state == 1 ) {

			// send us an email
			jQuery(".checkAvailabilityBtn").html("Check Availability");
			if (modal_content_loaded) { goto_tab(1); }
		}
		
	}
	return;
}

function GetInternetPlansHtml(divname, action, ccd) {
	jQuery.post(
		et_pb_custom.ajaxurl, 
		{
			'action': action,
			'ccd_param': ccd,
		}, 
		function(response) {

			availability_results( response, 0 );	
			 
		}
	);		
}	

function goto_res_avail_check_page() {

	var querysearch = window.location.search;
	if( querysearch == "" ) {
		querysearch = "?avail=1";
	} else {
		querysearch = querysearch + "&avail=1";
	}
	var url = "residential-internet/" + querysearch;
	window.open( url, "_self");

}

function show_internetplans() {

	var panel_plans = document.getElementById("panel-showinternetplans");
	if(panel_plans != undefined) {
		var show_cached_resulted = true;
		var querysearch = window.location.search;
		if( querysearch == "" ) {
		} else {
			let params = new URLSearchParams(querysearch);
			let q = params.get("avail");
			if( q == undefined) {
			} else {
				show_cached_resulted = false;
 				//launch_plan_building_wizard();
				jQuery("#plan-building-wizard-modal").modal();
			}
		}
		RemoveFromCart_Clear();
		if( show_cached_resulted == true ) {
			var ccd = "";
			if( querysearch.length > 0 ) {
				querysearch = querysearch.toLowerCase();
				const params = new URLSearchParams( querysearch );
				if( params.has('ccd') ) {
					ccd = btoa( params.get('ccd') );
				}
			}
			var action = "find_address";
			GetInternetPlansHtml(panel_plans, action, ccd);
		}
	}

}


function show_plans_check_availability() {
	
	jQuery(".plan_buy_now_btn").each(function(){
		jQuery(this).hide();		
	});
	
	jQuery(".plan_check_availability_btn").each(function(){
		jQuery(this).show();		
	});

}


function initAutocomplete() {
  // Create the autocomplete object, restricting the search predictions to
  // geographical location types.
  autocomplete = new google.maps.places.Autocomplete(
      document.getElementById('ssadd'), {types: ['geocode'], componentRestrictions: {country: "ca"} });

  // Avoid paying for data that you don't need by restricting the set of
  // place fields that are returned to just the address components.
  autocomplete.setFields('address_components');
  
  // When the user selects an address from the drop-down, populate the
  // address fields in the form.
  autocomplete.addListener('place_changed', fillInAddress);
}

function fillInAddress() {
  // Get the place details from the autocomplete object.
  var place = autocomplete.getPlace();
  //console.log(place);


  /*for (var component in componentForm) {
    document.getElementById(component).value = '';
    document.getElementById(component).disabled = false;
  }*/

  // Get each component of the address from the place details,
  // and then fill-in the corresponding field on the form.
  for (var i = 0; i < place.address_components.length; i++) {
    var addressType = place.address_components[i].types[0];
    var val = place.address_components[i][componentForm[addressType]];
    //console.log(addressType+":"+val);
    searched_address[addressType] = val;
    
    //fill the fields in manula address search form
    //jQuery("#add_"+addressType).val(val); 
    
    
    /*if (componentForm[addressType]) {
     
      //document.getElementById(addressType).value = val;
      console.log(addressType+":"+val);
      
    }*/
  }
}

// Bias the autocomplete object to the user's geographical location,
// as supplied by the browser's 'navigator.geolocation' object.
function geolocate() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      var geolocation = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      var circle = new google.maps.Circle(
          {center: geolocation, radius: position.coords.accuracy});
      autocomplete.setBounds(circle.getBounds());
    });
  }
}


function showTab(n) {
    // This function will display the specified tab of the form...
    var x = document.getElementsByClassName("tab");
    x[n].style.display = "block";
    
    if (show_steps_box==true) {
		jQuery(".steps-box").show();	
	} else { 
		jQuery(".steps-box").hide();
	} 
    
    //... and fix the Previous/Next buttons:
    if (n == 0) {
        document.getElementById("prevBtn").style.display = "none";
    } else {
        document.getElementById("prevBtn").style.display = "inline";
    }
    if (n == (x.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Submit";
    } else {
        document.getElementById("nextBtn").innerHTML = "Next";
    }
    //... and run a function that will display the correct step indicator:
    fixStepIndicator(n)
}

function nextPrev(n) {
    // This function will figure out which tab to display
    var x = document.getElementsByClassName("tab");
    // Exit the function if any field in the current tab is invalid:
    if (n == 1 && !validateForm()) return false;
    // Hide the current tab:
    x[currentTab].style.display = "none";
    // Increase or decrease the current tab by 1:
    currentTab = currentTab + n;
    // if you have reached the end of the form...
    if (currentTab >= x.length) {
        // ... the form gets submitted:
        document.getElementById("regForm").submit();
        return false;
    }
    // Otherwise, display the correct tab:
    showTab(currentTab);
}

function goto_tab(n) {
    // This function will figure out which tab to display
    var x = document.getElementsByClassName("tab");
    
    // Hide the current tab:
    x[currentTab].style.display = "none";
    // Increase or decrease the current tab by 1:
    currentTab = n;
    
    // Otherwise, display the correct tab:
    showTab(currentTab);
}



function validateForm() {
    // This function deals with validation of the form fields
    var x, y, z, peoplewilluse, dataplan, downloadplan, i, valid = true;
    x = document.getElementsByClassName("tab");
    y = x[currentTab].getElementsByTagName("input");
    // A loop that checks every input field in the current tab:
    for (i = 0; i < y.length; i++) {
        // If a field is empty...
        if (y[i].value == "") {
            // add an "invalid" class to the field:
            y[i].className += " invalid";
            // and set the current valid status to false
            valid = false;
        }
    }
    z = x[currentTab].getElementsByClassName("required");
    // A loop that checks every input field in the current tab:
    for (i = 0; i < z.length; i++) {
        // If a field is empty...
        if (z[i].value == "") {
            // add an "invalid" class to the field:
            z[i].className += " invalid";
            // and set the current valid status to false
            valid = false;
        }
    }
    // peoplewilluse = x[currentTab].getElementsByName('peoplewilluse');
    // // A loop that checks every input field in the current tab:
    // for (i = 0; i < peoplewilluse.length; i++) {
    //     // If a field is empty...
    //     if (peoplewilluse[i].value == "") {
    //         // add an "invalid" class to the field:
    //         peoplewilluse[i].className += " invalid";
    //         // and set the current valid status to false
    //         valid = false;
    //     }
    // }
    
    // dataplan = x[currentTab].getElementsByName('dataplan');
    // // A loop that checks every input field in the current tab:
    // for (i = 0; i < dataplan.length; i++) {
    //     // If a field is empty...
    //     if (dataplan[i].value == "") {
    //         // add an "invalid" class to the field:
    //         dataplan[i].className += " invalid";
    //         // and set the current valid status to false
    //         valid = false;
    //     }
    // }
    // downloadplan = x[currentTab].getElementsByName('downloadplan');
    // // A loop that checks every input field in the current tab:
    // for (i = 0; i < downloadplan.length; i++) {
    //     // If a field is empty...
    //     if (downloadplan[i].value == "") {
    //         // add an "invalid" class to the field:
    //         downloadplan[i].className += " invalid";
    //         // and set the current valid status to false
    //         valid = false;
    //     }
    // }


    // If the valid status is true, mark the step as finished and valid:
    if (valid) {
        document.getElementsByClassName("step")[currentTab].className += " finish";
    }
    return valid; // return the valid status
}

function fixStepIndicator(n) {
    // This function removes the "active" class of all steps...
    var i, x = document.getElementsByClassName("step");
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(" active", "");
    }
    //... and adds the "active" class on the current step:
    x[n].className += " active";
}

// Dg custom Tabs
function activate_dg_custom_tabs () {
	
	jQuery("div.dg_custom_tabs div.dg_custom_tab").each(function(){
		
		if(jQuery(this).hasClass("active")) {
			
			click_dg_custom_tab(jQuery(this));
			
		}
	});
	
	
	jQuery("div.dg_custom_tabs div.dg_custom_tab a.tab_text").click(function(e){
		
		e.preventDefault();	
		click_dg_custom_tab(jQuery(this).parent("div.dg_custom_tab"));	
	});
	
}

function click_dg_custom_tab(x) {
	
	var activate_sec =  x.find("a.tab_text").attr("target-section");
	jQuery(".dgCusTabSection").removeClass("active").hide();
	jQuery(activate_sec).addClass("active").fadeIn(500);
	
	jQuery("div.dg_custom_tabs div.dg_custom_tab").removeClass("active");
	x.addClass("active");
	
}

function cta_buttons_hover_effect () {
	
	jQuery(".dg_tab_cta_btn").hover(function() {
		
		jQuery(this).addClass("orange-button");
		jQuery(this).css("color","#FFF");
		
	} , function() {
		
		jQuery(this).removeClass("orange-button");
		jQuery(this).css("color","");
	});
	
}

function override_texts() {
	
	jQuery("#et_pb_signup_email").attr("placeholder","Enter Your Email");

}

function timeout_function() {
	
	setTimeout(function(){ footer_float_box_toggle('show') },10000);
	
}


function footer_float_box_toggle(tog) {
	
	//console.log('footer_float_box_toggle');
	
	if(dg_getCookie("plan_building_complete")==true) {
		return;
	}
	
	var window_width = jQuery(window).width();
	if (window_width<768) {
		jQuery(".footer_float_box").addClass('mobile');
		return;
	} else {
		jQuery(".footer_float_box").removeClass('mobile');
	}
	
	
	if (tog=="show") {
		
		jQuery(".footer_float_box").addClass("appear");
		jQuery(".footer_float_box").not(".mobile").animate({"right":"0"}, "slow").removeClass("disappear");
		jQuery(".footer_float_box.mobile").animate({"bottom":"0"}, "slow").removeClass("disappear");
		
		
	} else {
		
		
		jQuery(".footer_float_box").not('.mobile').animate({"right":"-220px"}, "slow").addClass("disappear").removeClass("appear");
		jQuery(".footer_float_box.mobile").animate({"bottom":"-220px"}, "slow").addClass("disappear").removeClass("appear");
		
	}
	
	jQuery('.ffb_close').on('click', function(){
	
		jQuery(".footer_float_box").animate({"right":"-220px"}, "slow").addClass("disappear").removeClass("appear");
	
	});
	
	
}

function GetServiceStreet() {

	var street_num = jQuery("#add_streetNumber").val();
	var street_nam = jQuery("#add_streetName").val();
	var street_dir = jQuery("#add_streetDirection").val();
	var street_typ = jQuery("#add_streetType").val();
	return street_num + " " + street_nam + " " + street_dir + " " + street_typ;

}

function GetServiceAddress() {

	var street_num = jQuery("#add_streetNumber").val();
	var street_nam = jQuery("#add_streetName").val();
	var street_dir = jQuery("#add_streetDirection").val();
	var street_typ = jQuery("#add_streetType").val();
	var city = jQuery("#add_municipalityCity").val();
	var prov = jQuery("#add_provinceOrState").val();
	var postal = jQuery("#add_postalCode").val();

	return street_num + " " + street_nam + " " + street_dir + " " + street_typ + " " + city + " " + prov + " " + postal;

}

function fill_address_on_checkout() {

	jQuery("#shipping_first_name").val(jQuery("#billing_first_name").val());
	jQuery("#shipping_last_name").val(jQuery("#billing_last_name").val());
	jQuery("#shipping_address").val(GetServiceAddress());

	jQuery("#modem_shipping_unit_no").val(jQuery("#add_unitNumber").val()); 
	jQuery("#modem_shipping_street").val(GetServiceStreet());
	jQuery("#modem_shipping_city").val(jQuery("#add_municipalityCity").val());
	jQuery("#modem_shipping_state").val(jQuery("#add_provinceOrState").val());
	jQuery("#modem_shipping_postal_code").val(jQuery("#add_postalCode").val());
	jQuery("#modem_shipping_phone").val(jQuery("#phone").val());

	// hide the fields by default
	jQuery(".modem_shipping_fields").hide();

	// set default to ship to service address
	jQuery("#modem_delivery1").attr('checked', true);
	jQuery(".modem_shipping_fields").hide();
	
}

function change_modem_options() {
	
	jQuery("input[name='ModemPlan']").change(function() {
		
		selected_modem =  jQuery("input [name=ModemPlan]:checked").val();
		//console.log(selected_modem);
		
		if(selected_modem=="I Have My Modem") {
			
			jQuery(".own_moden_type").fadeIn(200);
			
		} else {
			
			jQuery(".own_moden_type").fadeOut(200);
		}
		
	}); 
	 
}

function RemoveFromCart_Clear(){
	jQuery.post(
		et_pb_custom.ajaxurl, 
		{
			'action': 'remove_from_cart',
			'type'	 : 'all-types',
		}, 
		function(response) {
			
			response = CleanStringFromSpecialCharacters( response );

			if (response!="success") {
		
				failed = true;	
		
			} else {
				
				
		
			}
		}
	);
}

function UpdatePagesWithNameAddress(user_data) {

	// setting the information on the monthly billing credit card
	jQuery("#cc_monthly_billing_first_name").val( user_data.first_name + " " + user_data.last_name );
	jQuery("#cc_monthly_billing_card_number").val( "" );
	jQuery("#cc_monthly_billing_card_expiry").val( "" );
	jQuery("#cc_monthly_billing_card_cvv").val( "" );
	jQuery("#cc_monthly_billing_postcode").val( "" );

	var address = user_data.street_number + " " + user_data.street_name + " " + user_data.street_type + 
		" " + user_data.street_dir;
	if( user_data.unit_num.length > 0 ) {
		address = "Unit " + user_data.unit_num + " " + address;
	}

	jQuery("#cc_monthly_billing_postcode").val("");
        jQuery("#cc_upfront_billing_postcode").val("");

	jQuery("#bank_monthly_billing_first_name").val( user_data.first_name );
	jQuery("#bank_monthly_billing_last_name").val( user_data.last_name );
	jQuery("#bank_monthly_billing_address").val(address);
	jQuery("#bank_monthly_billing_city").val( user_data.city );
	jQuery("#bank_monthly_billing_postcode").val( user_data.postal_code );
	jQuery("#bank_monthly_billing_phone").val( user_data.phone );
	

	/*
        if( jQuery("input[name=copy_address_checkbox_bank]:checked").val() ) {
                jQuery(".monthly_bank_service_address_fields").hide();
        } else {
                jQuery(".monthly_bank_service_address_fields").show();
        }
	*/

	jQuery("#modem_shipping_street").val( user_data.street_number + " " + user_data.street_name + " " + 
			user_data.street_type + " " + user_data.street_dir);
	jQuery("#modem_shipping_unit_no").val( user_data.unit_num );
	jQuery("#modem_shipping_city").val( user_data.city );
	jQuery("#modem_shipping_state").val( user_data.prov );
	jQuery("#modem_shipping_postal_code").val( user_data.postal_code );
	jQuery("#modem_shipping_phone").val( user_data.phone );
	
	// by default hide modem_shipping, because default is modem_delivery to same address
	jQuery(".modem_shipping_fields").hide();
	
	var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 
	jQuery(".monthly_payment_options").hide();
	jQuery(".monthly_payment_options.monthly_"+monthly_bill_payment_option+"_info").show();

	var upfront_bill_payment_option = jQuery("input[name='checkout[upfront_bill_payment_option]']:checked").val(); 
	jQuery(".upfront_payment_options").hide();
	jQuery(".upfront_payment_options.upfront_"+  upfront_bill_payment_option +"_info").show();

}

function Validate_CustomerInformation () {

	var user_data = {};

	var fname_f = jQuery("#first_name");
	var lname_f = jQuery("#last_name");
	var phone_f = jQuery("#phone");
	var email_f = jQuery("#email");
	var hdyhau_f = ""; //jQuery("#how_did_you_hear_about_us");
	var ref_f = jQuery("#referrer_ecn");

	//var unitType = jQuery("#add_unitType").val() || "";
	//unitType = unitType.trim();

        var unitNumber = jQuery("#add_unitNumber").val() || "";
	unitNumber = unitNumber.trim();

        var buzzerCode = jQuery("#add_buzzerCode").val() || "";
	buzzerCode = buzzerCode.trim();

        var street_number = jQuery("#add_streetNumber").val() || "";
	street_number = street_number.trim();

        var street_name =  jQuery("#add_streetName").val() || "";
	street_name = street_name.trim();

        var street_type =  jQuery("#add_streetType").val() || "";
	street_type = street_type.trim();

        var street_dir =  jQuery("#add_streetDirection").val() || "";
	street_dir = street_dir.trim();

        var locality = jQuery("#add_municipalityCity").val() || "";
	locality = locality.trim();

        var prov = jQuery("#add_provinceOrState").val() || "";
	locality = locality.trim();

        var postal_code = jQuery("#add_postalCode").val() || "";
	postal_code = postal_code.trim();


	user_data.first_name = fname_f.val();
	user_data.last_name  = lname_f.val();
	user_data.phone  = phone_f.val();
	user_data.email  = email_f.val();
	//user_data.how_did_you_hear_about_us = hdyhau_f.val();
	user_data.referrer_ecn = ref_f.val();
	//user_data.unit_type = unitType;
	user_data.unit_num = unitNumber;
	user_data.buzzer_code  = buzzerCode;
	user_data.street_number = street_number;
	user_data.street_name = street_name;
	user_data.street_type = street_type;
	user_data.street_dir = street_dir;
	user_data.city = locality;
	user_data.prov = prov;
	user_data.postal_code = postal_code;

	if( street_number.length <= 0 ) {
		jQuery('#add_streetNumber').addClass("flash_error");
		return false;
	}
	if( street_name.length <= 0 ) {
		jQuery('#add_streetName').addClass("flash_error");
		return false;
	}
	if( locality.length <= 0 ) {
		jQuery('#add_municipalityCity').addClass("flash_error");
		return false;
	}
	if( prov.length <= 0 ) {
		jQuery('#add_provinceOrState').addClass("flash_error");
		return false;
	}
	if( postal_code.length <= 0 ) {
		jQuery('#add_postalCode').addClass("flash_error");
		return false;
	}

	var failed = false;
	if (user_data.first_name=="") {
		fname_f.addClass("flash_error");
		return false;
	} else if (user_data.first_name.length<2) {
		fname_f.addClass("flash_error");
		return false;
	} else {
		fname_f.removeClass("flash_error");
	}

	if (user_data.last_name=="") {
		lname_f.addClass("flash_error");
		failed = true;
		return false;
	} else if (user_data.last_name.length<2 || latterOnly(user_data.last_name)==false) {
		lname_f.addClass("flash_error");
		failed = true;
		return false;
	} 

	if (user_data.phone=="") {
		phone_f.addClass("flash_error");
		failed = true;
		return false;
	} else if (user_data.phone.length<10 || user_data.phone.length>14 ) {
		phone_f.addClass("flash_error");
		failed = true;
		return false;
	} else {
		phone_f.removeClass("flash_error");
	}

	if (user_data.email=="") {
		email_f.addClass("flash_error");
		failed = true;
		return false;
	} else if (email_reg.test(user_data.email) == false) {
		email_f.addClass("flash_error");
		failed = true;
		return false;
	} else {
		email_f.removeClass("flash_error");
	}

	/*else {
				var response = jQuery.get("https://isitarealemail.com/api/email/validate?email=" +user_data.email,
				function responseHandler(data) {
					data.status ==='valid';
					if (data.status === 'valid') {

						email_f.removeClass("flash_error");
					} else {
						email_f.addClass("flash_error");
						failed = true
					}
				})
			}
	*/
	/*
	if (user_data.how_did_you_hear_about_us=="") {
		hdyhau_f.addClass("flash_error");
		failed = true;
		return false;
	} else {
		hdyhau_f.removeClass("flash_error");
	}
	if ( (hdyhau_f.val()=="Friend or family" || hdyhau_f.val()=="Retail store")) {
		if (ref_f.val()=="" || ref_f.val().length<3) {
			ref_f.addClass("flash_error");
			failed = true;	
			return false;
		} else {
			ref_f.removeClass("flash_error");
		}
	} else if (hdyhau_f.val()!="Other") {
		user_data.referrer_name = "";
		ref_f.removeClass("flash_error");

	}
	*/

	return user_data;
}

function SignupWizardNextTap_ex( content_mod ) {
	var nav_mod = jQuery(".signup_steps_nav li.active");
	var sibling = content_mod.next(".dg_cart_module");
	var nav_sibling = nav_mod.next("li");

	content_mod.fadeOut(400);
	content_mod.addClass("done");
	content_mod.removeClass("active");
	nav_mod.addClass("done");
	nav_mod.removeClass("active");
	sibling.fadeIn(400);
	sibling.addClass("active");
	nav_sibling.removeClass("disabled");
	nav_sibling.addClass("active");

	window.scrollTo(0,50);
}

function SignupWizardNextTap() {
	var content_mod = jQuery(".selected_customer_details");
	var nav_mod = jQuery(".signup_steps_nav li.active");

	var sibling = content_mod.next(".dg_cart_module");
	var nav_sibling = nav_mod.next("li");

	content_mod.fadeOut(400);
	content_mod.addClass("done");
	content_mod.removeClass("active");

	nav_mod.addClass("done");
	nav_mod.removeClass("active");

	sibling.fadeIn(400);
	sibling.addClass("active");

	nav_sibling.removeClass("disabled");
	nav_sibling.addClass("active");

	window.scrollTo(0,50);
}

function UpdateUpfrontInfo( data ) {

	jQuery(".confirm_upfront_email-transfer_details").attr("disabled",true);
	jQuery(".confirm_upfront_cc_details").attr("disabled",true);

	var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 
	if( monthly_bill_payment_option == "cc" ) {

		var cc_number = atob( data.cc_monthly_billing_card_number );
		var masked_cc = "";

		if( cc_number.length > 0 ) {
			masked_cc = cc_number.substring(0,4) + " **** **** " + cc_number.substring(13); 
		}

		jQuery("#cc_upfront_billing_full_name").val(data.cc_monthly_billing_full_name);
		jQuery("#cc_upfront_billing_card_number").val(masked_cc);
		jQuery("#cc_upfront_billing_card_expiry").val(data.cc_monthly_billing_card_expiry);
		jQuery("#cc_upfront_billing_card_cvv").val(data.cc_monthly_billing_card_cvv);
		jQuery("#cc_upfront_billing_postcode").val(data.cc_monthly_billing_postcode);

	} else {

		jQuery("#cc_upfront_billing_full_name").val("");
		jQuery("#cc_upfront_billing_card_number").val("");
		jQuery("#cc_upfront_billing_card_expiry").val("");
		jQuery("#cc_upfront_billing_card_cvv").val("");
		jQuery("#cc_upfront_billing_postcode").val("");

	}
}

function UpdateViewUpfrontPaymentDetails(option) {

	var upfront_bill_payment_option = jQuery("input[name='checkout[upfront_bill_payment_option]']:checked").val();

	if (upfront_bill_payment_option && upfront_bill_payment_option !="" && 
		upfront_bill_payment_option != null && typeof upfront_bill_payment_option !==undefined) {

		jQuery(".upfront_payment_options").hide();
		jQuery(".upfront_payment_options.upfront_"+  upfront_bill_payment_option +"_info").show();

	}
}

function UpdateViewMonthlyPaymentDetails(option) {

	var data = {};

	/*
	if( jQuery("input[name=copy_address_checkbox_bank]:checked").val() ) {
		jQuery(".monthly_bank_service_address_fields").hide();
	} else {
		jQuery(".monthly_bank_service_address_fields").show();
	}
	*/

	var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 
	if (monthly_bill_payment_option && monthly_bill_payment_option!="" && 
		monthly_bill_payment_option!=null && typeof monthly_bill_payment_option!==undefined) {

		jQuery(".monthly_payment_options").hide();
		jQuery(".monthly_payment_options.monthly_"+monthly_bill_payment_option+"_info").show();

		//remove payafter deposit if any other method is selected
		if( monthly_bill_payment_option!="payafter" ) {
			jQuery.post(
				et_pb_custom.ajaxurl, 
				{
					'action': 'remove_from_cart',
					'plan_id': 307,
					'category': 'deposit'    
				}, 
				function(response) {

					if (response!="success") {
						failed = true;	
					} else {
						jQuery("body").trigger("update_checkout");
						dg_update_order_review();
					}

				}
			);
		} else {
			jQuery.post(
				et_pb_custom.ajaxurl, 
				{
					'action'    : 'add_plan',
					'plan_id'	: 307,
					'type'		: "deposit",    
				}, 
				function(response) {

					response = CleanStringFromSpecialCharacters( response );
					if (response!="success") {
						failed = true;	
					} else {
						jQuery("body").trigger("update_checkout");
					}
				}
			);
		}

	} else {
		return;
	}
}

function dg_cart_module() {

	jQuery(".cart_checkout_button").attr("disabled",true);
	jQuery(".cart_checkout_button").hide();
	all_validated = true;

	jQuery('#phone').mask("(000) 000-0000");
	//jQuery('#phone').mask("(000) 000-0000");
	
	/*
	jQuery("#how_did_you_hear_about_us").change(function() {
		
		var hdyhau = jQuery("#how_did_you_hear_about_us").val();
		
		if (hdyhau=="Friend or family") {
			
			jQuery("#referrer_name_wrap").show();
			jQuery("#referrer_name").attr("placeholder","Enter their name or account number");
		
		} else if (hdyhau=="Other") {
			
			jQuery("#referrer_name_wrap").show();
			jQuery("#referrer_name").attr("placeholder","Please specify");
		
		} else if (hdyhau=="Retail store") {
			
			jQuery("#referrer_name_wrap").show();
			jQuery("#referrer_name").attr("placeholder","Store referral code");
			
		} else {
			
			jQuery("#referrer_name_wrap").hide();
		}
		
	});
	*/
	
	jQuery("input[name=ModemPlan]").change(function() {
		
		//var v = jQuery("#ihavemymodem").is(":checked");
		//if( v == true ) {
		if (jQuery("input[name=ModemPlan]:checked").val()=="7175" || 
			jQuery("input[name=ModemPlan]:checked").val()=="7174" || 
			jQuery("input[name=ModemPlan]:checked").val()=="7149" || 
			jQuery("input[name=ModemPlan]:checked").val()=="7172" ) {
			jQuery(".modem_delivery_wrapper").hide();
		} else {
			jQuery(".modem_delivery_wrapper").fadeIn(500);
		}
	});
	
	jQuery("input[name=modem_delivery]").change(function() {
		
		if (jQuery("input[name=modem_delivery]:checked").val()=="Ship to a different address") {
			jQuery(".modem_shipping_fields").show();
		} else {
			jQuery(".modem_shipping_fields").hide();
		}
	});
	
	// remove enter key on signup form
	jQuery("form[name='signup']").on('keyup keypress', function(e) {
		var keyCode = e.keyCode || e.which;
		if (keyCode === 13) { 
			console.log('denied');
			e.preventDefault();
			return false;
		}
	});

	/*
	jQuery("#copy_address_checkbox_bank").click(function() {
		
		if (jQuery(this).prop("checked")) {
			
			user_data = Validate_CustomerInformation();

			jQuery("#bank_monthly_billing_address").val(user_data.unit_num + " " + 
				user_data.street_number + " " + user_data.street_name + " " + 
				user_data.street_type + " " + user_data.street_dir);
			jQuery("#bank_monthly_billing_city").val(user_data.city);
			jQuery("#bank_monthly_billing_state option[value='"+user_data.prov+"']").attr("selected",true);
			jQuery("#bank_monthly_billing_postcode").val(user_data.postal_code);
			jQuery("#bank_monthly_billing_phone").val(user_data.phone);
			
			jQuery(".monthly_bank_service_address_fields").hide();

		} else {
			
			jQuery("#bank_monthly_billing_address").val("");
			jQuery("#bank_monthly_billing_city").val("");
			jQuery("#bank_monthly_billing_state option[value='0']").attr("selected",true);
			jQuery("#bank_monthly_billing_postcode").val("");
			jQuery("#bank_monthly_billing_phone").val("");
			
			jQuery(".monthly_bank_service_address_fields").show();
		}
		
	});
	*/

	jQuery(".dg_cart .back_button").click(function(e){
		
		e.preventDefault;
		window.history.go(-1); 
		return false;
		
	});
	
	
// IMP: Handle Confirm button 
jQuery(".dg_cart .confirmation_button").click(function(e) {

	if( jQuery(this).hasClass("terms_button") ) {
		//customer clicked on Agree Terms
		//1. show the order now button
		//2. send information to back end
		
		var user_data = {};

		dismiss_modal_toc();

		user_data.agreed_terms = jQuery("input[name='popup_terms']:checked").val();
		if( user_data.agreed_terms == undefined ) {
			jQuery(".confirm_upfront_email-transfer_details").attr( "disabled", true );
			jQuery(".confirm_upfront_cc_details").attr( "disabled", true);
			return;
		}
		user_data.agreed_terms = "agreed";
		
		jQuery(".confirm_upfront_email-transfer_details").attr( "disabled", false );
		jQuery(".confirm_upfront_cc_details").attr( "disabled", false );

		UpdateUserData( user_data, 7 );

		return;
	}

	// code when user selected the internet plan
	if (jQuery(this).hasClass("confirm_internet_plan")) {

		var user_data = {};

		user_data.selected_internet_plan = jQuery("input[name='InternetPlan']:checked").val();
		if( user_data.selected_internet_plan == undefined ) {
			return;
		}

		// show modems matching the internet plan
		var skus = jQuery("input[name='InternetPlan']:checked + label .internet-sku").text();
		var li = skus.split(",");
		if( li.length > 0 ) {
			jQuery(".modemsku-all").hide(); // hide all
			for( var i = 0; i<li.length; i++ ) {
				var s = li[i];
				/* select modems based on plan */
				var name = ".modemsku-" + s;
				jQuery(name).show();
			}
		}

		UpdateUserData(user_data, 2);

		UpdateBuildYourPlan_AddPlan(user_data.selected_internet_plan, "Internet Plan");

		var stateObj = { "step" : 4 , "dg_rand": dg_rand } 
		history_push_state(stateObj,"Installation","#stepdates");
	}

	// code when customer details page is added
	if (jQuery(this).hasClass("confirm_customer_details_button")) {
		var user_data = Validate_CustomerInformation();

		var email_f = jQuery("#email");
		if(user_data && !email_f.hasClass("flash_error")) {

			jQuery(".confirm_customer_details_button").attr("disabled",true);
			jQuery(".confirm_customer_details_button").html("<i class='fa fa-spinner fa-pulse fa-2x fa-fw'></i><span class='sr-only'>Checking...</span>");
			jQuery(".confirm_customer_details_button").addClass("checking");

			checkAvailability_buildPlan();
			UpdatePagesWithNameAddress(user_data);

			UpdateUserData(user_data, 1);

			var stateObj = { "step" : 2 , "dg_rand": dg_rand } 
			history_push_state(stateObj,"Internet Plan","#stepinternet");
				
			return;

		} else {
			return;
		}
	}

	// code when run the selected the installation dates
	if (jQuery(this).hasClass("confirm_dates_button")) {
			
		var user_data = {};

		var date1_f = ""; //jQuery("#date1");
		var date2_f = ""; //jQuery("#date2");
		var date3_f = ""; //jQuery("#date3");
		var time1_f = jQuery("#time1");
		var time2_f = jQuery("#time2");
		var time3_f = ""; //jQuery("#time3");
		var instructions = jQuery("#install_instructions");
		user_data.preffered_installation_date_1  = ""; //date1_f.val();
		user_data.preffered_installation_date_2  = ""; //date2_f.val();
		user_data.preffered_installation_date_3  = "";// date3_f.val();
		user_data.preffered_installation_time_1  = time1_f.val();
		user_data.preffered_installation_time_2  = time2_f.val();
		user_data.preffered_installation_time_3  = ""; //time3_f.val();
		user_data.installation_instructions = btoa( instructions.val() );

		var failed = false;
		if( user_data.preffered_installation_time_1 == null || user_data.preffered_installation_time_1 == "" ) {
			time1_f.addClass("flash_error");
			failed = true;
		} else {
			time1_f.removeClass("flash_error");
		}
		/*
		if (user_data.preffered_installation_date_1=="" || !isValidDateString(user_data.preffered_installation_date_1)) {
			date1_f.addClass("flash_error");
			time1_f.addClass("flash_error");
			failed = true;
		} else {
			date1_f.removeClass("flash_error");
			time1_f.removeClass("flash_error");
		}

		if (user_data.preffered_installation_date_2=="" || !isValidDateString(user_data.preffered_installation_date_2)) {
			date2_f.addClass("flash_error");
			time2_f.addClass("flash_error");
			failed = true;
		} else {
			date2_f.removeClass("flash_error");
			time2_f.removeClass("flash_error");
		}

		if (user_data.preffered_installation_date_3=="" || !isValidDateString(user_data.preffered_installation_date_3)) {
			date3_f.addClass("flash_error");
			time3_f.addClass("flash_error");
			failed = true;
		} else {
			date3_f.removeClass("flash_error");
			time3_f.removeClass("flash_error");
		}
		*/

		//add more validations in this sections.
		if (!failed && validate_service_dates()) {

			UpdateUserData( user_data, 3 );

		} else {

			return;

		}

	}
		
	if (jQuery(this).hasClass("select_phone_button")) {

		selected_phone = jQuery("input[name='PhonePlan']:checked").val();
		selected_phone_name = jQuery("input[name='PhonePlan']:checked + label .purchase-title").text();
		var failed = false;

		if (selected_phone && selected_phone!="" && selected_phone!=null && typeof selected_phone!==undefined) {

			jQuery('table.woocommerce-checkout-review-order-table').addClass('loading_act');
			jQuery.post(
				et_pb_custom.ajaxurl, 
				{
					'action': 'add_plan',
					'plan_id': selected_phone,
					'type'	 : 'phone'   
				}, 
				function(response) {

					response = CleanStringFromSpecialCharacters( response );

					if (response!="success") {

						failed = true;	

					} else {

						jQuery('.review_order_phone').show();
						jQuery('.display_phone .plan_name').html(selected_phone_name);

						//update cart total
						dg_update_order_review();

					}
				}
			);
			if (failed) {
				return;
			}

		} else {
			return;
		}

		jQuery(".modems_without_phone").hide();
		jQuery(".modems_with_phone").show();
		jQuery(".modems_without_phone input").attr("checked",false);
		jQuery("#ihavemymodem").attr("checked",false);


		var stateObj = { "step" : 5 , "dg_rand": dg_rand }
		history_push_state(stateObj,"Modem Option","#stepmodem"); 
	}

	if (jQuery(this).hasClass("skip_phone_button")) {

		selected_phone = 0;
		selected_phone_name = "None";
		var failed = false;
		jQuery("input[name='PhonePlan']").attr("checked",false);

		jQuery.post(
			et_pb_custom.ajaxurl, 
			{
				'action': 'remove_from_cart',
				'type'	 : 'phone',
				'category' : 'phone-plan'    
			}, 
			function(response) {

				response = CleanStringFromSpecialCharacters( response );

				if (response!="success") {
					failed = true;	
				} else {

					jQuery('.review_order_phone').hide();
					jQuery('.display_phone .plan_name').html(selected_phone_name);
					//update cart total
					dg_update_order_review();

				}
			}
		);

		if (failed) {
			return;
		}

		//enable modem byo
		jQuery(".ex_radio_p").show();
		jQuery(".modems_without_phone").show();
		jQuery(".modems_with_phone").hide();
		jQuery(".modems_with_phone input").attr("checked",false);

		var stateObj = { "step" : 5 , "dg_rand": dg_rand } 
		history_push_state(stateObj,"Modem Option","#stepmodem");
	}

		
	if (jQuery(this).hasClass("select_modem_button")) {

		var selected_modem = jQuery("input[name='ModemPlan']:checked").val();
		var selected_modem_name = jQuery("input[name='ModemPlan']:checked + label .purchase-title").text() + 
			" "+jQuery("input[name='ModemPlan']:checked + label .purchase-text").text();
		var BYO_ModemName = jQuery("#BYO_ModemName").val();
		var modem_delivery = jQuery("input[name=modem_delivery]:checked").val();

		var data = {};
		if (selected_phone == 0 && jQuery("#ihavemymodem").is(":checked") && BYO_ModemName.length < 3 ) {
			jQuery("#BYO_ModemName").addClass("flash_error");
			return false;
		} else {
			jQuery("#BYO_ModemName").removeClass("flash_error");
		}

		if (!jQuery("#ihavemymodem").is(":checked") && 
			jQuery("#modem_delivery2").is(":checked")) {

			var modem_shippping_field_error = false;  
			jQuery(".modem_shippping_field").each(function() {
				data[jQuery(this).attr("name")] = jQuery(this).val();
				if (jQuery(this).hasClass('required') && jQuery(this).val().length<2) {
					jQuery(this).addClass("flash_error");
					modem_shippping_field_error = true;				
				} else {
					jQuery(this).removeClass("flash_error");
				}
			});

			if (modem_shippping_field_error) {
				return false;	
			} else {
				var modem_shippping_address = data['modem_shipping_unit_no']+ 
					" "+
					data['modem_shipping_street']+" "+
					data['modem_shipping_city']+" "+
					data['modem_shipping_state']+" "+
					data['modem_shipping_postal_code']+" Phone "+
					data['modem_shipping_phone'];
			}

		} else {

			//jQuery("#alternate_modem_delivery_address").removeClass("flash_error");
		}


		if (selected_modem && selected_modem!="" && selected_modem!=null && 
			typeof selected_modem!==undefined) {

			jQuery('.modem_error_message').hide();
			jQuery('table.woocommerce-checkout-review-order-table').addClass('loading_act');

			jQuery.post(
				et_pb_custom.ajaxurl, 
				{
					'action': 'add_plan',
					'plan_id': selected_modem,
					'type'	 : 'modem'
				}, 
				function(response) {

					response = CleanStringFromSpecialCharacters( response );

					if (response!="success") {
						return;	
					} else {

						jQuery('.display_modem .plan_name').html(selected_modem_name);
						if (!jQuery("#ihavemymodem").is(":checked") && jQuery("#modem_delivery2").is(":checked")) {
							jQuery('.display_modem .plan_name').append("<br>Delivered to: "+modem_shippping_address);	
						}

						//update cart total
						dg_update_order_review();

					}
				}
			);

			data.modem_delivery = modem_delivery;
			data.modem_shipping_unit_no = jQuery("#modem_shipping_unit_no").val();
                        data.modem_shipping_street = jQuery("#modem_shipping_street").val();
                        data.modem_shipping_city = jQuery("#modem_shipping_city").val();
                        data.modem_shipping_state = jQuery("#modem_shipping_state").val();
                        data.modem_shipping_postal_code = jQuery("#modem_shipping_postal_code").val();
                        data.modem_shipping_phone = jQuery("#modem_shipping_phone").val();

			if (modem_delivery=="Ship to a different address") {
				data.alternate_modem_delivery_address = modem_shippping_address;
				data.BYO_ModemName = "";	
			} else {
				//data.alternate_modem_delivery_address = "";
				data.BYO_ModemName = jQuery("#BYO_ModemName").val();
			}


			UpdateUserData( data, 5 );

			var stateObj = { "step" : 6 , "dg_rand": dg_rand }
			history_push_state(stateObj,"Review Monthly Payment","#stepmonthly");

		} else {
			jQuery('.modem_error_message').show();
			//do not proceed;
			return;
		}
	}

	//Code when customer add the Monthly Billing Credit Card
	if (jQuery(this).hasClass("confirm_cc_details")) {

		var data = {};

		var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 
		var cc_monthly_billing_full_name = jQuery("input[name='checkout[monthly_cc][billing_full_name]']").val();
		var cc_monthly_billing_card_number = jQuery("input[name='checkout[monthly_cc][billing_card_number]']").val();
		var cc_monthly_billing_card_expiry = jQuery("input[name='checkout[monthly_cc][billing_card_expiry]']").val();
		var cc_monthly_billing_card_cvv = jQuery("input[name='checkout[monthly_cc][billing_card_cvv]']").val();
		var cc_monthly_billing_postcode = jQuery("input[name='checkout[monthly_cc][billing_postcode]']").val();

		if ( cc_monthly_billing_full_name.length<3   ) {
			jQuery("input[name='checkout[monthly_cc][billing_full_name]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_cc][billing_full_name]']").removeClass("flash_error");
		}

		var cc_number = cc_monthly_billing_card_number.replace(/ /g, '');

		//TODO addback the validation
		var cc_validation = true; //valid_credit_card( cc_number );

		if (cc_monthly_billing_card_number.length<14 || !cc_validation || 
			jQuery("input[name='checkout[monthly_cc][billing_card_number]']").hasClass('invalid-card-type')) {
			jQuery("input[name='checkout[monthly_cc][billing_card_number]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_cc][billing_card_number]']").removeClass("flash_error");
		}

		if( ValidateCreditCardExpiry( cc_monthly_billing_card_expiry, "checkout[monthly_cc][billing_card_expiry]" ) == false ) {
			failed = true;
		}

		if (cc_monthly_billing_card_cvv==undefined || cc_monthly_billing_card_cvv=="" || 
			cc_monthly_billing_card_cvv==0 || cc_monthly_billing_card_cvv.length<3) {
			jQuery("input[name='checkout[monthly_cc][billing_card_cvv]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_cc][billing_card_cvv]']").removeClass("flash_error");
		}

		if (cc_monthly_billing_postcode=="") {
			jQuery("input[name='checkout[monthly_cc][billing_postcode]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_cc][billing_postcode]']").removeClass("flash_error");
		}

		if (!failed) {	

			data.monthly_bill_payment_option = monthly_bill_payment_option;
			data.cc_monthly_billing_card_number = btoa( cc_monthly_billing_card_number );
			data.cc_monthly_billing_card_expiry = cc_monthly_billing_card_expiry;
			data.cc_monthly_billing_card_cvv = cc_monthly_billing_card_cvv;
			data.cc_monthly_billing_full_name = cc_monthly_billing_full_name;
			data.cc_monthly_billing_postcode = cc_monthly_billing_postcode;

			data.bank_monthly_billing_first_name = "";
			data.bank_monthly_billing_last_name = "";
                        //data.bank_monthly_billing_address = "";
                        //data.bank_monthly_billing_city = "";
                        //data.bank_monthly_billing_state = "";
                        //data.bank_monthly_billing_postcode = "";
                        //data.bank_monthly_billing_phone = "";

			data.bank_monthly_billing_account_type = "";
                        data.bank_monthly_billing_financial_institution =  "";
                        data.bank_monthly_billing_transit_number = "";
                        data.bank_monthly_billing_institution_number = "";
                        data.bank_monthly_billing_account_number = "";

			//UpdateUserData is important here, because it will validate the credit card
			UpdateUserData( data, 6,  jQuery(this).parents(".dg_cart_module") );
			UpdateUpfrontInfo( data );

		} else {

			return;

		}

		// do not go to the next step until the credit card validation finish
		//var stateObj = { "step" : 7 , "dg_rand": dg_rand } 
		//history_push_state(stateObj,"Review Upfront Payment","#stepupfront");
		return;
	}

	//Monthly Billing Bank
	if (jQuery(this).hasClass("confirm_bank_details")) {

		//hide copy cc checkbox
		if (copy_cc_field_added) {
			jQuery(".copy_cc_field").hide();
		}

		var data = {};

		var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 
		var bank_monthly_billing_account_type = jQuery("input[name='checkout[monthly_bank][account_type]']:checked").val();

		var bank_monthly_billing_financial_institution = jQuery("input[name='checkout[monthly_bank][financial_institution]']").val();
		var bank_monthly_billing_transit_number = jQuery("input[name='checkout[monthly_bank][transit_number]']").val();
		var bank_monthly_billing_institution_number = jQuery("input[name='checkout[monthly_bank][institution_number]']").val();
		var bank_monthly_billing_account_number = jQuery("input[name='checkout[monthly_bank][account_number]']").val();

		var bank_monthly_billing_first_name = jQuery("input[name='checkout[monthly_bank][billing_first_name]']").val();
		var bank_monthly_billing_last_name = jQuery("input[name='checkout[monthly_bank][billing_last_name]']").val();

		//var bank_monthly_billing_address = jQuery("input[name='checkout[monthly_bank][billing_address]']").val();
		//var bank_monthly_billing_city = jQuery("input[name='checkout[monthly_bank][billing_city]']").val();
		//var bank_monthly_billing_state = jQuery("select[name='checkout[monthly_bank][billing_state]']").val();
		//var bank_monthly_billing_postcode = jQuery("input[name='checkout[monthly_bank][billing_postcode]']").val();
		//var bank_monthly_billing_phone = jQuery("input[name='checkout[monthly_bank][billing_phone]']").val();

		if ( !bank_monthly_billing_account_type || 
			bank_monthly_billing_account_type=="" || 
			bank_monthly_billing_account_type==undefined || 
			bank_monthly_billing_account_type==0 ) {
			jQuery("#bank_monthly_billing_account_type_fields").css("color","red");
			failed = true;
		} else {
			jQuery("#bank_monthly_billing_account_type_fields").css("color","");
		}

		if (bank_monthly_billing_financial_institution=="") {
			jQuery("input[name='checkout[monthly_bank][financial_institution]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][financial_institution]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_transit_number=="" || bank_monthly_billing_transit_number.length!=5) {
			jQuery("input[name='checkout[monthly_bank][transit_number]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][transit_number]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_institution_number==""|| bank_monthly_billing_institution_number.length!=3) {
			jQuery("input[name='checkout[monthly_bank][institution_number]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][institution_number]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_account_number==""|| bank_monthly_billing_account_number.length <7 || bank_monthly_billing_account_number.length > 12) {
			jQuery("input[name='checkout[monthly_bank][account_number]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][account_number]']").removeClass("flash_error");
		}


		if (bank_monthly_billing_first_name=="" || bank_monthly_billing_first_name.length < 2 || (latterOnly(bank_monthly_billing_first_name)==false)) {
			jQuery("input[name='checkout[monthly_bank][billing_first_name]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_first_name]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_last_name==""|| bank_monthly_billing_last_name.length < 2 || (latterOnly(bank_monthly_billing_last_name)==false)) {
			jQuery("input[name='checkout[monthly_bank][billing_last_name]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_last_name]']").removeClass("flash_error");
		}


		/*
		if (bank_monthly_billing_address=="") {
			jQuery("input[name='checkout[monthly_bank][billing_address]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_address]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_city=="") {
			jQuery("input[name='checkout[monthly_bank][billing_city]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_city]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_state=="" || bank_monthly_billing_state==0) {
			jQuery("select[name='checkout[monthly_bank][billing_state]']").addClass("flash_error");
			failed = true;
		} else {

			//console.log(bank_monthly_billing_state);
			jQuery("select[name='checkout[monthly_bank][billing_state]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_postcode=="") {
			jQuery("input[name='checkout[monthly_bank][billing_postcode]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_postcode]']").removeClass("flash_error");
		}

		if (bank_monthly_billing_phone=="") {
			jQuery("input[name='checkout[monthly_bank][billing_phone]']").addClass("flash_error");
			failed = true;
		} else {
			jQuery("input[name='checkout[monthly_bank][billing_phone]']").removeClass("flash_error");
		}
		*/

		if (!failed) {	

			data.monthly_bill_payment_option = monthly_bill_payment_option;
			data.bank_monthly_billing_first_name = bank_monthly_billing_first_name;
			data.bank_monthly_billing_last_name = bank_monthly_billing_last_name;
			//data.bank_monthly_billing_address = bank_monthly_billing_address;
			//data.bank_monthly_billing_city = bank_monthly_billing_city;
			//data.bank_monthly_billing_state = bank_monthly_billing_state;
			//data.bank_monthly_billing_postcode = bank_monthly_billing_postcode;
			//data.bank_monthly_billing_phone = bank_monthly_billing_phone;

			data.bank_monthly_billing_account_type = bank_monthly_billing_account_type;
                	data.bank_monthly_billing_financial_institution = bank_monthly_billing_financial_institution;
			data.bank_monthly_billing_transit_number = bank_monthly_billing_transit_number;
			data.bank_monthly_billing_institution_number = bank_monthly_billing_institution_number;
			data.bank_monthly_billing_account_number = bank_monthly_billing_account_number;

			data.cc_monthly_billing_card_number = "";
			data.cc_monthly_billing_card_expiry = "";
                        data.cc_monthly_billing_card_cvv = "";
                        data.cc_monthly_billing_full_name = "";
                        data.cc_monthly_billing_postcode = "";

			UpdateUserData( data, 6,  jQuery(this).parents(".dg_cart_module") );
			UpdateUpfrontInfo( data );

		} else {

			return;

		}

		// do not go to next step yet, until UpdateUserData finish
		//var stateObj = { "step" : 7 , "dg_rand": dg_rand } 
		//history_push_state(stateObj,"Review Upfront Payment","#stepupfront");
		return;

	}

	//Monthly Billing Payafter
	if (jQuery(this).hasClass("confirm_payafter_details")) {

		var data = {};
		var monthly_bill_payment_option = jQuery("input[name='checkout[monthly_bill_payment_option]']:checked").val(); 

		failed = false;

		if (!failed) {
			data.monthly_bill_payment_option = monthly_bill_payment_option;

                        data.bank_monthly_billing_first_name = "";
                        data.bank_monthly_billing_last_name = "";

                        //data.bank_monthly_billing_address = "";
                        //data.bank_monthly_billing_city = "";
                        //data.bank_monthly_billing_state = "";
                        //data.bank_monthly_billing_postcode = "";
                        //data.bank_monthly_billing_phone = "";

			data.bank_monthly_billing_account_type = "";
                        data.bank_monthly_billing_financial_institution = "";
                        data.bank_monthly_billing_transit_number = "";
                        data.bank_monthly_billing_institution_number = "";
                        data.bank_monthly_billing_account_number = "";

			data.cc_monthly_billing_card_number = "";
			data.cc_monthly_billing_card_expiry = "";
                        data.cc_monthly_billing_card_cvv = "";
                        data.cc_monthly_billing_full_name = "";
                        data.cc_monthly_billing_postcode = "";
			jQuery.post(
				et_pb_custom.ajaxurl, 
				{
					'action'    : 'add_plan',
					'plan_id'	: 307,
					'type'		: "deposit",    
				}, 
				function(response) {
					response = CleanStringFromSpecialCharacters( response );
					if (response!="success") {
						failed = true;	
					} else {
						jQuery("body").trigger("update_checkout");
						dg_update_order_review();
					}
				}
			);
		} else {

			return;
		}

		if (failed) {
			return;
		} else {
			UpdateUserData ( data, 6 );
			UpdateUpfrontInfo( data );
		}

		//var stateObj = { "step" : 7 , "dg_rand": dg_rand } 
		//history_push_state(stateObj,"Review Upfront Payment","#stepupfront");
	}

	//Upfront Billing Summary
	if (jQuery(this).hasClass("confirm_upfront_email-transfer_details")) {

		var data = {};

		var upfront_bill_payment_option = jQuery("input[name='checkout[upfront_bill_payment_option]']:checked").val(); 
		if (upfront_bill_payment_option && upfront_bill_payment_option!="" && upfront_bill_payment_option!=null && 
			typeof upfront_bill_payment_option!==undefined) {

			data.upfront_bill_payment_option = upfront_bill_payment_option;
			data.onboarding_stage = "checkout_initiated";
			data.order_step = 7;
	
			jQuery(".confirm_upfront_email-transfer_details").attr("disabled",true);
			jQuery(".confirm_upfront_email-transfer_details").html("<i class='fa fa-spinner fa-pulse fa-2x fa-fw'></i><span class='sr-only'>Checking...</span>");
			jQuery(".confirm_upfront_email-transfer_details").addClass("checking");

			jQuery.post(

				et_pb_custom.ajaxurl,
				{
					'action': 'complete_order',
					'data': data 
				},
				function(response) {

					res = response;
					if( res.length > 0 ) {
						response = JSON.parse(res);

						if (response.status == "success" ) {

							SubmitSignup();

							return;
						}
					}

					alert(" Error processing order " + response.msg );
					jQuery(".confirm_upfront_email-transfer_details").attr("disabled", false);
					jQuery(".confirm_upfront_email-transfer_details").html("Order Now");
					jQuery(".confirm_upfront_email-transfer_details").removeClass("checking");
					return;
				}
			);

		} else {

			jQuery(".upfront_bill_payment_option").css("color","red");
			return;

		}
		return;
	}

	if (jQuery(this).hasClass("confirm_upfront_cc_details")) {

		var data = {};
		var upfront_bill_payment_option = jQuery("input[name='checkout[upfront_bill_payment_option]']:checked").val(); 
		if (upfront_bill_payment_option && upfront_bill_payment_option!="" && upfront_bill_payment_option!=null && 
			typeof upfront_bill_payment_option!==undefined) {

			var failed = false;

			data.upfront_bill_payment_option = upfront_bill_payment_option;

			/*
			jQuery(".upfront_bill_payment_option").css("color","");
			toggle_wc_payment_options();
			*/

			data.onboarding_stage = "checkout_initiated";
			data.order_step = 7;
	
                        data.upfront_billing_full_name = jQuery("#cc_upfront_billing_full_name").val();
			if( typeof data.upfront_billing_full_name == undefined ||  data.upfront_billing_full_name.length < 3 ) {
				jQuery("input[name='checkout[upfront_cc][billing_full_name]']").addClass("flash_error");
				failed = true;
			} else {
				jQuery("input[name='checkout[upfront_cc][billing_full_name]']").removeClass("flash_error");
			}

			var card_cc = jQuery('#cc_upfront_billing_card_number').val();
			if( card_cc.indexOf( "**** ****" ) > 0 ) {
				card_cc = jQuery("#cc_monthly_billing_card_number"). val();
			} else {
				var cc_number = card_cc.replace(/ /g, '');
				var cc_validation = true; //valid_credit_card( cc_number );
				if (cc_number.length<14 || !cc_validation ||
					jQuery("input[name='checkout[upfront_cc][billing_card_number]']").hasClass('invalid-card-type')) {
					jQuery("input[name='checkout[upfront_cc][billing_card_number]']").addClass("flash_error");
					failed = true;
				} else {
					jQuery("input[name='checkout[upfront_cc][billing_card_number]']").removeClass("flash_error");
				}
			}

                        data.upfront_billing_card_number = btoa( card_cc );
                        data.upfront_billing_card_expiry = jQuery("#cc_upfront_billing_card_expiry").val();
			if( ValidateCreditCardExpiry( data.upfront_billing_card_expiry, "checkout[upfront_cc][billing_card_expiry]" ) == false ) {
				return;
			}

                        data.upfront_billing_card_cvv = jQuery("#cc_upfront_billing_card_cvv").val();
                        data.upfront_billing_postcode = jQuery("#cc_upfront_billing_postcode").val();

			if( failed == true ) {
				return;
			}

			jQuery(".confirm_upfront_cc_details").attr("disabled",true);
			jQuery(".confirm_upfront_cc_details").html("<i class='fa fa-spinner fa-pulse fa-2x fa-fw'></i><span class='sr-only'>Checking...</span>");
			jQuery(".confirm_upfront_cc_details").addClass("checking");

			jQuery.post(
				et_pb_custom.ajaxurl,
				{
					'action': 'complete_order',
					'data': data 
				},
				function(response) {
					res = response; //CleanStringFromSpecialCharacters( response );
					if( res.length > 0 ) {
						response = JSON.parse(res);
						if (response.status == "success" ) {
							SubmitSignup();
							return;
						} 
					}
					alert(" Error processing order " + response.msg );
					jQuery(".confirm_upfront_cc_details").attr("disabled", false);
					jQuery(".confirm_upfront_cc_details").html("Order Now");
					jQuery(".confirm_upfront_cc_details").removeClass("checking");
					return;
				}
			);

		} else {

			jQuery(".upfront_bill_payment_option").css("color","red");
			return;
		}

		return;

	}
		
	var content_mod = jQuery(this).parents(".dg_cart_module");
	var nav_mod = jQuery(".signup_steps_nav li.active");

	var sibling = content_mod.next(".dg_cart_module");
	var nav_sibling = nav_mod.next("li");


	content_mod.fadeOut(400);
	content_mod.addClass("done");
	content_mod.removeClass("active");

	nav_mod.addClass("done");
	nav_mod.removeClass("active");


	sibling.fadeIn(400);
	sibling.addClass("active");

	nav_sibling.removeClass("disabled");
	nav_sibling.addClass("active");

	window.scrollTo(0,50); 
});
	
	jQuery(".signup_steps_nav li").click(function(e) { 

		var this_nav = jQuery(this);
		if (this_nav.hasClass("last_checkout") || this_nav.hasClass("disabled") )  {
			return;
		}

		var this_nav_index = this_nav.index();

		//signup page
		if (this_nav_index==0 && jQuery("body").hasClass("page-id-2935")) {
			var stateObj = {"step" : 1 , "dg_rand": dg_rand };
			history_push_state(stateObj,"Internet Plan","#stepinternet");

			//signup page
		} else if (this_nav_index==0 && jQuery("body").hasClass("page-id-2867")) {
			var stateObj = {"step" : 1 , "dg_rand": dg_rand  };
			history_push_state(stateObj,"Review Order","#steprevieworder");
		}

		change_cart_step (this_nav_index);

	});

}


function change_cart_step (step_number) {
	
	jQuery(".dg_cart_module").removeClass("active");
	jQuery(".signup_steps_nav li").removeClass("active");
	jQuery(".dg_cart_module").hide();
	jQuery(".dg_cart_module").eq(step_number).addClass("active");
	jQuery(".signup_steps_nav li").eq(step_number).addClass("active");
	jQuery(".dg_cart_module").eq(step_number).show();
	
} 

function dg_get_order_summary_page() {

	jQuery.post(
	    et_pb_custom.ajaxurl, 
	    {
	        'action': 'get_order_summary_page',    
	    }, 
	    function(response) {
			
			jQuery(".dg_get_order_summary_page").html(response);
			
	    }
	);

}

function dg_update_order_review_tab() {
	
	jQuery.post(
	    et_pb_custom.ajaxurl, 
	    {
	        'action': 'get_upfront_fee_json',    
	    }, 
	    function(response) {
			
			form_update_order_summary(response);
			

	    }
	);
}

function dg_update_order_review () {
	
	jQuery(".cart_checkout_button").attr("disabled",true);
	jQuery.post(
	    et_pb_custom.ajaxurl, 
	    {
	        'action': 'update_order_review',    
	    }, 
	    function(response) {
			
			jQuery( '#order_review' ).html( jQuery.trim( response ) );
			jQuery(".cart_checkout_button").attr("disabled",false);
			
	    }
	);
}

//On cart update order summary. 
jQuery("body").on("update_checkout" , function() {
	
	 toggle_wc_payment_options();
	 checkout_cart_double_display_issue();

});

jQuery("body").on("updated_checkout" , function() {
	
	 toggle_wc_payment_options();
	 checkout_cart_double_display_issue();

});


function checkout_cart_double_display_issue() {
	
	jQuery(".shop_table.monthly-total").eq(1).remove();
	jQuery(".shop_table.upfront-total").eq(1).remove();
	
}

function service_date_validations(field) {
	
	//var today = new Date();
	var this_date = field.val();
	var js_this_date = new Date(this_date);
	
	var plus_seven_days = new Date(dg_today);
	plus_seven_days.setDate(dg_today.getDate() + (default_days-1) );
	
	//console.log(dg_today);
	//console.log(this_date);
	//console.log(js_this_date);
	//console.log(plus_seven_days);
	
	if (!isValidDateString(this_date)) {
		
		return "Please enter a valid date in MM/DD/YYYY format";
	
	} else if (!if_date_serviceble(js_this_date)) {
		
		return "This date is not available for service";
		
	} else if (js_this_date.getDay() == 0)  {
		
		return "We could not provide service on sundays";
		
	} else if (js_this_date < plus_seven_days) {
		
		return "Please select a date at least "+(default_days-1)+" business days from today";
	
	} else if (!check_for_same_dates()) {
		
		
	
	} else {
		
		return true;
		
	}
	
}

function isValidDateString(s) {
  var bits = s.split('/');
  var d = new Date(bits[0] + '/' + bits[1] + '/' + bits[2]);
  return !!(d && (d.getMonth() + 1) == bits[0] && d.getDate() == Number(bits[1]));
}

function validate_service_dates () {
	return true;
/*
	var d1 = service_date_validations(jQuery("#date1"));
	var d2 = service_date_validations(jQuery("#date2"));
	var d3 = service_date_validations(jQuery("#date3"));
	
	var valid1 = show_dates_error("date1",d1);
	var valid2 = show_dates_error("date2",d2);
	var valid3 = show_dates_error("date3",d3);
	//var valid4 = check_for_same_dates();
	
	if ( valid1 && valid2 && valid3 ) {
		
		if (check_for_same_dates()) {
			return true;	
		}
		
	} else {
		return false;
	}
	
*/
}

function calculate_dg_service_days () {
	
	var days = default_days;
	var d_today = new Date();
	d_today.setHours(0,0,0,0);
	
	
	
	if (dg_today) {
		
		// if user system clock is in past or future. 
		if (d_today<dg_today || d_today>dg_today ) {
			
			var diff_days =  Math.round((dg_today-d_today)/(1000*60*60*24));
			//console.log(diff_days);
			days = days + diff_days;
			
			
		} 
		
		
		//console.log(temp_date);
		var to_be_d = default_days;
		
		for (var i=0 ;  i<=to_be_d ; i++ ) {
			
			var temp_date = new Date(dg_today);
			//console.log(i);
			temp_date.setDate(temp_date.getDate() + parseInt(i));
			//console.log(temp_date);
			
			//if sunday then increment
			if (temp_date.getDay() === 0) {
				//console.log("Sunday");
				days++;
				to_be_d++;
			
			//if holiday then increment
			} else if (!if_date_serviceble(temp_date))  {
				//console.log("Holiday");
				days++;
				to_be_d++;				
			} 

			
		}
		
	} 
	
	//default_days = days;
	//console.log(days);
	return days;
	
	
}

function if_date_serviceble (d) {
	
	var serviceble = true;
	
	invalid_service_dates.forEach(function(index,value){
		
		if (!serviceble) return;
		
		var new_date = new Date(index);
		//new_date.setHours(0,0,0,0);
		
		
		if (new_date.getFullYear()==d.getFullYear() && new_date.getMonth()==d.getMonth() && new_date.getDate()==d.getDate()) {
			
			//console.log("O1 "+d);
			//console.log("O2 "+new_date);
			
			serviceble =  false;
			return;
			
		}
	});
	
	
	
	return serviceble;	
	
}


function validate_date_fields() {
	
	return true;

	var days = calculate_dg_service_days();
	
	//console.log(days);
	
	var min_date = new Date();
	min_date.setHours(0,0,0,0);
	
	//if (dg_today==min_date) {
	//	min_date.setDate(dg_today.getDate() + (days));	
	//} else {
		min_date.setDate(min_date.getDate() + days);
	//}
	
	//console.log(min_date);	

	
	var datepicker_option = { 	'dateFormat':"mm/dd/yy" , 
								"minDate":"+"+days+"D" , 
								'beforeShowDay': function(d) {
									
									d.setHours(0,0,0,0);
											
									/*if (d <= dg_today) {
										
										return [false];
										
									}*/
									
									if (d.getDay() === 0) {
									
										return [false];	
									
									}
									
									else if (!if_date_serviceble(d)) { 
										
										 //console.log(d +" "+ if_date_serviceble(d));
										 return [false];
										 
									} 
									
									return [true];		
							},
							
							
		
		 };
	
	
	jQuery("#date1").datepicker(datepicker_option).change(function(){
		
		var valid = service_date_validations(jQuery(this));
		show_dates_error("date1",valid);
	
	}).val(('0' + (min_date.getMonth()+1)).slice(-2) + '/'
             + ('0' + (min_date.getDate())).slice(-2) + '/'
             + min_date.getFullYear());
	
	
	
	
	jQuery("#date2").datepicker(datepicker_option).change(function(){
		
		var valid = service_date_validations(jQuery(this));
		show_dates_error("date2",valid);
	});
	
	jQuery("#date3").datepicker(datepicker_option).change(function(){
		
		var valid = service_date_validations(jQuery(this));
		show_dates_error("date3",valid);
	
	});
	
	
	jQuery("#time1").change(function(){
		
		var valid = service_date_validations(jQuery("#date1"));
		show_dates_error("date1",valid);
	
	});	
	
	jQuery("#time2").change(function(){
		
		var valid = service_date_validations(jQuery("#date2"));
		show_dates_error("date2",valid);
	});
	
	jQuery("#time3").change(function(){
		
		var valid = service_date_validations(jQuery("#date3"));
		show_dates_error("date3",valid);
	
	});
}

function show_dates_error(field,valid) {
	
	if (valid===true) {
		jQuery("#"+field).removeClass("flash_error");
		jQuery("#"+field+"_error").html("");
		return true;
		
	} else {
		jQuery("#"+field).addClass("flash_error");
		jQuery("#"+field+"_error").html(valid);
		return false;
	}
}

function check_for_same_dates() {
	
	var valid = true;
	
	
	if ((jQuery("#date1").val() == jQuery("#date2").val()) &&  (jQuery("#time1").val() == jQuery("#time2").val())) {
		 
		 show_dates_error("date1","Installation date and time preferences cannot be the same. Please choose a different time.");
		 show_dates_error("date2","Installation date and time preferences cannot be the same. Please choose a different time.");
		 
		 return false;
	
	 } else {
	 
		 show_dates_error("date1",true);
		 show_dates_error("date2",true);
		 
		 //valid = true;
	 
	 } 
	 
	 
	 if ((jQuery("#date1").val() == jQuery("#date3").val()) &&  (jQuery("#time1").val() == jQuery("#time3").val()) ) {
	 
		 show_dates_error("date1","Installation date and time preferences cannot be the same. Please choose a different time.");
		 show_dates_error("date3","Installation date and time preferences cannot be the same. Please choose a different time.");
		 
		 return false;
	 
	 } else {
	 
		 show_dates_error("date1",true);
		 show_dates_error("date3",true);
		 
		 //valid = true;
	 
	 }
	 
	 
	 if ( (jQuery("#date2").val() == jQuery("#date3").val()) && (jQuery("#time2").val() == jQuery("#time3").val()) ) {
		 
		 show_dates_error("date2","Installation date and time preferences cannot be the same. Please choose a different time.");
		 show_dates_error("date3","Installation date and time preferences cannot be the same. Please choose a different time.");
		 
		 return false;
		 
	 }  else {
	 
		 show_dates_error("date2",true);
		 show_dates_error("date3",true);
		 
		 //valid = true;
	 
	 }
	 
	 
	 return valid;	
}

function toggle_wc_payment_options() {
	
	
	var upfront_bill_payment_option = jQuery("input[name='checkout[upfront_bill_payment_option]']:checked").val(); 
			
	if (upfront_bill_payment_option && upfront_bill_payment_option!="" && upfront_bill_payment_option!=null && typeof upfront_bill_payment_option!==undefined) {
	
		if(upfront_bill_payment_option=="cc") {
						
			//console.log(upfront_bill_payment_option);
			
			jQuery("#payment_method_moneris").attr("checked",true);
			jQuery("#payment_method_emt").attr("checked",false);
			
			jQuery(".payment_method_moneris_wrapper").show();
			jQuery(".payment_box.payment_method_moneris").show();
			
			jQuery(".payment_method_emt_wrapper").hide();
			jQuery(".payment_box.payment_method_emt").hide();
			
			jQuery(".woocommerce-billing-fields__field-wrapper").show();		
			
		} else if (upfront_bill_payment_option=="email-transfer"){
			
			//console.log(upfront_bill_payment_option);
			
			jQuery("#payment_method_moneris").attr("checked",false);
			jQuery("#payment_method_emt").attr("checked",true);
			
			jQuery(".payment_method_moneris_wrapper").hide();
			jQuery(".payment_box.payment_method_moneris").hide();
			
			jQuery(".payment_method_emt_wrapper").show();
			jQuery(".payment_box.payment_method_emt").show();
			
			jQuery(".woocommerce-billing-fields__field-wrapper").hide();
			
		}
		
	}
}

 
jQuery(".plan_buy_now_btn").click( function ( e ) {
	
	e.preventDefault();
	var plan_id = jQuery(this).attr("data-plan-id");
	var this_button = jQuery(this);
	
	this_button.attr("disabled",true);
	this_button.html("Redirecting...");
	this_button.addClass("redirecting");	
		
	jQuery.post(
	    et_pb_custom.ajaxurl, 
	    {
	        'action': 'add_plan',
	        'plan_id': plan_id    
	    }, 
	    function(response) {
			
			response = CleanStringFromSpecialCharacters( response );

			if (response=="success") {
				
				window.location.href="/residential/signup/";	
			
			} else {
				
				this_button.attr("disabled",false);
				this_button.html("Buy Now");
				this_button.removeClass("redirecting");
				alert("Some error Occured. Try Again");
				
			} 
	    }
	);
});


function load_phone_rates () {
	

	var phone_rates;
			
	jQuery.post(
	    et_pb_custom.ajaxurl, 
	    {
	        'action': 'get_ld_rates',    
	    }, 
	    function(response) {
			
			phone_rates = jQuery.parseJSON(response);
			
			jQuery(".phone_rate").html(phone_rates[0].rate);
			jQuery(".country_code").html(phone_rates[0].code);
			
			Object.keys(phone_rates).forEach(function(index){
				
				jQuery("#select_country").append("<option value='"+index+"'>"+phone_rates[index].name+"</option>");
			
			});
			
			
	    }
	);
	
	jQuery("#select_country").change(function() {
		
		var index = jQuery(this).val();
		var rate = phone_rates[index]['rate'];
		var code = phone_rates[index]['code'];
		
		jQuery(".phone_rate").html(rate);
		jQuery(".country_code").html(code);
		
	});

	
}

jQuery( 'form.checkout' ).bind( 'checkout_place_order', function() {

	return validatedCustomData( jQuery( this ) );

	
});

function validatedCustomData( $form ) {
	
	var failed = true;
	var billing_first_name = jQuery("input[name='billing_first_name']").val();
	var billing_last_name = jQuery("input[name='billing_last_name']").val();
	var billing_address = jQuery("input[name='billing_address']").val();
	var billing_city = jQuery("input[name='billing_city']").val();
	var billing_state = jQuery("select[name='billing_state']").val();
	var billing_postcode = jQuery("input[name='billing_postcode']").val();
	var billing_phone = jQuery("input[name='billing_phone']").val();
	var billing_email = jQuery("input[name='billing_email']").val();
	
	if (billing_first_name=="" || billing_first_name.length < 2 || (latterOnly(billing_first_name)==false)) {
	jQuery("input[name='billing_first_name']").addClass("flash_error");
		failed = false;
	} else {
		jQuery("input[name='billing_first_name']").removeClass("flash_error");
	}

	if (billing_last_name=="" || billing_last_name.length < 2 || (latterOnly(billing_last_name)==false)) {
	jQuery("input[name='billing_last_name']").addClass("flash_error");
		failed = false;
	} else {
		jQuery("input[name='billing_last_name']").removeClass("flash_error");
	}
	if (billing_address=="") {
		jQuery("input[name='billing_address']").addClass("flash_error");
		failed = false;
	} else {
		jQuery("input[name='billing_address']").removeClass("flash_error");
	}
	
	if (billing_city=="") {
		jQuery("input[name='billing_city']").addClass("flash_error");
		failed = false;
	} else {
		jQuery("input[name='billing_city']").removeClass("flash_error");
	}
	
	if (billing_state=="" || billing_state==0) {
		jQuery("select[name='billing_state']").addClass("flash_error");
		failed = false;
	} else {
		
		//console.log(bank_monthly_billing_state);
		jQuery("select[name='billing_state']").removeClass("flash_error");
	}
	
	if (billing_postcode=="") {
		jQuery("input[name='billing_postcode']").addClass("flash_error");
		failed = false;
	} else {
		jQuery("input[name='billing_postcode']").removeClass("flash_error");
	}
	if (billing_phone=="" || billing_phone.length<6 || billing_phone.length>11) {
			
		jQuery("input[name='billing_phone']").addClass("flash_error");
		failed = false;
	
	} else {
		
		jQuery("input[name='billing_phone']").removeClass("flash_error");
	}
	if (billing_email=="" || email_reg.test(billing_email) == false) {
		failed = false;
		jQuery("input[name='billing_email']").addClass("flash_error");
		
	} else {
		/*
		var response = jQuery.get("https://isitarealemail.com/api/email/validate?email=" +billing_email,
		function responseHandler(data) {
			data.status ==='valid';
			if (data.status === 'valid') {
				jQuery("input[name='billing_email']").removeClass("flash_error");
			} else {
				jQuery("input[name='billing_email']").addClass("flash_error");
				failed = false;
			}
		})
		*/
	}
	
	if(jQuery("input[name='wc-moneris-account-number']").length){
		var account_number=jQuery("input[name='wc-moneris-account-number']").val();
		if (account_number=="") {
		jQuery("input[name='wc-moneris-account-number']").addClass("flash_error");
		failed = false;
		} else {
			jQuery("input[name='wc-moneris-account-number']").removeClass("flash_error");
		}
	}
	if(jQuery("input[name='wc-moneris-account-number']").length){
		var account_number=jQuery("input[name='wc-moneris-account-number']").val();
		if (account_number=="") {
		jQuery("input[name='wc-moneris-account-number']").addClass("flash_error");
		failed = false;
		} else {
			jQuery("input[name='wc-moneris-account-number']").removeClass("flash_error");
		}
	}
	if(jQuery("input[name='wc-moneris-expiry']").length){
		var moneris_expiry=jQuery("input[name='wc-moneris-expiry']").val();
		if (moneris_expiry=="") {
		jQuery("input[name='wc-moneris-expiry']").addClass("flash_error");
		failed = false;
		} else {
			jQuery("input[name='wc-moneris-expiry']").removeClass("flash_error");
		}
	}
	if(jQuery("input[name='wc-moneris-csc']").length){
		var moneris_csc=jQuery("input[name='wc-moneris-csc']").val();
		if (moneris_csc=="") {
		jQuery("input[name='wc-moneris-csc']").addClass("flash_error");
		failed = false;
		} else {
			jQuery("input[name='wc-moneris-csc']").removeClass("flash_error");
		}
	}
	
	return failed;
}


function valid_credit_card(value) {
	// Accept only digits, dashes or spaces
	  if (/[^0-9-\s]+/.test(value)) return false;
	
	  // The Luhn Algorithm. It's so pretty.
	  let nCheck = 0, bEven = false;
	  value = value.replace(/\D/g, "");
  
	  for (var n = value.length - 1; n >= 0; n--) {
		  var cDigit = value.charAt(n),
				nDigit = parseInt(cDigit, 10);
  
		  if (bEven && (nDigit *= 2) > 9) nDigit -= 9;
  
		  nCheck += nDigit;
		  bEven = !bEven;
	  }
  
	  return (nCheck % 10) == 0;
}

function SubmitSignup() {
	document.signup.submit();
  //window.location = "/residential/checkout/";
}

function ValidateCreditCardExpiry(value, control_name) {
	var cc_month = "";
	var cc_year = "";
	var failed = false;

	if( value.length > 2 ) {
		var cc_monthly_billing_card_expiry = value.split('/');
		cc_month = parseInt(cc_monthly_billing_card_expiry[0]);
		cc_year = parseInt(cc_monthly_billing_card_expiry[1]);
	} else {
		cc_month = 0;
		cc_year = 0;
	}

	if (cc_year < 100) {
		cc_year = 2000 + parseInt(cc_year);
	} else {
		failed = true;
	}

	if ( cc_month ==undefined ||
		cc_month =="" ||
		cc_month ==0 ||
		cc_month >12 ||
		cc_month < 1 ) {
		failed = true;
	} else {
		if (cc_month.length==1) {
			//cc_monthly_billing_card_expiry_month = "0"+cc_monthly_billing_card_expiry_month;
			//jQuery("input[name='checkout[monthly_cc][billing_card_expiry]']").val(cc_monthly_billing_card_expiry_month);
		}
	}

	if ( cc_year ==undefined ||
		cc_year =="" ||
		cc_year ==0 ) {
		failed = true;
	} else {
	}

	if( failed == false ) {
		const now = new Date()
		const secondsSinceEpoch = Math.round(now.getTime() / 1000);
		const exp = Math.round( new Date(cc_year, cc_month, 1).getTime() / 1000);
		if( exp <= secondsSinceEpoch ) {
			failed = true;
		}
	}

	if( failed ) {
		jQuery("input[name='" + control_name + "']").addClass("flash_error");
		return false;
	}

	jQuery("input[name='" + control_name + "']").removeClass("flash_error");
	return true;
}


