var itemsApp = angular.module( 'itemsApp', ['ngAnimate', 'ngDialog'] );

itemsApp.controller('itemsCtrl', ['$scope', '$timeout', 'ngDialog', 
    function( $scope, $timeout, ngDialog ) {
        
        $ = jQuery;
        
        $scope.requests = []; // track gotoPage requests
        
        $scope.ajaxparams = function() {
            var sendmembertype = $.merge( [], $scope.membertype );
            var sendstate = $.merge( [], $scope.state );
            if(sendstate.length === $('.state').length ) sendstate = [];
            return { 'state':sendstate, 
                'membertype':sendmembertype, 
                'letter':$scope.letter, 
                'clss':$scope.clss, 
                'search':$scope.search 
            };
        };

        $scope.gotoPage = function(page) {
           $scope.paged = page;
           $.each( $scope.requests, function(index, el) { // get rid of outstanding requests before making a new one
               el.abort();
           });
           $scope.requests = [];
           if($scope.membertype.length===0 || $scope.state.length===0 ) {
               $scope.data.items = []; // no membertypes requested, show blank
               $scope.data.pages = 0;
               $scope.showLoading = false;
               $scope.dopagearray();
               $('#items').animate( { opacity: 1 } );
           } else {
                $scope.showLoading = true;
                var data = $scope.ajaxparams();
                data.page = page;
                data.rows_per_page = $scope.data.rows_per_page;
                data.action = 'CBDWeb_get_items';
                $scope.requests.push(
                    $.post($scope.data.ajaxurl + "/otu-members", data, function( response ){
                       $.extend($scope.data, response);
                       $scope.dopagearray();
                       $timeout ( function() {
                           $('#items').animate( { opacity: 1 } );
                       });
                       $scope.showLoading = false;
                    })
                );
//                $scope.hide();
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

        $scope.show = function(item, $) {
            $ = jQuery;
            $scope.item = item;
            $scope.album_requested = false;
            $scope.$on('ngDialog.opened', function(e, $dialog) {
                if ( $scope.album_requested ) return;
                $scope.album_requested = true;
                if ( ! $scope.item.album || $scope.item.album <= 1 ) {
                    $('#wppa-container-1').html("No photographs loaded by this member");
                    return;
                }
                var ajaxurl = $scope.data.siteurl + "/wp-content/plugins/wp-photo-album-plus/wppa-ajax-front.php?" +
                    "action=wppa&wppa-action=render&wppa-cover=0&wppa-album=" + $scope.item.album + "&wppa-occur=1&wppa-size=640";
                wppaDoAjaxRender( 1, ajaxurl, '' )
            })
            ngDialog.open( {
                template: 'templateId',
                className: 'ngdialog-theme-default',
                scope: $scope
            });            
        };
//        $scope.hide = function() {
//            $('.listitems').animate( { opacity: 1 }, { complete: $scope.displayblock } );
//            $('#item').animate( { opacity: 0 }, { complete: $scope.displaynone } );
//        };
//        $scope.displaynone = function ( ) {
//            $(this).css({display: 'none'} );
//        };
//        $scope.displayblock = function () {
//            $(this).css({display: 'block'} );
//           if($(this).attr('id')=='item' ) {
//                $('#item').css({position: "absolute"});
//                $('#item').offset( $scope.pos );
//            }
//        };
        $scope.setletter = function(letter) {
            $scope.letter = letter;
            $scope.gotoPage(1);
        };
        $scope.setclss = function ( clss ) {
            $scope.clss = clss;
            $scope.gotoPage(1);
        }
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
            if( ! item ) return ""; // this can happen when no individual is selected in div#item
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
        $scope.download = function() {
            $scope.showLoading = true;
            var data = $scope.ajaxparams();
            data.action = 'CBDWeb_download_items';
            $.fileDownload( $scope.data.ajaxurl + "?" + $.param(data), {
                failCallback: function ( responseHtml ) {
                    $scope.showLoading = false;
                    $scope.$apply();
                    alert( responseHtml );
                },
                successCallback: function () {
                    $scope.showLoading = false;
                    $scope.$apply();
                }
            })
                .fail( function( responseHtml ) {
                })
                .success( function() {
                    alert ( 'file downloaded ' );
                });
        };
        
        $scope.pos = $('#items').offset();
        $scope.pos.left += 100;
        $scope.pos.top +=50;
        $scope.letter = '';
        $scope.clss = '';
        $scope.membertype = [''];
        $.each( $scope.data.membertypes, function(index, el) { // everything except deceased
            $scope.membertype.push( el.id );
        });
        $scope.state = [''];
        $.each ( $scope.data.states, function(index, el) {
            $scope.state.push ( el );
        });
    }
]);