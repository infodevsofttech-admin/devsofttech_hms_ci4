<html>

<head>
    <style>
        .container {
            position: relative;
            
            border: 1px red solid;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://doconline.dhms.in/external_api.js"></script>
    <script>
        var apiObj = null;

        function StartMeeting() {
            const domain = 'doconline.dhms.in';
            const options = {
                roomName: '<?=$doc_name?>_<?=$doc_id?>',
                width: '100%',
                height: '100%',
                userName : 'dsbisht',
                password : 'Bisht1979',
                parentNode: document.querySelector('#jitsi-meet-conf-container'),
                userInfo: {
                    displayName: '<?=$doc_name?> : <?=H_Name?>'
                },
                configOverwrite: {

                },
                interfaceConfigOverwrite: {
                    DISPLAY_WELCOME_PAGE_CONTENT: false,
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera'
                    ],
                },
                onload: function() {
                    alert('loaded');
                }
            };

            apiObj = new JitsiMeetExternalAPI(domain, options);

            apiObj.addEventListeners({
                readyToClose: function() {
                    alert('going to close');
                    $("#jitsi-meet-conf-container").empty();
                }
            });

            apiObj.executeCommand('subject', '');
        }

        function HangupCall() {
            apiObj.executeCommand('hangup');
        }
    </script>

    <script>
        $(function() {
            $('#btnStart').on('click', function() {
                StartMeeting();
            });
        });
    </script>
</head>

<body>
    <button id='btnStart'>Start</button>
    <div class="container">
        <div id='jitsi-meet-conf-container'></div>
    </div>
</body>

</html>