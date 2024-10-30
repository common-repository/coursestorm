/**
 * Simple JavaScript Inheritance
 * By John Resig http://ejohn.org/
 * MIT Licensed.
 * 
 * http://ejohn.org/blog/simple-javascript-inheritance/
 * Inspired by base2 and Prototype
 * 
 */
(function() {
	var t = false,
		e = /xyz/.test(function() {}) ? /\b_super\b/ : /.*/;
	this._CourseStormClass = function() {}, _CourseStormClass.extend = function(i) {
		function o() {
			!t && this.init && this.init.apply(this, arguments)
		}
		var r = this.prototype;
		t = true;
		var n = new this;
		t = false;
		for (var a in i) n[a] = 'function' == typeof i[a] && 'function' == typeof r[a] && e.test(i[a]) ? function(t, e) {
			return function() {
				var i = this._super;
				this._super = r[t];
				var o = e.apply(this, arguments);
				return this._super = i, o
			}
		}(a, i[a]) : i[a];
		return o.prototype = n, o.prototype.constructor = o, o.extend = arguments.callee, o
	}
})();

var coursestormSearch = _CourseStormClass.extend({
	/**
	 * Initializing
	 * 
	 */
	init: function(options)
	{},

	// Go to category archive on category filter submit
	filter: function(forms)
	{
		this._applyFilter(forms);
	},

	// Apply the sort filter to the url
	sort: function(form)
	{
		this._applySort(form);
	},

	search: function(form)
	{
		var location = form.find('#coursestorm_search_location');
		
		form.submit(function(e){
			// If we have the location field and it is empty
			// require the value
			if (location.length && location.val().length == 0) {
				e.preventDefault();
				this.handleFormValidation(location);

				return;
			}
		}.bind(this));

		location.on('change', function(e) {
			this.removeValidationErrors(location);
		}.bind(this));

	},
	
	handleFormValidation: function(field) {
		validationError = jQuery('<div class="coursestorm-validation-error">Please provide a location for your search</div>');

		field.parent().addClass('coursestorm-validation-highlight')
		field.focus();
		validationError.insertAfter('#coursestorm_search_location');
	},

	removeValidationErrors: function(field) {
		field.parent().removeClass('coursestorm-validation-highlight')
		field.next('.coursestorm-validation-error').remove();
	},

	_applySort: function(form) {
		var filter = jQuery('#sort').val(),
			action = form.attr('action');
			firstQueryParam = action.substring(action.lastIndexOf('/') + 1);
			sortQuery = this._getQueryString('sort');
			usingPlainPermalinks = firstQueryParam.indexOf("?");

		if( filter.length > 0 ) {
			if( 0 === usingPlainPermalinks ) {
				if( sortQuery ) {
					newUrl = action.replace( sortQuery, 'sort' + '=' + filter );
				} else {
					newUrl = action + '&' + 'sort' + '=' + filter;
				}
			} else {
				if( sortQuery ) {
					newUrl = action.replace( sortQuery, 'sort' + '=' + filter );
				} else {
					newUrl = action + '?' + 'sort' + '=' + filter;
				}
			}
		} else {
			newUrl = action.replace( sortQuery, '' );
		}

		jQuery(location).attr('href', newUrl);
	},

	_applyFilter: function(forms) {
		var element = forms.find('select.postform'),
			slug = element.val(),
			action = forms.attr('action');
			// get hardcoded url of the first category
			catUrl = forms.data('category-url').replace(/\/$/, ''),
			catTaxonomy = forms.data('category-taxonomy'),
			catQuery = this._getQueryString(catTaxonomy);
			sortQuery = this._getQueryString('sort');
			postTypeQuery = this._getQueryString('post_type');
			searchTermQuery = this._getQueryString('coursestorm_search_term');
			locationQuery = this._getQueryString('coursestorm_search_location');
			radiusQuery = this._getQueryString('coursestorm_search_radius');
			value = catUrl.substring(catUrl.lastIndexOf('/') + 1),
			usingPlainPermalinks = value.indexOf("?");
		
		// change term url to use correct slug
		if( -1 == slug ) {
			return;
		} else if( 0 == slug ) {
			if( 0 === usingPlainPermalinks ) {
				newUrl = '/?post_type=coursestorm_class';
			} else {
				newUrl = '/class/';
			}
		} else if( 0 === usingPlainPermalinks ) {
			if( catQuery ) {
				newUrl = action.replace( catQuery, catTaxonomy + '=' + slug );
			} else {
				newUrl = '?' + catTaxonomy + '=' + slug;
			}
		} else {
			if( sortQuery ) {
				newUrl = catUrl.replace(value, slug) + '?' + sortQuery;
			} else {
				newUrl = catUrl.replace(value, slug);
			}
		}
		
		if (postTypeQuery && searchTermQuery) {
			searchPrefix = (sortQuery || catQuery) ? '&' : '?';
			newUrl += searchPrefix + postTypeQuery + '&' + searchTermQuery;
		}

		if (locationQuery && radiusQuery) {
			searchPrefix = (sortQuery || catQuery || postTypeQuery || searchTermQuery) ? '&' : '?';
			newUrl += searchPrefix + radiusQuery + '&' + locationQuery;
			if (!sortQuery) {
				newUrl += '&sort=distance';
			}
		}

		jQuery(location).attr('href', newUrl);
	},

	_getQueryString: function(variable=null)
	{
		var query = window.location.search.substring(1);
		var vars = query.split("&");
		for (var i=0;i<vars.length;i++) {
						var pair = vars[i].split("=");
						if(pair[0] == variable){return vars[i];}
		}
		return(false);
	}
});

jQuery(document).ready(function($) {
	var search = new coursestormSearch();
	// Submit the form on field change...
	$('#sort').change(function() {
		$(this).parent('form').submit();
	});
	// Catch the form submission, and handle it.
	$('#sort-filter-form').submit(function(e) {
		e.preventDefault();
		search.sort($(this));
	});

	// Submit the form on field change...
	$('.coursestorm-filter-form > select').change(function() {
		$(this).parent('form').submit();
	});
	// Catch the form submission, and handle it.
	$('#categories-filter-select, #categories-widget-select').submit(function(e) {
		e.preventDefault();
		search.filter($(this));
	});
	search.search($('#coursestorm-searchform'));
});