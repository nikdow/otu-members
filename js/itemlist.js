var itemsApp = angular.module('itemsApp', ['ngAnimate']);

itemsApp.controller('itemsCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;

        $scope.gotoPage = function(page) {
           $scope.paged = page;
           $scope.showLoading = true;
            var data = { 'page':page, 'rows_per_page':$scope.data.rows_per_page, 'action':'CBDWeb_get_items' };
            $.post($scope.data.ajaxurl, data, function( response ){
               var ajaxdata = $.parseJSON(response);
               $scope.items = ajaxdata;
               $scope.dopagearray();
               $timeout ( function() {
                   $('#items').animate( { opacity: 1 } ) 
               });
               $scope.showLoading = false;
            });
            $('#items').animate( { opacity: 0 } );
        };
        
        $scope.dopagearray = function() {
            $scope.pagearray = [];
            for(var i = $scope.paged - $scope.range; i <= $scope.paged + $scope.range; i++ ) {
                if(i>0 && i<=$scope.data.pages) $scope.pagearray.push(i);
            }
        };
        
        $scope.items = _items; // pushed from the PHP
        $scope.data = _data;
        $scope.range = 4; // how many links to show in pagination
        $scope.showitems = ($scope.range * 2)+1;
        $scope.paged = 1; // page to display
        $scope.dopagearray();

    }
]);