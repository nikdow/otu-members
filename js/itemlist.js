var itemsApp = angular.module('itemsApp', ['ngAnimate']);

itemsApp.controller('itemsCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;

        $scope.gotoPage = function(page) {
           $scope.paged = page;
           $scope.showLoading = true;
            var data = { 'letter':$scope.letter, 'page':page, 'rows_per_page':$scope.data.rows_per_page, 'action':'CBDWeb_get_items' };
            $.post($scope.data.ajaxurl, data, function( response ){
               var ajaxdata = $.parseJSON(response);
               $scope.data.items = ajaxdata;
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
        
        $scope.data = _data;
        $scope.range = 4; // how many links to show in pagination
        $scope.showitems = ($scope.range * 2)+1;
        $scope.paged = 1; // page to display
        $scope.dopagearray();

        $scope.show = function(item) {
            $('.listitems').animate( { opacity: 0 } );
            $('#item').animate( { opacity: 1 } );
            $scope.item = item;
        };
        $scope.hide = function() {
            $('.listitems').animate( { opacity: 1 } );
            $('#item').animate( { opacity: 0 } );
        };
        $scope.setletter = function(letter) {
            $scope.letter = letter;
            $scope.gotoPage(1);
        }
        $scope.letter = '';
        /*
         * position detail panel
         */
        $('#item').css({position: "absolute"});
        var pos = $('#items').offset();
        $('#item').offset( pos );
    }
]);