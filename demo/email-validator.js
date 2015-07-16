/*jslint browser: true, white: true, plusplus: true, eqeq: true*/
/*global angular, console, alert */

(function () {
  'use strict';

  var app = angular.module('EmailValidator', []);

  // change this variable with email-validator.php path
  app.validator = "../email-validator.php";

  app.controller("EmailValidatorCtrl", function($scope, $http) {

    $scope.showMessage = false;

    $scope.validateEmail = function () {

      $http.get( app.validator, { params : { email : $scope.emailAddress } } )
        .success(function(data, status, header, config) {
        $scope.valid    = data.valid;
        $scope.message  = data.message;
        $scope.showMessage = true;
      })
        .error(function(data, status, header, config) {
        console.log("Error in $http.get " + app.validator + "?email=" + $scope.emailAddress);
      });

    };

  });

}());
