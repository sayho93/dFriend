<? include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/public/classes/WebRoute.php"; ?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="img/favicon.png" type="image/png">
    <title>미드나잇</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="vendors/linericon/style.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="vendors/owl-carousel/owl.carousel.min.css">
    <link rel="stylesheet" href="vendors/lightbox/simpleLightbox.css">
    <link rel="stylesheet" href="vendors/nice-select/css/nice-select.css">
    <link rel="stylesheet" href="vendors/animate-css/animate.css">
    <link rel="stylesheet" href="vendors/popup/magnific-popup.css">
    <!-- main css -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <script src="js/jquery-3.2.1.min.js"></script>
    <!-- Insert these scripts at the bottom of the HTML, but before you use any Firebase services -->

    <!-- Firebase App (the core Firebase SDK) is always required and must be listed first -->

    <!-- The core Firebase JS SDK is always required and must be listed first -->
    <script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-app.js"></script>

    <!-- TODO: Add SDKs for Firebase products that you want to use
         https://firebase.google.com/docs/web/setup#available-libraries -->
    <script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-analytics.js"></script>

    <!-- Add Firebase products that you want to use -->
    <script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/7.8.1/firebase-storage.js"></script>

    <script>
        // Your web app's Firebase configuration
        var firebaseConfig = {
            apiKey: "AIzaSyDcgTAuKI8gNtut29wFL743SZrBivqchds",
            authDomain: "midnight-pickle.firebaseapp.com",
            databaseURL: "https://midnight-pickle.firebaseio.com",
            projectId: "midnight-pickle",
            storageBucket: "midnight-pickle.appspot.com",
            messagingSenderId: "634955073954",
            appId: "1:634955073954:web:c308c91de1ba3bb7f98afb",
            measurementId: "G-S7FTVQVZ46"
        };
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        firebase.analytics();

    </script>

    <script>
        function signOut(){
            if (firebase.auth().currentUser) {
                // [START signout]
                firebase.auth().signOut();
                // [END signout]
            }
        }
        function signIn(email, password) {
            if (email.length < 4) {
                alert('이메일 주소를 입력하세요.');
                return;
            }
            if (password.length < 4) {
                alert('패스워드를 입력하세요.');
                return;
            }
            // Sign in with email and pass.
            // [START authwithemail]
            firebase.auth().signInWithEmailAndPassword(email, password).catch(function(error) {
                // Handle Errors here.
                var errorCode = error.code;
                var errorMessage = error.message;
                // [START_EXCLUDE]
                if (errorCode === 'auth/wrong-password') {
                    alert('일치하는 않는 패스워드입니다.');
                } else {
                    alert(errorMessage);
                }
                console.log(error);
                // [END_EXCLUDE]
            });
            // [END authwithemail]
        }

        /**
         * Handles the sign up button press.
         */
        function handleSignUp(email, password) {
            if (email.length < 4) {
                alert('이메일 주소를 입력하세요.');
                return;
            }
            if (password.length < 4) {
                alert('패스워드를 입력하세요.');
                return;
            }
            // Sign in with email and pass.
            // [START createwithemail]
            firebase.auth().createUserWithEmailAndPassword(email, password).catch(function(error) {
                // Handle Errors here.
                var errorCode = error.code;
                var errorMessage = error.message;
                // [START_EXCLUDE]
                if (errorCode == 'auth/weak-password') {
                    alert('패스워드가 취약합니다.');
                } else {
                    alert(errorMessage);
                }
                console.log(error);
                // [END_EXCLUDE]
            });
            // [END createwithemail]
        }

        /**
         * Sends an email verification to the user.
         */
        function sendEmailVerification() {
            // [START sendemailverification]
            firebase.auth().currentUser.sendEmailVerification().then(function() {
                // Email Verification sent!
                // [START_EXCLUDE]
                alert('Email Verification Sent!');
                // [END_EXCLUDE]
            });
            // [END sendemailverification]
        }

        function sendPasswordReset(email) {
            // [START sendpasswordemail]
            firebase.auth().sendPasswordResetEmail(email).then(function() {
                // Password Reset Email Sent!
                // [START_EXCLUDE]
                var ua = navigator.userAgent.toLowerCase();
                var isAndroid = ua.indexOf("android") > -1; //&& ua.indexOf("mobile");
                if(isAndroid) {
                    Android.showToast("재설정 메일이 발송되었습니다.");
                }else{
                    alert('재설정 메일이 발송되었습니다.');
                }

                // [END_EXCLUDE]
            }).catch(function(error) {
                // Handle Errors here.
                var errorCode = error.code;
                var errorMessage = error.message;
                // [START_EXCLUDE]
                if (errorCode == 'auth/invalid-email') {
                    alert(errorMessage);
                } else if (errorCode == 'auth/user-not-found') {
                    alert(errorMessage);
                }
                console.log(error);
                // [END_EXCLUDE]
            });
            // [END sendpasswordemail];
        }

        /**
         * initApp handles setting up UI event listeners and registering Firebase auth listeners:
         *  - firebase.auth().onAuthStateChanged: This listener is called when the user is signed in or
         *    out, and that is where we update the UI.
         */
        function initApp() {
            // Listening for auth state changes.
            // [START authstatelistener]
            firebase.auth().onAuthStateChanged(function(user) {

                if (user) {
                    // User is signed in.
                    var displayName = user.displayName;
                    var email = user.email;
                    var emailVerified = user.emailVerified;
                    var photoURL = user.photoURL;
                    var isAnonymous = user.isAnonymous;
                    var uid = user.uid;
                    var providerData = user.providerData;
                    // [START_EXCLUDE]
                    $(".fireDisplay").text(email);
                    $(".fireSignedOut").hide();
                    $(".fireSignedIn").show();
                } else {
                    // User is signed out.
                    $(".fireSignedOut").show();
                    $(".fireSignedIn").hide();
                }
            });
            // [END authstatelistener]
        }

        window.onload = function() {
            initApp();
        };

    </script>
</head>
<body data-spy="scroll" data-target="#mainNav" data-offset="70">

<!--================Header Menu Area =================-->
<header class="header_area">
    <div class="main_menu" id="mainNav">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container">
                <!-- Brand and toggle get grouped for better mobile display -->
                <a class="navbar-brand logo_h" href="index.php"><img src="img/logo.png" alt=""></a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse offset" id="navbarSupportedContent">
                    <ul class="nav navbar-nav menu_nav ml-auto">
                        <li class="nav-item active"><a class="nav-link" href="index.php">홈</a></li>
                        <li class="nav-item"><a class="nav-link" href="notice.php">공지사항</a>

                        <li class="nav-item submenu dropdown fireSignedIn">
                            <a href="#" class="nav-link dropdown-toggle fireDisplay" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">마이페이지</a>
                            <ul class="dropdown-menu">
                                <li class="nav-item"><a class="nav-link" href="signin.php">관리</a></li>
                            </ul>
                        </li>
                        <li class="nav-item submenu dropdown fireSignedOut">
                            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">마이페이지</a>
                            <ul class="dropdown-menu">
                                <li class="nav-item"><a class="nav-link" href="signin.php">로그인</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>
<!--================Header Menu Area =================-->
