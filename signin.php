<? include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/inc/inch.php"; ?>
<?
if(AuthUtil::isLoggedIn()){
    echo "<script>alert('비정상적인 접근입니다.'); history.back();</script>";
}
?>
    <script>
        $(document).ready(function(){

            $(".jLogin").click(function(){
                if($(".jEmailTxt").val() == "" || $(".jPasswordTxt").val() == ""){
                    alert("회원 정보를 입력하세요.");
                    return;
                }

                firebase.auth().signInWithEmailAndPassword($(".jEmailTxt").val(), $(".jPasswordTxt").val()).catch(function(error) {
                    // Handle Errors here.
                    var errorCode = error.code;
                    var errorMessage = error.message;
                    alert(errorCode + "/" + errorMessage);
                });

                return;

                callJson(
                    "/midnight/shared/public/route.php?F=UserAuthRoute.requestLogin",
                    {
                        email : $(".jEmailTxt").val(),
                        pwd : $(".jPasswordTxt").val()
                    }
                    , function(data){
                        if(data.returnCode > 0){
                            if(data.returnCode > 1){
                                alert(data.returnMessage);
                            }else{
                                location.href = "index.php";
                            }
                        }else{
                            alert("오류가 발생하였습니다.\n관리자에게 문의하세요.");
                        }
                    }
                )
            });
        });
    </script>

    <!--================Home Banner Area =================-->
        <section class="banner_area">
            <div class="banner_inner d-flex align-items-center">
            	<div class="overlay bg-parallax" data-stellar-ratio="0.9" data-stellar-vertical-offset="0" data-background=""></div>
				<div class="container">
					<div class="banner_content text-center">
						<div class="page_link">
							<a href="#">홈</a>
							<a href="#">마이페이지</a>
						</div>
						<h2>로그인</h2>
					</div>
				</div>
            </div>
        </section>
        <!--================End Home Banner Area =================-->
        
        <!--================Contact Area =================-->
        <section class="contact_area p_120">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="contact_info">
                            <div class="info_item">
                                <i class="lnr lnr-user"></i>
                                <h6><a href="#">회원 로그인</a></h6>
                                <p>업소 및 앱 관리를 위한 회원 로그인입니다. (SNS 계정 사용 불가)<br/>회원가입을 모바일앱을 이용하시기 바랍니다.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <form class="row contact_form" action="contact_process.php" method="post" id="contactForm" novalidate="novalidate">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <input type="email" class="form-control jEmailTxt" id="account" name="account" placeholder="이메일">
                                </div>
                                <div class="form-group">
                                    <input type="password" class="form-control jPasswordTxt" id="password" name="password" placeholder="패스워드">
                                </div>
                            </div>
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn submit_btn jLogin">로그인</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!--================Contact Area =================-->
<? include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/inc/incf.php"; ?>