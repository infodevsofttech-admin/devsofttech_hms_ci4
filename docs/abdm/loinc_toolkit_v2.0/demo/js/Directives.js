app.directive('number', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9/.]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('sctidnumber', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('unit', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|^[^a-zA-Z ]|[^a-zA-Z\/ ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('strength', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|^[^0-9\. ]|[^0-9\/\. ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('molecularwgt', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|^[^0-9a-zA-Z\. ]|[^0-9a-zA-Z\/\. ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.filter('unique', function() {
	return function (arr, field) {
	    var o = {}, i, l = arr.length, r = [];
	    for(i=0; i<l;i+=1) {
	      o[arr[i][field]] = arr[i];
	    }
	    for(i in o) {
	      r.push(o[i]);
	    }
	    return r;
	  };
});

app.directive('doseform', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/[^a-zA-Z\- ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('uniinumber', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/[^0-9A-Z]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('casnumber', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/[^0-9\-]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('iupacname', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9a-zA-Z\(\)\-\[\]\, ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('inchl', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9a-zA-Z\(\)\-\+\,\/\= ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});
app.directive('smiles', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9a-zA-Z\(\)\=\@\.\[\]\+\- ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});
app.directive('molecularformula', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]|[^0-9a-zA-Z ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('removestartingspace', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/^[ ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('alphabetic', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				if(text!="")
					{
					this.value = text.replace(/^[ ]|[^a-zA-Z ]/g,'');
					scope.$apply(ctrl.$setViewValue(this.value ));
					}
			
			});	
		}
	};
});

//directive to validate organization field
app.directive('licenseno', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				if(text!="")
				{
				this.value = text.replace(/^[^a-zA-Z0-9]|[^a-zA-Z0-9\&\-\:\_\(\) ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
				}
			});	
		}
	};
});
//directive to validate organization field
app.directive('ecipient', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				if(text!="")
				{
				this.value = text.replace(/^[^a-zA-Z0-9]|[^a-zA-Z0-9\,\.\&\-\:\_\(\) ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
				}
			});	
		}
	};
});

//directive to validate organization field
app.directive('drugname', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				if(text!="")
				{
				this.value = text.replace(/^[^a-zA-Z0-9]|[^a-zA-Z0-9\.\&\-\_\/\+\%\(\) ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
				}
			});	
		}
	};
});

//directive to validate organization field
app.directive('organization', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				if(text!="")
				{
				this.value = text.replace(/^[^a-zA-Z0-9]|[^a-zA-Z0-9\/\,\.\&\'\-\@\:\_\(\) ]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
				}
			});	
		}
	};
});

app.directive('alphanumeric', function(){
	return {
		require: 'ngModel',
		link: function(scope, elem, attr, ctrl){
			elem.bind('keyup', function(e) {

				var text = this.value;
				this.value = text.replace(/[^a-zA-Z0-9\s,\/]/g,'');
				scope.$apply(ctrl.$setViewValue(this.value ));
			});	
		}
	};
});

app.directive('compile', ['$compile', function ($compile) {
    return function (scope, element, attrs) {
        scope.$watch(
      function (scope) {
          return scope.$eval(attrs.compile);
      },
      function (value) {
          element.html(value);
          $compile(element.contents())(scope);
      }
    );
    };
}]);

app.directive('ngConfirmClick', [
	  function(){
	    return {
	      priority: 100,
	      restrict: 'A',
	      //terminal: true,
	      link: function(scope, element, attrs){
	        element.bind('click', function(e){
	          var message = attrs.ngConfirmClick;
	          var clickAction = attrs.confirmedClick;
	          if ( window.confirm(message) ) {
                  scope.$eval(clickAction)
              }
	        });
	      }
	    }
	  }
	]);

