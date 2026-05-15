app.controller(
    "labtestController",
    function labtestController($scope, $http, $uibModal) {
        $scope.patient = {
            reportDate: new Date(),
            reportTime: "",
            patientName: "Smita Chaudhari",
            contactNo: 9876543210,
            age: {
                year: 33,
                month: 10,
                days: 21,
            },
            gender: "Female",
            address: "Pune",
            orderingDoctor: "Dr. Vaidya Patil",
            collectionDate: new Date(),
            collectionTime: "",
            testName: "",
            registrationNo: "12345-67890",
        };

        $scope.selectedTests = [];

        $http.get("system.properties").then(function(response) {
            $scope.LOINCSERV_IP = response.data.loincservip;
            console.log(loincservip);
        });

        $scope.test = {
            name: "",
            code: "",
            maxReportingDate: new Date(),
            today: new Date().getFullYear() +
                "-" +
                new Date().getMonth() +
                "-" +
                new Date().getDate(),
            reportTableHeaders: [
                { text: "Name", style: "header" },
                { text: "Value", style: "header" },
                { text: "Units", style: "header" },
            ],
            reportStyles: {
                header: {
                    bold: true,
                    color: "#000",
                    fontSize: 11,
                },
                demoTable: {
                    color: "#666",
                    fontSize: 10,
                    margin: [0, 20],
                },
                reportTitle: {
                    bold: true,
                    fontSize: 20,
                    alignment: "center",
                    lineHeight: 2,
                },
            },

            downloadPdf: function() {
                var _this = this;
                console.log($scope.labInfo);

                let reportData = {
                    content: [
                        { text: "Laboratory Test Report", style: "reportTitle" },
                        {
                            columns: [{
                                    text: "Patient: " + $scope.patient.patientName,
                                    fontSize: 10,
                                },
                                {
                                    text: "Reg. No: " + $scope.patient.registrationNo,
                                    fontSize: 10,
                                },
                                { text: "Contact: " + $scope.patient.contactNo, fontSize: 10 },
                            ],
                        },
                        {
                            columns: [{
                                    text: "Age: " +
                                        $scope.patient.age.year +
                                        " Years, " +
                                        $scope.patient.age.month +
                                        " Months ",
                                    fontSize: 10,
                                },
                                { text: "Gender: " + $scope.patient.gender, fontSize: 10 },
                                { text: "Address: " + $scope.patient.address, fontSize: 10 },
                            ],
                        },
                        {
                            columns: [{
                                    text: "Ref. Dr.: " + $scope.patient.orderingDoctor,
                                    fontSize: 10,
                                },
                                {
                                    text: "Collected On: " +
                                        ($scope.patient.collectionDate ?
                                            _this.getFormattedDate($scope.patient.collectionDate) :
                                            "-"),
                                    fontSize: 10,
                                },
                                {
                                    text: "Reported On: " +
                                        ($scope.patient.reportDate ?
                                            _this.getFormattedDate($scope.patient.reportDate) :
                                            "-"),
                                    fontSize: 10,
                                },
                            ],
                        },
                    ],
                    styles: _this.reportStyles,
                };

                let tableContent = {
                    style: "demoTable",
                    table: {
                        widths: ["*", 70, 100],
                        headerRows: 1,
                        body: [],
                    },
                    layout: "headerLineOnly",
                };

                let tableBody = [];
                tableBody.push(_this.reportTableHeaders);
                $scope.selectedTests.forEach((selTest) => {
                    tableBody.push([{
                        colSpan: 3,
                        text: selTest.name,
                        fontSize: 11,
                        bold: true,
                        margin: [0, 10, 0, 0],
                    }, ]);
                    selTest.labInfo.forEach((labRecord) => {
                        if (!(labRecord.Children && labRecord.Children.length)) {
                            tableBody.push([
                                labRecord.ShortName,
                                labRecord.testValue ? "" + labRecord.testValue : "-",
                                labRecord.selUnit,
                            ]);
                        } else {
                            labRecord.Children.forEach((childReport) => {
                                tableBody.push([
                                    childReport.Name,
                                    childReport.testValue ? "" + childReport.testValue : "-",
                                    childReport.selUnit,
                                ]);

                                if (
                                    childReport.grandChildren &&
                                    childReport.grandChildren.length > 0
                                ) {
                                    childReport.grandChildren.forEach((grandChild) => {
                                        tableBody.push([
                                            { text: grandChild.Name, margin: [10, 0, 0, 0] },
                                            grandChild.testValue ? "" + grandChild.testValue : "-",
                                            grandChild.selUnit,
                                        ]);
                                    });
                                }
                            });
                        }
                    });
                });
                // $scope.labInfo.forEach(labRecord => {
                //   tableBody.push([labRecord.ShortName, labRecord.testValue ? '' + labRecord.testValue : '--', labRecord.ExampleUnits, '']);

                //   if (labRecord.Children && labRecord.Children.length) {
                //     tableBody.push(_this.createChildReportTable(labRecord.Children))
                //   }
                // });

                tableContent.table.body = tableBody;
                reportData.content.push(tableContent);

                console.log(reportData);
                pdfMake
                    .createPdf(reportData)
                    .download("Report_" + $scope.patient.patientName + ".pdf");
            },

            search: function(searchTerm) {
                var testObject;
                testObject = $scope.test;
                $scope.hidethis = true;
                var url =
                    $scope.LOINCSERV_IP +
                    "v2/search?class=ALL&classType=ALL&component=ALL&exampleUnits=ALL&limit=281&method=ALL&panelType=ALL&property=ALL&scale=ALL&sortByRank=false&status=ACTIVE&system=ALL&term=" +
                    searchTerm +
                    "&timing=ALL";

                $scope.showProgessbar = true;
                $http.get(url).then(function(response) {
                    if (response.status == 200) {
                        $scope.hidethis = false;
                        $scope.labtests = response.data;
                        console.log(response.status);
                        $scope.showProgessbar = false;
                        response.data.sort((a, b) => {	
                            let along = a.LONG_COMMON_NAME.toLowerCase()	
                            let blong = b.LONG_COMMON_NAME.toLowerCase()	
                            let aindex = along.indexOf($scope.test.name.toLowerCase())	
                            let bindex = blong.indexOf($scope.test.name.toLowerCase())	
                            if (aindex == -1) {	
                                return 1	
                            }	
                            if (aindex < bindex) {	
                                return -1	
                            } else if (aindex > bindex) {	
                                return 1	
                            } else {	
                                return along.localeCompare(blong)	
                            }	
                        })
                        return response.data;
                    }
                });
            },

            selectTestName: function(testName, testCode) {
                $scope.test.name = testName;
                $scope.test.code = testCode;
                $scope.hidethis = true;
            },

            showDetails: function(labInfo) {
                var urlLookup =
                    $scope.LOINCSERV_IP + "v2/lookup?loincNumber=" + labInfo;
                $http.get(urlLookup).then(function(responseLookup) {
                    $scope.labdetails = responseLookup.data;
                    console.log($scope.labinformation);
                });

                viewGenericDetailModal = $uibModal.open({
                    templateUrl: "views/LabTestDetails.html",
                    scope: $scope,
                    windowClass: "large-Modal",
                    backdrop: "static",
                });
                viewGenericDetailModal.result.then(
                    function() {},
                    function() {}
                );
            },

            filltextbox: function() {
                // $scope.test.name = string;
                // $scope.hidethis = true;
                var urlLookup =
                    $scope.LOINCSERV_IP + "v2/lookup?loincNumber=" + $scope.test.code;

                let _this = this;
                $http.get(urlLookup).then(function(responseLookup) {
                    let selectedTest = {
                        name: $scope.test.name,
                        code: $scope.test.code,
                        labInfo: responseLookup.data,
                    };

                    _this.enrichLabInfo(selectedTest.labInfo);

                    $scope.selectedTests.push(selectedTest);
                    $scope.test.name = "";
                    $scope.test.code = "";
                    // if (!$scope.labInfo) {
                    //   $scope.labInfo = responseLookup.data;
                    // } else {
                    //   $scope.labInfo = $scope.labInfo.concat(responseLookup.data);
                    // }
                    // _this.downloadPdf();
                });
            },

            enrichLabInfo: function(labInfo) {
                let _this = this;
                if (labInfo) {
                    labInfo.forEach((lab) => {
                        // Filter out child where name if blank
                        lab.Children = lab.Children.filter((child) => child.Name != "");

                        if (lab.Children && lab.Children.length > 0) {
                            lab.Children.forEach((child) => {
                                // Split the Units for child
                                child.units = _this.splitUnits(child, child.Example_UCUM_UNITS);

                                if (child.grandChildren && child.grandChildren.length > 0) {
                                    // Filter out grandchild where name if blank
                                    child.grandChildren = child.grandChildren.filter(
                                        (grandChild) => grandChild.Name != ""
                                    );

                                    // Split the Units for Grandchild
                                    child.grandChildren.forEach((grandChild) => {
                                        grandChild.units = _this.splitUnits(
                                            grandChild,
                                            grandChild.Example_UCUM_UNITS
                                        );
                                    });
                                }
                            });
                        } else {
                            lab.units = _this.splitUnits(lab, lab.ExampleUnits);
                        }
                    });
                }
            },

            deleteReport: function(testCode) {
                console.log("deleting records at index - " + testCode);
                $scope.selectedTests = $scope.selectedTests.filter(
                    (test) => test.code !== testCode
                );
            },

            splitUnits: function(model, units) {
                var options = units.split(";");
                if (!model.selUnit) {
                    model.selUnit = options[0];
                }
                return options;
            },

            getFormattedDate: function(date) {
                let month = date.getMonth() + 1;
                return month + "/" + date.getDate() + "/" + date.getFullYear();
            },
            getFormattedTime: function(date) {
                return date.toLocaleTimeString();
            },
            createChildReportTable: function(childLabReports) {
                let childRecordObject = {
                    colSpan: 4,
                    table: {
                        widths: ["*", "*", "*", "*"],
                        headerRows: 1,
                        body: [],
                    },
                    layout: "headerLineOnly",
                };

                let body = [
                    [
                        { text: "Name", style: "header" },
                        { text: "Value", style: "header" },
                        { text: "Units", style: "header" },
                        { text: "Required", style: "header" },
                    ],
                ];

                childLabReports.forEach((report) => {
                    body.push([
                        report.Name,
                        report.testValue ? "" + report.testValue : "",
                        report.Example_UCUM_UNITS,
                        report.ObservationRequiredInPanel,
                    ]);
                });

                childRecordObject.table.body = body;
                return [childRecordObject];
            },
        };
    }
);
