var chartDirective = function()
	{
		return {
			restrict: 'E',
			replace: true,
			template: '<div></div>',
			scope: {
					chart: '='
				},
			link: function( scope, element, attrs )
				{
					var process = function()
						{
							var defaultOptions = {
									chart: { renderTo: element[0] }
								};
							var config = angular.merge( defaultOptions, scope.chart.config );
							scope.chart.chart = new Highcharts.Chart( config );
						};

					process();
				}
			};
	};