var chartDirective = function()
    {
        return {
                restrict: 'E',
                replace: true,
                template: '<div></div>',
                scope: {
                        chart: '=',
                        config: '='
                    },
                link: function( scope, element, attrs )
                    {
                        var process = function()
                            {
                                var defaultOptions = {
                                        chart: { renderTo: element[0] }
                                    };
                                var config = angular.extend( defaultOptions, scope.config );
                                Highcharts.setOptions({
                                        global: {
                                            useUTC: false
                                        }
                                    });
                                scope.chart = new Highcharts.Chart( config );
                            };

                        process();
                    }
            };
    };