//original
app.directive('upperBoxLoading', function () {
	return {
		restrict: 'E',
		replace:true,
		template: '<div class="loading"><img src="app/images/bluespinner.gif" class="dropBoxSpinnerImage" /><h3 class="plzWaitMsg">Please Wait</h3></div>',
		link: function (scope, element, attr) {
			scope.$watch('upperBoxLoading', function (val) {
				if (val)
					$(element).show();
				else
					$(element).hide();
			});
		}
	}
});

app.directive('lowerBoxLoading', function () {
	return {
		restrict: 'E',
		replace:true,
		template: '<div class="loading"><img src="app/images/bluespinner.gif" class="dropBoxSpinnerImage" /><h3 class="plzWaitMsg">Please Wait</h3></div>',
		link: function (scope, element, attr) {
			scope.$watch('lowerBoxLoading', function (val) {
				if (val)
					$(element).show();
				else
					$(element).hide();
			});
		}
	}
});

app.directive('serchResultsLoading', function () {
	return {
		restrict: 'E',
		replace:true,
		template: '<div><div class="searchingImage"><img src="app/images/search-spinner.gif" class="searchSpinner" /></div><div>Searching</div></div>',
		link: function (scope, element, attr) {
			scope.$watch('serchResults', function (val) {
				if (val)
					$(element).show();
				else
					$(element).hide();
			});
		}
	}
});

app.directive('loading', function () {
	return {
		restrict: 'E',
		replace:true,
		template: '<div class="logSpinnerDiv"><img src="app/images/bluespinner.gif" class="dropBoxSpinnerImage" /><h3 class="plzWaitMsg">Please Wait</h3></div>',
		link: function (scope, element, attr) {
			scope.$watch('loading', function (val) {
				if (val)
					$(element).show();
				else
					$(element).hide();
			});
		}
	}
});

app.directive('loadingAddFromSnomed', function () {
	return {
		restrict: 'E',
		replace:true,
		template: '<div class="logSpinnerDiv"><img src="app/images/bluespinner.gif" class="dropBoxSpinnerImage" /><h3 class="plzWaitMsg">Please Wait</h3></div>',
		link: function (scope, element, attr) {
			scope.$watch('loadingAddFromSnomed', function (val) {
				if (val)
					$(element).show();
				else
					$(element).hide();
			});
		}
	}
});

app.directive('resize', function ($window) {
	return function (scope, element) {
		var w = angular.element($window);
		// console.log("window height is"+$(window).height());
		scope.getWindowDimensions = function () {
			return {
				'h':$(window).height(),
				'w':$(window).width()
			};
		};
		scope.$watch(scope.getWindowDimensions, function (newValue, oldValue) {
			scope.windowHeight = newValue.h;
	//console.log("window height is "+newValue.h+"header height is"+$('#header').outerHeight(true)+"footer height is"+$('#footer').outerHeight(true)+"min height is "+(newValue.h-$('#header').outerHeight(true)-$('#footer').outerHeight(true)));
			scope.style = function () {
				return {
			//'min-height': (newValue.h-$('#header').outerHeight(true)-$('#footer').outerHeight(true)) + 'px'
			'min-height': (newValue.h-37) + 'px'
		//'min-height': (newValue.h-$('#footer').outerHeight(true)) + 'px'
				};
			};
			
			scope.getWindowSize=function()
			{
				/*	if(newValue.w<768)
					{
					console.log("width in if"+newValue.w)
					return 'showCard=true';
					}
				else
					{
					
					console.log("width in else"+newValue.w)
					return 'showCard=false';
					};*/
					
				return newValue.w;
			}

		}, true);

		w.bind('resize', function () {
			scope.$apply();
		});
	}
});


app.directive("matchPassword", function () {
    return {
        require: "ngModel",
        scope: {
            otherModelValue: "=matchPassword"
        },
        link: function(scope, element, attributes, ngModel) {

            ngModel.$validators.matchPassword = function(modelValue) {
                return modelValue == scope.otherModelValue;
            };

            scope.$watch("otherModelValue", function() {
                ngModel.$validate();
            });
        }
    };
});


