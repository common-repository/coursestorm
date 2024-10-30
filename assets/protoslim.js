var makeArray = function(iterable) {
	if (!iterable) {
		return [];
	}
	
	if (iterable.toArray) {
		return iterable.toArray();
	} else {
		var results = [];
		for (var i = 0, length = iterable.length; i < length; i++) {
			results.push(iterable[i]);
		}
		
		return results;
	}
};

Function.prototype.bind = function() {
	var __method = this, args = makeArray(arguments), object = args.shift();
	return function() {
		return __method.apply(object, args.concat(makeArray(arguments)));
	}
};

var Class = {
	create: function() {
		var properties = makeArray(arguments);
		
		var _class = function () {
			this.initialize.apply(this, arguments);
		};
		
		_class.addMethods = this.addMethods;
		
		for (var i = 0, length = properties.length; i < length; i++) {
			_class.addMethods(properties[i]);
		}

		_class.prototype.constructor = _class;
		return _class;
	},
	
	addMethods: function(source) {
		properties = Object.keys(source);
	
	    for (var i = 0, length = properties.length; i < length; i++) {
			var property = properties[i], value = source[property];
			
			this.prototype[property] = value;
	    }
	
	    return this;
	}
};