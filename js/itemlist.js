var itemsApp = angular.module('itemsApp', ['ngAnimate']);

itemsApp.controller('itemsCtrl', ['$scope', '$timeout',
    function( $scope, $timeout ) {
        
        $ = jQuery;

        $scope.gotoPage = function(page) {
           $scope.paged = page;
           if($scope.membertype.length===0 || $scope.state.length===0 ) {
               $scope.data.items = []; // no membertypes requested, show blank
               $scope.data.pages = 0;
               $scope.dopagearray();
           } else {
               $scope.showLoading = true;
                var data = { 'state':$scope.state, 'membertype':$scope.membertype, 'letter':$scope.letter, 'page':page, 'rows_per_page':$scope.data.rows_per_page, 'action':'CBDWeb_get_items' };
                $.post($scope.data.ajaxurl, data, function( response ){
                   var ajaxdata = $.parseJSON(response);
                   $.extend($scope.data, ajaxdata);
                   $scope.dopagearray();
                   $timeout ( function() {
                       $('#items').animate( { opacity: 1 } );
                   });
                   $scope.showLoading = false;
                });
                $scope.hide();
                $('#items').animate( { opacity: 0 } );
            }
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
            $('.listitems').animate( { opacity: 0 }, { complete:$scope.displaynone } );
            $('#item').animate( { opacity: 1 }, { complete: $scope.displayblock } );
            $scope.item = item;
        };
        $scope.hide = function() {
            $('.listitems').animate( { opacity: 1 }, { complete: $scope.displayblock } );
            $('#item').animate( { opacity: 0 }, { complete: $scope.displaynone } );
        };
        $scope.displaynone = function ( ) {
            $(this).css({display: 'none'} );
        };
        $scope.displayblock = function () {
            $(this).css({display: 'block'} );
            if($(this).attr('id')=='item' ) {
                $('#item').css({position: "absolute"});
                $('#item').offset( $scope.pos );
            }
        };
        $scope.setletter = function(letter) {
            $scope.letter = letter;
            $scope.gotoPage(1);
        };
        $scope.togglemembertype = function(membertype) {
            if($.inArray(membertype, $scope.membertype ) > -1 ) {
                var index = $.inArray(membertype, $scope.membertype);
                if(index != -1)
                {
                  $scope.membertype.splice(index, 1);
                }
            } else {
                $scope.membertype.push(membertype);
            }
            $scope.gotoPage(1);
        };
        $scope.isMemberType = function(membertype) {
            return jQuery.inArray(membertype, $scope.membertype)>-1;
        };
        $scope.togglestate = function(state) {
            if($.inArray(state, $scope.state ) > -1 ) {
                var index = $.inArray(state, $scope.state );
                if(index != -1) {
                    $scope.state.splice(index, 1 );
                }
            } else {
                $scope.state.push(state);
            }
            $scope.gotoPage(1);
        }
        $scope.isState = function(state) {
            return jQuery.inArray(state, $scope.state ) > -1;
        }
        $scope.etc = function(item) {
            if( ! item ) return "";
            var etcs = "";
            if(item.deceased==="1") {
                etcs = "deceased";
            } else {
                $.each($scope.data.membertypes, function(index, el) {
                    if(item.membershiplevel===el.id) etcs = el.name;
                });
            }
            return etcs;
        };
        $scope.pos = $('#items').offset();
        $scope.pos.left += 100;
        $scope.pos.top +=50;
        $scope.letter = '';
        $scope.membertype = [''];
        $.each( $scope.data.membertypes, function(index, el) {
            $scope.membertype.push( el.id );
        });
        $scope.state = [''];
        $.each ( $scope.data.states, function(index, el) {
            $scope.state.push ( el );
        });
    }
]);