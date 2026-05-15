var app=angular.module('labReportApp',['ngAnimate','ui.bootstrap','ui.bootstrap.tpls']);
app.config(['$qProvider','$httpProvider', function ($qProvider,$httpProvider) {
    $qProvider.errorOnUnhandledRejections(false);
    $httpProvider.defaults.useXDomain = true;
    delete $httpProvider.defaults.headers.common['X-Requested-With'];
}]);
